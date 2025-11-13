<?php
session_start();
require 'db.php'; // Bach nt-connectaw l-database

// Checki wach logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// L-User li ghay-organiser l-activité
$organizer_username = $_SESSION['user'];

// Khddem l-formulaire ila t-clika 3la submit
if (isset($_POST['submit_activity'])) {
    // Njbdo l-data mn l-form
    $title = $_POST['title'];
    $description = $_POST['description'];
    $date = $_POST['date'];
    $location_name = $_POST['location_name'];
    $location_coords = $_POST['location_coords']; // Coordonnées
    $max_participants = $_POST['max_participants'];
    $category = $_POST['category']; // Randonnée, Sport, etc.
    $image_path = null; // B défaut, bla tsswira

    // Khedma dyal Upload dyal Tsswira (Nafs l-principe dyal l-profile)
    if (isset($_FILES['activity_image']) && $_FILES['activity_image']['error'] == 0) {
        $target_dir = "uploads/"; // NAFS dossier dyal tssawer
        $file_extension = strtolower(pathinfo($_FILES["activity_image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            $file_name = uniqid('activity_', true) . '.' . $file_extension;
            $target_file = $target_dir . $file_name;

            if (move_uploaded_file($_FILES["activity_image"]["tmp_name"], $target_file)) {
                $image_path = $target_file; // L-path dyal tsswira l-jdida
            } else {
                echo "Erreur uploading image.";
            }
        } else {
            echo "Format d'image non supporté.";
        }
    }

    // --- HNA KHASSNA NZIDO L-ACTIVITÉ F DATABASE ---
    // !!! MOLAHADA: Had l-code kayfترض أن 3ndek table jdida smitha "activities" !!!
    // !!! Khassk t-creerha f phpMyAdmin (ghadi n3tik l-code SQL dyalha) !!!

    try {
        $sql = "INSERT INTO activities (title, description, activity_date, location_name, location_coords, max_participants, category, image_path, organizer_username) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
            $organizer_username
        ]);

        // Mli tzad l-activité, rje3 l-project.php
        header("Location: project.php"); // Ghayrddk l-page dyal les activités 
        exit();

    } catch (PDOException $e) {
        // Imken ytra chi mochkil f database
        echo "Erreur d'enregistrement: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer une Activité - GoOut</title>
    <link href="bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-green: #11998e;
            --secondary-green: #38ef7d;
            --hero-gradient: linear-gradient(90deg, var(--primary-green), var(--secondary-green));
            --text-dark: #212529;
            --secondary-color: #6c757d;
            --bg-light: #f8f9fa;
        }
        body { background-color: var(--bg-light); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .navbar { background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 1rem 2rem; display: flex; justify-content: space-between; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color: var(--text-dark); text-decoration: none; }
        .navbar-brand i { color: var(--primary-green); font-size: 1.8rem; vertical-align: middle; margin-right: 5px; }
        .user-menu a { color: var(--secondary-color); font-size: 1.5rem; margin-left: 15px; text-decoration: none; }
        .user-menu a:hover { color: var(--text-dark); }
        .form-container { max-width: 700px; margin: 40px auto; background-color: white; padding: 30px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .form-container h1 { font-weight: bold; color: var(--text-dark); margin-bottom: 25px; text-align: center; }
        .btn-submit-activity { background: var(--hero-gradient); color: white; border: none; font-weight: 600; padding: 10px 25px; }
        .btn-submit-activity:hover { filter: brightness(1.1); color: white; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="navbar-brand" href="project.php">
            <i class="bi bi-compass"></i> GoOut
        </a>
        <div class="user-menu">
            <a href="profile.php" title="<?php echo htmlspecialchars($organizer_username); ?>">
                <i class="bi bi-person-circle"></i>
            </a>
            <a href="project.php?logout=true" title="Déconnexion">
                <i class="bi bi-box-arrow-right"></i>
            </a>
        </div>
    </nav>

    <div class="form-container">
        <h1><i class="bi bi-plus-circle-fill" style="color: var(--primary-green);"></i> Créer une nouvelle activité</h1>

        <form method="POST" action="create_activity.php" enctype="multipart/form-data">
            
            <div class="mb-3">
                <label for="title" class="form-label">Titre de l'activité</label>
                <input type="text" class="form-control" id="title" name="title" required>
            </div>

            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="date" class="form-label">Date et Heure</label>
                    <input type="datetime-local" class="form-control" id="date" name="date" required>
                </div>
                <div class="col-md-6">
                    <label for="category" class="form-label">Catégorie</label>
                    <select class="form-select" id="category" name="category" required>
                        <option value="">Choisir...</option>
                        <option value="randonnée">Randonnée</option>
                        <option value="sport">Sport</option>
                        <option value="culture">Culture</option>
                        <option value="pique-nique">Pique-nique</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                 <div class="col-md-6">
                    <label for="location_name" class="form-label">Lieu (ex: Forêt Maamora, Rabat)</label>
                    <input type="text" class="form-control" id="location_name" name="location_name" required>
                </div>
                 <div class="col-md-6">
                    <label for="location_coords" class="form-label">Coordonnées GPS (Optionnel)</label>
                    <input type="text" class="form-control" id="location_coords" name="location_coords" placeholder="ex: 34.020, -6.842">
                </div>
            </div>

            <div class="mb-3">
                <label for="max_participants" class="form-label">Nombre maximum de participants</label>
                <input type="number" class="form-control" id="max_participants" name="max_participants" min="1" required>
            </div>

            <div class="d-grid gap-2 mt-4">
                 <button type="submit" name="submit_activity" class="btn btn-submit-activity">Publier l'activité</button>
            </div>

        </form>
    </div>

    <script src="bootstrap.bundle.min.js"></script>
</body>
</html>