<?php
/**
 * Script d'export automatique hebdomadaire
 * À exécuter tous les vendredis à 20h via CRON
 * Exemple de ligne CRON : 0 20 * * 5 /usr/bin/php /path/to/weekly_export.php
 */

require_once 'config.php';

// Calculer les dates de la semaine
$monday = date('Y-m-d', strtotime('monday this week'));
$sunday = date('Y-m-d', strtotime('sunday this week'));

try {
    $pdo = getDB();
    
    // Récupérer toutes les données de la semaine
    $stmt = $pdo->prepare("
        SELECT 
            u.name as Membre,
            f.name as Dossier,
            f.color as Couleur,
            t.date as Date,
            t.hours as Heures,
            t.comment as Commentaire,
            IF(t.validated, 'Validé', 'Non validé') as Statut
        FROM tasks t
        JOIN users u ON t.user_id = u.id
        JOIN folders f ON t.folder_id = f.id
        WHERE t.date BETWEEN ? AND ?
        ORDER BY u.name, t.date, f.name
    ");
    $stmt->execute([$monday, $sunday]);
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($data)) {
        echo "Aucune donnée pour la semaine du " . date('d/m/Y', strtotime($monday)) . "\n";
        exit(0);
    }
    
    // Créer le fichier CSV
    $filename = 'planning_sequoia_' . $monday . '.csv';
    $filepath = '/tmp/' . $filename;
    
    $fp = fopen($filepath, 'w');
    
    // BOM UTF-8 pour Excel
    fprintf($fp, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // En-têtes
    fputcsv($fp, array_keys($data[0]), ';');
    
    // Données
    foreach ($data as $row) {
        fputcsv($fp, $row, ';');
    }
    
    fclose($fp);
    
    // Préparer l'email
    $to = 'licences@sequoia.fr';
    $subject = 'Planning Sequoia - Semaine du ' . date('d/m/Y', strtotime($monday));
    
    // Créer le message avec statistiques
    $totalHours = array_sum(array_column($data, 'Heures'));
    $validatedCount = count(array_filter($data, fn($r) => $r['Statut'] === 'Validé'));
    $totalCount = count($data);
    
    $message = "Bonjour,\n\n";
    $message .= "Veuillez trouver ci-joint l'export du planning pour la semaine du ";
    $message .= date('d/m/Y', strtotime($monday)) . " au " . date('d/m/Y', strtotime($sunday)) . ".\n\n";
    $message .= "Statistiques de la semaine :\n";
    $message .= "- Total d'heures : " . number_format($totalHours, 2, ',', ' ') . "h\n";
    $message .= "- Tâches validées : " . $validatedCount . " / " . $totalCount . "\n";
    $message .= "- Taux de validation : " . round(($validatedCount / $totalCount) * 100, 1) . "%\n\n";
    
    // Détail par membre
    $memberStats = [];
    foreach ($data as $row) {
        if (!isset($memberStats[$row['Membre']])) {
            $memberStats[$row['Membre']] = 0;
        }
        $memberStats[$row['Membre']] += $row['Heures'];
    }
    
    $message .= "Heures par membre :\n";
    foreach ($memberStats as $member => $hours) {
        $message .= "- " . $member . " : " . number_format($hours, 2, ',', ' ') . "h\n";
    }
    
    $message .= "\nCordialement,\n";
    $message .= "Planning Sequoia";
    
    // Préparer la pièce jointe
    $fileContent = file_get_contents($filepath);
    $boundary = md5(time());
    
    $headers = "From: planning@sequoia.fr\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: multipart/mixed; boundary=\"{$boundary}\"\r\n";
    
    $body = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
    $body .= $message . "\r\n";
    
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/csv; name=\"{$filename}\"\r\n";
    $body .= "Content-Transfer-Encoding: base64\r\n";
    $body .= "Content-Disposition: attachment; filename=\"{$filename}\"\r\n\r\n";
    $body .= chunk_split(base64_encode($fileContent)) . "\r\n";
    $body .= "--{$boundary}--";
    
    // Envoyer l'email
    if (mail($to, $subject, $body, $headers)) {
        echo "Email envoyé avec succès à " . $to . "\n";
        echo "Export : " . $filename . "\n";
        echo "Total heures : " . $totalHours . "h\n";
    } else {
        echo "Erreur lors de l'envoi de l'email\n";
    }
    
    // Nettoyer le fichier temporaire
    unlink($filepath);
    
} catch (Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
    exit(1);
}
?>