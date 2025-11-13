<?php
session_start();
require 'db.php'; // Connexion l-database

// Checki wach logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
$current_username = $_SESSION['user'];

// 1. Jbed l-ID dyal l-activité mn l-URL (?id=...)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID dyal l'activité ma s7i7ch.";
    exit();
}
$activity_id = $_GET['id'];

// 2. Jbed l-info dyal l-activité (bach n3rfo wach howa molaha o fin kayna tsswira)
try {
    $stmt = $pdo->prepare("SELECT organizer_username, image_path FROM activities WHERE id = ?");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ila malqach l-activité b dak l-ID
    if (!$activity) {
        echo "L'activité (" . $activity_id . ") ma kaynach.";
        exit();
    }

    // 3. VERIFI wach had l-user howa li saybha
    if ($activity['organizer_username'] !== $current_username) {
        echo "Ma 3ndekch l-7eq tm7i had l'activité.";
        // Tqder trddo l project.php: header('Location: project.php');
        exit();
    }

    // 4. Im7i mn Database
    $deleteStmt = $pdo->prepare("DELETE FROM activities WHERE id = ?");
    $deleteStmt->execute([$activity_id]);

    // 5. Im7i tsswira mn dossier "uploads" ila kant
    if (!empty($activity['image_path']) && file_exists($activity['image_path'])) {
        unlink($activity['image_path']);
    }

    // 6. Rje3 l project.php
    header("Location: project.php?deleted=true"); // Zedt ?deleted=true
    exit();

} catch (PDOException $e) {
    echo "Erreur f tm7i l'activité: " . $e->getMessage();
    exit();
}
?>