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

// 2. Jbed l-info dyal l-activité mn database b ID dyalha
try {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    // Ila malqach l-activité b dak l-ID
    if (!$activity) {
        echo "L'activité (" . $activity_id . ") ma kaynach.";
        exit();
    }

    // 3. VERIFI wach had l-user howa li saybha
    if ($activity['organizer_username'] !== $current_username) {
        echo "Ma 3ndekch l-7eq tbeddel had l'activité.";
        // Tqder trddo l project.php ila bghiti: header('Location: project.php');
        exit();
    }

} catch (PDOException $e) {
    echo "Erreur f jbedan l'activité: " . $e->getMessage();
    exit();
}


// 4. KHDDEM L-FORMULAIRE ILA T-POSTA (SAVE CHANGES)
if (isset($_POST['update_activity'])) {
    // Njbdo l-data jdida mn l-form
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $location_name = $_POST['location_name'];
    $location_coords = $_POST['location_coords'];
    $max_participants = $_POST['max_participants'];
    $category = $_POST['category'];

    // Khlli tsswira l-qdima b défaut
    $image_path = $activity['image_path']; // L-path l-qdim

    // Checki wach upload tsswira jdida
    if (isset($_FILES['activity_image']) && $_FILES['activity_image']['error'] == 0) {

        // Im7i tsswira l-qdima ila kant o machi khawya
        if (!empty($activity['image_path']) && file_exists($activity['image_path'])) {
            unlink($activity['image_path']);
        }

        $target_dir = "uploads/";
        $file_extension = strtolower(pathinfo($_FILES["activity_image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid('activity_', true) . '.' . $file_extension;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["activity_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file; // L-path jdid
            } else { echo "Erreur uploading image."; }
        } else { echo "Format d'image non supporté."; }
    }

    // Dir l-UPDATE f database
    try {
        $sql = "UPDATE activities SET
                    title = ?,
                    description = ?,
                    activity_date = ?,
                    location_name = ?,
                    location_coords = ?,
                    max_participants = ?,
                    category = ?,
                    image_path = ?
                WHERE id = ?"; // L-Condition hiya l-ID
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $title,
            $description,
            $date,
            $location_name,
            $location_coords,
            $max_participants,
            $category,
            $image_path,
            $activity_id // L-ID dyal l-activité li kanbeddlo
        ]);

        // Mli t-update, rje3 l-project.php
        header("Location: project.php?updated=true"); // Zedt ?updated=true
        exit();

    } catch (PDOException $e) {
        echo "Erreur d'enregistrement: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier l'Activité - GoOut</title>
    <link href="bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-icons.min.css">
    <style>
        /* Nafs style dyal create_activity.php */
        :root { /* ... colors ... */ --primary-green: #11998e; --secondary-green: #38ef7d; --hero-gradient: linear-gradient(90deg, var(--primary-green), var(--secondary-green)); --text-dark: #212529; --secondary-color: #6c757d; --bg-light: #f8f9fa; }
        body { background-color: var(--bg-light); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .navbar { /* ... styles ... */ background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 1rem 2rem; display: flex; justify-content: space-between; }
        .navbar-brand { /* ... styles ... */ font-weight: bold; font-size: 1.5rem; color: var(--text-dark); text-decoration: none; }
        .navbar-brand i { /* ... styles ... */ color: var(--primary-green); font-size: 1.8rem; vertical-align: middle; margin-right: 5px; }
        .user-menu a { /* ... styles ... */ color: var(--secondary-color); font-size: 1.5rem; margin-left: 15px; text-decoration: none; }
        .user-menu a:hover { /* ... styles ... */ color: var(--text-dark); }
        .form-container { max-width: 700px; margin: 40px auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-container h1 { font-weight: bold; color: var(--text-dark); margin-bottom: 25px; text-align: center; }
        .btn-submit-activity { background: var(--hero-gradient); color: white; border: none; font-weight: 600; padding: 10px 25px; }
        .btn-submit-activity:hover { filter: brightness(1.1); color: white; }
        /* Style l-tsswira l-7alia */
        .current-image { max-width: 150px; max-height: 100px; margin-top: 10px; border-radius: 5px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="navbar-brand" href="project.php">
            <i class="bi bi-compass"></i> GoOut
        </a>
        <div class="user-menu">
            <a href="profile.php" title="<?php echo htmlspecialchars($current_username); ?>">
                <i class="bi bi-person-circle"></i>
            </a>
            <a href="project.php?logout=true" title="Déconnexion">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>

    <div class="form-container">
        <h1><i class="bi bi-pencil-fill" style="color: var(--primary-green);"></i> Modifier l'activité</h1>

        <form method="POST" action="edit_activity.php?id=<?php echo $activity_id; ?>" enctype="multipart/form-data">

            <div class="mb-3">
                <label for="title" class="form-label">Titre de l'activité</label>
                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($activity['title']); ?>" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required><?php echo htmlspecialchars($activity['description']); ?></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date" class="form-label">Date et Heure</label>
                    <?php $formatted_date = date('Y-m-d\TH:i', strtotime($activity['activity_date'])); ?>
                    <input type="datetime-local" class="form-control" id="date" name="date" value="<?php echo $formatted_date; ?>" required>
                </div>
                <div class="col-md-6">
                    <label for="category" class="form-label">Catégorie</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Choisir...</option>
                        <option value="randonnée" <?php echo ($activity['category'] == 'randonnée') ? 'selected' : ''; ?>>Randonnée</option>
                        <option value="sport" <?php echo ($activity['category'] == 'sport') ? 'selected' : ''; ?>>Sport</option>
                        <option value="culture" <?php echo ($activity['category'] == 'culture') ? 'selected' : ''; ?>>Culture</option>
                        <option value="pique-nique" <?php echo ($activity['category'] == 'pique-nique') ? 'selected' : ''; ?>>Pique-nique</option>
                        <option value="autre" <?php echo ($activity['category'] == 'autre') ? 'selected' : ''; ?>>Autre</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                 <div class="col-md-6">
                    <label for="location_name" class="form-label">Lieu</label>
                    <input type="text" class="form-control" id="location_name" name="location_name" value="<?php echo htmlspecialchars($activity['location_name']); ?>" required>
                </div>
                 <div class="col-md-6">
                    <label for="location_coords" class="form-label">Coordonnées GPS (Optionnel)</label>
                    <input type="text" class="form-control" id="location_coords" name="location_coords" value="<?php echo htmlspecialchars($activity['location_coords']); ?>" placeholder="ex: 34.020, -6.842">
                </div>
            </div>

            <div class="mb-3">
                <label for="max_participants" class="form-label">Nombre maximum de participants</label>
                <input type="number" class="form-control" id="max_participants" name="max_participants" min="1" value="<?php echo htmlspecialchars($activity['max_participants']); ?>" required>
            </div>

            <div class="d-grid gap-2 mt-4">
                 <button type="submit" name="update_activity" class="btn btn-submit-activity">Sauvegarder les modifications</button>
                 <a href="project.php" class="btn btn-secondary">Annuler</a> </div>

        </form>
    </div>

    <script src="bootstrap.bundle.min.js"></script>
</body>
</html>