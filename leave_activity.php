<?php
session_start();
require 'db.php'; // Connexion l-database

// 1. Checki wach logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// 2. Jbed ID dyal user mn session
$stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch();
if (!$user) {
    session_destroy();
    header('Location: index.php');
    exit();
}
$user_id = $user['id'];

// 3. Jbed ID dyal activité mn l-URL (?id=...)
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: profile.php?leave_error=invalid_id');
    exit();
}
$activity_id = $_GET['id'];

// 4. Im7i l-jointure mn table user_activities
try {
    $sql = "DELETE FROM user_activities WHERE user_id = ? AND activity_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $activity_id]);

    // 5. Rje3 l profile.php
    if ($stmt->rowCount() > 0) {
        header("Location: profile.php?left=" . $activity_id);
    } else {
        header("Location: profile.php?leave_error=not_joined");
    }
    exit();

} catch (PDOException $e) {
    header("Location: profile.php?leave_error=" . urlencode($e->getMessage()));
    exit();
}
?>