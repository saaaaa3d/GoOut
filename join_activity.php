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
    header('Location: project.php?join_error=invalid_id');
    exit();
}
$activity_id = $_GET['id'];

// 4. 7awel tzid l-jointure f table user_activities
try {
    // INSERT IGNORE: Ila lqa user_id o activity_id déja kaynin, ghay tjahal l-insertion
    $sql = "INSERT IGNORE INTO user_activities (user_id, activity_id) VALUES (?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id, $activity_id]);

    // 5. Rje3 l project.php
    header("Location: project.php?joined=" . $activity_id);
    exit();

} catch (PDOException $e) {
    header("Location: project.php?join_error=" . urlencode($e->getMessage()));
    exit();
}
?>