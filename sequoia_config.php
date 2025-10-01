<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'nombddtest');
define('DB_USER', 'testlogin');
define('DB_PASS', 'testpwd');

// Connexion à la base de données
function getDB() {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
        return $pdo;
    } catch(PDOException $e) {
        die(json_encode(['error' => 'Erreur de connexion: ' . $e->getMessage()]));
    }
}

// Initialisation de la base de données
function initDB() {
    $pdo = getDB();
    
    // Table des utilisateurs
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(100) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Table des dossiers
    $pdo->exec("CREATE TABLE IF NOT EXISTS folders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        color VARCHAR(7) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Table des tâches/heures
    $pdo->exec("CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        folder_id INT NOT NULL,
        date DATE NOT NULL,
        hours DECIMAL(4,2) NOT NULL,
        comment TEXT,
        validated BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (folder_id) REFERENCES folders(id) ON DELETE CASCADE
    )");
    
    // Créer un utilisateur admin par défaut
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, password, name) VALUES (?, ?, ?)");
    $stmt->execute(['admin', password_hash('admin123', PASSWORD_DEFAULT), 'Administrateur']);
}

// Initialiser la DB au premier appel
initDB();
?>