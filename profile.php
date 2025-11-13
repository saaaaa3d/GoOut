<?php
session_start();
require 'db.php'; // Connexion

// Checki l-user o logout...
if (!isset($_SESSION['user'])) { header('Location: index.php'); exit(); }
if (isset($_GET['logout'])) { session_destroy(); unset($_SESSION['user']); header('Location: index.php'); exit(); }

// Jbed l-info dyal l-user...
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch();
if (!$user) { session_destroy(); header('Location: index.php'); exit(); }
$user_id = $user['id'];

// Kheddem l-formulaire dyal "Modifier"...
if (isset($_POST['save_profile'])) {
    $new_username = $_POST['username']; $new_location = $_POST['location']; $new_about = $_POST['about']; $new_profile_pic_path = $user['profile_picture']; $new_member_since = $_POST['member_since'];
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) { if (!empty($user['profile_picture']) && file_exists($user['profile_picture'])) { unlink($user['profile_picture']); } $target_dir = "uploads/"; $file_extension = strtolower(pathinfo($_FILES["profile_picture"]["name"], PATHINFO_EXTENSION)); $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']; if (in_array($file_extension, $allowed_extensions)) { $file_name = uniqid('profile_', true) . '.' . $file_extension; $target_file = $target_dir . $file_name; if (move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $target_file)) { $new_profile_pic_path = $target_file; } } }
    $sql = "UPDATE users SET username = ?, location = ?, about = ?, profile_picture = ?, member_since = ? WHERE id = ?"; $stmtUpdate = $pdo->prepare($sql); $stmtUpdate->execute([$new_username, $new_location, $new_about, $new_profile_pic_path, $new_member_since, $user_id]);
    if ($new_username !== $_SESSION['user']) { $_SESSION['user'] = $new_username; } header("Location: profile.php?updated=true"); exit();
}

// === JBED LES DÉTAILS DYAL LES ACTIVITÉS REJOINTES (DATABASE) ===
$joined_activities_details = [];
try {
    $sqlJoined = "SELECT a.id, a.title, a.activity_date, a.image_path, a.category, a.location_name
                  FROM activities a
                  JOIN user_activities ua ON a.id = ua.activity_id
                  WHERE ua.user_id = ?
                  ORDER BY a.activity_date DESC";
    $stmtJoinedDetails = $pdo->prepare($sqlJoined);
    $stmtJoinedDetails->execute([$user_id]);
    $joined_activities_details = $stmtJoinedDetails->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) { echo "Erreur activités rejointes: " . $e->getMessage(); }

// === JBED LES ACTIVITÉS STATIQUES REJOINTES (MN SESSION) ===
$joined_static_activities = isset($_SESSION['joined_static_activities']) ? $_SESSION['joined_static_activities'] : [];

// Définir les détails des activités statiques
$static_activities_details = [
    'static-toubkal' => [
        'title' => 'Ascension du Jbel Toubkal',
        'category' => 'randonnée',
        'date' => '2025-11-15',
        'location' => 'Jbel Toubkal, Maroc',
        'image' => 'media/sommet-mont-toubkal.jpg'
    ],
    'static-michlifen' => [
        'title' => 'Journée Ski à Michlifen',
        'category' => 'sport', 
        'date' => '2025-12-20',
        'location' => 'Michlifen, Ifrane',
        'image' => 'media/ve1e8dykfinqfite6etv.jpg'
    ],
    'static-akchour' => [
        'title' => 'Cascades d\'Akchour',
        'category' => 'randonnée',
        'date' => '2025-11-08', 
        'location' => 'Akchour, Chefchaouen',
        'image' => 'media/caption.jpg'
    ],
    'static-benhaddou' => [
        'title' => 'Visite Ksar Aït Benhaddou',
        'category' => 'culture',
        'date' => '2025-11-22',
        'location' => 'Aït Benhaddou, Ouarzazate',
        'image' => 'media/30.jpg'
    ],
    'static-essaouira' => [
        'title' => 'Initiation Kitesurf',
        'category' => 'sport',
        'date' => '2025-11-01',
        'location' => 'Plage d\'Essaouira',
        'image' => 'media/kitesurf-rodrigues.jpg'
    ],
    'static-takerkoust' => [
        'title' => 'Pique-nique Lac Takerkoust', 
        'category' => 'pique-nique',
        'date' => '2025-11-02',
        'location' => 'Lalla Takerkoust, Marrakech',
        'image' => 'media/idees-pique-nique.jpg'
    ]
];

// N3awdo njebdo l-info dyal l-user (mn be3d l-update)
$stmtUser = $pdo->prepare("SELECT * FROM users WHERE username = ?");
$stmtUser->execute([$_SESSION['user']]);
$user = $stmtUser->fetch();
if (!$user) { session_destroy(); header('Location: index.php'); exit(); }
$username_display = $user['username'];

// Calculer le total des activités rejointes
$total_joined_activities = count($joined_activities_details) + count($joined_static_activities);

?>