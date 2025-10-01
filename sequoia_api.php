<?php
session_start();
header('Content-Type: application/json');
require_once 'config.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

try {
    switch($action) {
        case 'login':
            $username = $_POST['username'] ?? '';
            $password = $_POST['password'] ?? '';
            
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['name'] = $user['name'];
                echo json_encode(['success' => true, 'user' => $user]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Identifiants incorrects']);
            }
            break;
            
        case 'logout':
            session_destroy();
            echo json_encode(['success' => true]);
            break;
            
        case 'check_session':
            if (isset($_SESSION['user_id'])) {
                echo json_encode(['logged_in' => true, 'user' => [
                    'id' => $_SESSION['user_id'],
                    'username' => $_SESSION['username'],
                    'name' => $_SESSION['name']
                ]]);
            } else {
                echo json_encode(['logged_in' => false]);
            }
            break;
            
        case 'get_users':
            $pdo = getDB();
            $stmt = $pdo->query("SELECT id, name, username FROM users ORDER BY name");
            $users = $stmt->fetchAll();
            echo json_encode($users);
            break;
            
        case 'add_user':
            $name = $_POST['name'] ?? '';
            $username = $_POST['username'] ?? '';
            $password = password_hash('sequoia123', PASSWORD_DEFAULT);
            
            $pdo = getDB();
            $stmt = $pdo->prepare("INSERT INTO users (username, password, name) VALUES (?, ?, ?)");
            $stmt->execute([$username, $password, $name]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;
            
        case 'delete_user':
            $id = $_POST['id'] ?? 0;
            $pdo = getDB();
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'get_folders':
            $pdo = getDB();
            $stmt = $pdo->query("SELECT * FROM folders ORDER BY name");
            $folders = $stmt->fetchAll();
            echo json_encode($folders);
            break;
            
        case 'add_folder':
            $name = $_POST['name'] ?? '';
            $color = $_POST['color'] ?? '#015871';
            
            $pdo = getDB();
            $stmt = $pdo->prepare("INSERT INTO folders (name, color) VALUES (?, ?)");
            $stmt->execute([$name, $color]);
            echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
            break;
            
        case 'delete_folder':
            $id = $_POST['id'] ?? 0;
            $pdo = getDB();
            $stmt = $pdo->prepare("DELETE FROM folders WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'get_tasks':
            $startDate = $_GET['start_date'] ?? date('Y-m-d');
            $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+21 days'));
            
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT t.*, u.name as user_name, f.name as folder_name, f.color as folder_color
                FROM tasks t
                JOIN users u ON t.user_id = u.id
                JOIN folders f ON t.folder_id = f.id
                WHERE t.date BETWEEN ? AND ?
                ORDER BY t.date, u.name
            ");
            $stmt->execute([$startDate, $endDate]);
            $tasks = $stmt->fetchAll();
            echo json_encode($tasks);
            break;
            
        case 'save_task':
            $userId = $_POST['user_id'] ?? 0;
            $folderId = $_POST['folder_id'] ?? 0;
            $date = $_POST['date'] ?? '';
            $hours = $_POST['hours'] ?? 0;
            $comment = $_POST['comment'] ?? '';
            $taskId = $_POST['task_id'] ?? null;
            
            $pdo = getDB();
            
            if ($taskId) {
                $stmt = $pdo->prepare("UPDATE tasks SET user_id=?, folder_id=?, date=?, hours=?, comment=? WHERE id=?");
                $stmt->execute([$userId, $folderId, $date, $hours, $comment, $taskId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO tasks (user_id, folder_id, date, hours, comment) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$userId, $folderId, $date, $hours, $comment]);
                $taskId = $pdo->lastInsertId();
            }
            
            echo json_encode(['success' => true, 'id' => $taskId]);
            break;
            
        case 'delete_task':
            $id = $_POST['id'] ?? 0;
            $pdo = getDB();
            $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'validate_task':
            $id = $_POST['id'] ?? 0;
            $pdo = getDB();
            $stmt = $pdo->prepare("UPDATE tasks SET validated = TRUE WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;
            
        case 'export_by_user':
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('sunday this week'));
            
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT u.name as Membre, f.name as Dossier, 
                       SUM(t.hours) as Total_Heures,
                       GROUP_CONCAT(DISTINCT IF(t.validated, 'Validé', 'Non validé')) as Statut
                FROM tasks t
                JOIN users u ON t.user_id = u.id
                JOIN folders f ON t.folder_id = f.id
                WHERE t.date BETWEEN ? AND ?
                GROUP BY u.id, f.id
                ORDER BY u.name, f.name
            ");
            $stmt->execute([$startDate, $endDate]);
            $data = $stmt->fetchAll();
            echo json_encode($data);
            break;
            
        case 'export_by_folder':
            $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('monday this week'));
            $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('sunday this week'));
            
            $pdo = getDB();
            $stmt = $pdo->prepare("
                SELECT f.name as Dossier, u.name as Membre,
                       SUM(t.hours) as Total_Heures,
                       GROUP_CONCAT(DISTINCT IF(t.validated, 'Validé', 'Non validé')) as Statut
                FROM tasks t
                JOIN users u ON t.user_id = u.id
                JOIN folders f ON t.folder_id = f.id
                WHERE t.date BETWEEN ? AND ?
                GROUP BY f.id, u.id
                ORDER BY f.name, u.name
            ");
            $stmt->execute([$startDate, $endDate]);
            $data = $stmt->fetchAll();
            echo json_encode($data);
            break;
            
        default:
            echo json_encode(['error' => 'Action non reconnue']);
    }
} catch(Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>