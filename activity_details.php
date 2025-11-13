<?php
session_start();
require 'db.php';

// 1. Checki wach logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
$username = $_SESSION['user'];

// 2. Jbed l-ID dyal l-user (ghanhbtajoh l-comments o rating)
$stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
$stmtUser->execute([$username]);
$user = $stmtUser->fetch();
$user_id = $user['id'];

// 3. Jbed l-ID dyal l-activité mn l-URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "ID dyal l'activité ma s7i7ch.";
    exit();
}
$activity_id = $_GET['id'];

// 4. Jbed l-info dyal l-activité b ID dyalha
try {
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE id = ?");
    $stmt->execute([$activity_id]);
    $activity = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$activity) {
        echo "L'activité ma kaynach.";
        exit();
    }
    
    // Jbed les commentaires dyal had l-activité
    $stmtComments = $pdo->prepare("SELECT * FROM comments WHERE activity_id = ? ORDER BY created_at DESC");
    $stmtComments->execute([$activity_id]);
    $comments = $stmtComments->fetchAll(PDO::FETCH_ASSOC);
    
    // Jbed l-Rating (ch7al mn etoile)
    $stmtRating = $pdo->prepare("SELECT AVG(rating) as avg_rating, COUNT(rating) as total_ratings FROM ratings WHERE activity_id = ?");
    $stmtRating->execute([$activity_id]);
    $rating_stats = $stmtRating->fetch();
    
    // Checki wach l-user m-joini (bach nbyyno lih l-form)
    $stmtCheckJoined = $pdo->prepare("SELECT id FROM user_activities WHERE user_id = ? AND activity_id = ?");
    $stmtCheckJoined->execute([$user_id, $activity_id]);
    $is_joined = $stmtCheckJoined->fetch() ? true : false;
    
    // Checki wach l-user déja 3ta rating
    $stmtCheckRating = $pdo->prepare("SELECT rating FROM ratings WHERE user_id = ? AND activity_id = ?");
    $stmtCheckRating->execute([$user_id, $activity_id]);
    $user_rating = $stmtCheckRating->fetchColumn(); // Ghayjib ghir l-rating (ex: 4) wla false

} catch (PDOException $e) {
    echo "Erreur f jbedan l'activité: " . $e->getMessage();
    exit();
}

// 5. Khddem l-formulaire dyal Commentaire
if (isset($_POST['submit_comment']) && $is_joined) { // Khass ykoun m-joini
    $comment_text = $_POST['comment_text'];
    if (!empty($comment_text)) {
        try {
            $sql = "INSERT INTO comments (activity_id, user_id, username, comment_text) VALUES (?, ?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$activity_id, $user_id, $username, $comment_text]);
            
            // 3awed charge l-page bach yban l-comment jdid
            header("Location: activity_details.php?id=" . $activity_id);
            exit();
        } catch (PDOException $e) {
            echo "Erreur f zyadit l-commentaire: " . $e->getMessage();
        }
    }
}

// 6. Khddem l-formulaire dyal Rating (Étoiles)
if (isset($_POST['submit_rating']) && $is_joined) { // Khass ykoun m-joini
    $rating_value = $_POST['rating'];
    if ($rating_value >= 1 && $rating_value <= 5) {
         try {
            // REPLACE INTO: Ila déja 3ta rating, ghaybdlo. Ila yallah ghay3tih, ghayzido.
            $sql = "REPLACE INTO ratings (activity_id, user_id, rating) VALUES (?, ?, ?)";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$activity_id, $user_id, $rating_value]);
            
            header("Location: activity_details.php?id=" . $activity_id);
            exit();
        } catch (PDOException $e) {
            echo "Erreur f zyadit l-rating: " . $e->getMessage();
        }
    }
}

// Formatage dyal l-date dyal l-activité
$activity_date_obj = new DateTime($activity['activity_date']);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Détails: <?php echo htmlspecialchars($activity['title']); ?></title>
    <link href="bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-icons.min.css">
    
    <style>
        /* CSS 3adi */
        :root {
             --primary-green: #11998e; --secondary-color: #6c757d;
             --bg-light: #f8f9fa; --text-dark: #212529; --warning-color: #ffc107;
             --hero-gradient: linear-gradient(90deg, var(--primary-green), #38ef7d);
        }
        body { background-color: var(--bg-light); font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; }
        .navbar { background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 1rem 2rem; display: flex; justify-content: space-between; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color: var(--text-dark); text-decoration: none; }
        .navbar-brand i { color: var(--primary-green); font-size: 1.8rem; vertical-align: middle; margin-right: 5px; }
        .user-menu a { color: var(--secondary-color); font-size: 1.5rem; margin-left: 15px; text-decoration: none; }
        .user-menu a:hover { color: var(--text-dark); }
        .user-menu a.active { color: var(--primary-green); }
        
        .details-container { max-width: 900px; margin: 40px auto; }
        .activity-header-image {
            width: 100%;
            height: 350px;
            object-fit: cover;
            border-radius: 10px;
            background-color: #eee;
        }
        .activity-content {
            background-color: white;
            padding: 30px;
            border-radius: 10px;
            margin-top: 20px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .activity-content h1 { font-weight: bold; color: var(--text-dark); }
        .organizer-info { font-weight: 500; color: var(--secondary-color); margin-bottom: 20px; }
        .activity-info { font-size: 1.1rem; margin-bottom: 8px; }
        .activity-info i { color: var(--primary-green); margin-right: 10px; width: 20px; }
        .btn-join-details { background: var(--hero-gradient); color: white; border: none; font-weight: 600; padding: 10px 25px; }
        .btn-join-details:hover { filter: brightness(1.1); color: white; }
        .btn-joined-details { background-color: var(--success-color); color: white; border: none; font-weight: 600; padding: 10px 25px; cursor: default; }

        /* Style dyal l-Étoiles */
        .rating-form { margin-top: 20px; padding: 20px; background-color: #f8f9fa; border-radius: 8px; }
        .star-rating { display: flex; flex-direction: row-reverse; /* Bach tkhdem b hover */ justify-content: center; }
        .star-rating input[type="radio"] { display: none; }
        .star-rating label {
            font-size: 2.5rem;
            color: #ddd;
            cursor: pointer;
            transition: color 0.2s ease;
        }
        .star-rating input[type="radio"]:checked ~ label, /* Kaylwwen li morah (b l-reverse) */
        .star-rating label:hover,
        .star-rating label:hover ~ label {
            color: var(--warning-color);
        }
        
        /* Style dyal l-Commentaires */
        .comments-section { margin-top: 30px; }
        .comment-form { margin-top: 20px; }
        .comment-box { background-color: white; border-radius: 8px; padding: 15px; margin-bottom: 15px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .comment-box strong { color: var(--primary-green); }
        .comment-box small { color: var(--secondary-color); font-size: 0.8rem; }
        .comment-box p { margin-top: 5px; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="navbar-brand" href="project.php"> <i class="bi bi-compass"></i> GoOut </a>
        <div class="user-menu">
            <a href="profile.php" title="<?php echo htmlspecialchars($username); ?>"> <i class="bi bi-person-circle"></i> </a>
            <a href="project.php?logout=true" title="Déconnexion"> <i class="bi bi-box-arrow-right"></i> </a>
        </div>
    </nav>

    <div class="details-container">
        
        <?php if (!empty($activity['image_path']) && file_exists($activity['image_path'])): ?>
            <img src="<?php echo htmlspecialchars($activity['image_path']); ?>" alt="<?php echo htmlspecialchars($activity['title']); ?>" class="activity-header-image">
        <?php else: ?>
             <img src="placeholder.jpg" alt="Image par défaut" class="activity-header-image"> <?php endif; ?>

        <div class="activity-content">
            <h1><?php echo htmlspecialchars($activity['title']); ?></h1>
            <p class="organizer-info">Organisé par <?php echo htmlspecialchars($activity['organizer_username']); ?></p>
            <hr>
            
            <div class="row">
                <div class="col-md-6">
                    <p class="activity-info"><i class="bi bi-calendar-event-fill"></i> <?php echo $activity_date_obj->format('d M Y \à H:i'); ?></p>
                    <p class="activity-info"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($activity['location_name']); ?></p>
                </div>
                <div class="col-md-6">
                    <p class="activity-info"><i class="bi bi-people-fill"></i> 0 / <?php echo htmlspecialchars($activity['max_participants']); ?> participants</p>
                    <p class="activity-info"><i class="bi bi-tag-fill"></i> <?php echo htmlspecialchars(ucfirst($activity['category'])); ?></p>
                </div>
            </div>
            
            <h4 class="mt-4">Description</h4>
            <p><?php echo nl2br(htmlspecialchars($activity['description'])); ?></p>
            
            <?php if (!$is_joined): ?>
                <a href="join_activity.php?id=<?php echo $activity_id; ?>" class="btn btn-join-details mt-3">Rejoindre cette activité</a>
            <?php else: ?>
                 <button class="btn btn-joined-details mt-3" disabled><i class="bi bi-check-circle-fill"></i> Vous avez rejoint</button>
            <?php endif; ?>
        </div>

        <?php if ($is_joined): ?>
            <div class="activity-content comments-section">
                
                <h4>Évaluer cette activité</h4>
                <?php if ($user_rating): ?>
                    <p>Vous avez déjà voté: <?php echo $user_rating; ?> <i class="bi bi-star-fill" style="color: var(--warning-color);"></i></p>
                <?php endif; ?>
                
                <form method="POST" action="activity_details.php?id=<?php echo $activity_id; ?>" class="rating-form">
                    <div class="star-rating">
                        <input type="radio" id="star5" name="rating" value="5" <?php echo ($user_rating == 5) ? 'checked' : ''; ?>><label for="star5" title="5 stars">&#9733;</label>
                        <input type="radio" id="star4" name="rating" value="4" <?php echo ($user_rating == 4) ? 'checked' : ''; ?>><label for="star4" title="4 stars">&#9733;</label>
                        <input type="radio" id="star3" name="rating" value="3" <?php echo ($user_rating == 3) ? 'checked' : ''; ?>><label for="star3" title="3 stars">&#9733;</label>
                        <input type="radio" id="star2" name="rating" value="2" <?php echo ($user_rating == 2) ? 'checked' : ''; ?>><label for="star2" title="2 stars">&#9733;</label>
                        <input type="radio" id="star1" name="rating" value="1" <?php echo ($user_rating == 1) ? 'checked' : ''; ?>><label for="star1" title="1 star">&#9733;</label>
                    </div>
                    <button type="submit" name="submit_rating" class="btn btn-primary btn-sm mt-2">Envoyer l'évaluation</button>
                </form>
                
                <hr class="my-4">

                <h4>Commentaires (<?php echo count($comments); ?>)</h4>
                
                <form method="POST" action="activity_details.php?id=<?php echo $activity_id; ?>" class="comment-form">
                    <div class="mb-3">
                        <label for="comment_text" class="form-label">Ajouter un commentaire</label>
                        <textarea class="form-control" id="comment_text" name="comment_text" rows="3" required></textarea>
                    </div>
                    <button type="submit" name="submit_comment" class="btn btn-primary">Envoyer</button>
                </form>
                
                <div class="mt-4">
                    <?php if (empty($comments)): ?>
                        <p>Aucun commentaire pour le moment.</p>
                    <?php else: ?>
                        <?php foreach ($comments as $comment): ?>
                            <div class="comment-box">
                                <strong><?php echo htmlspecialchars($comment['username']); ?></strong>
                                <small class="float-end"><?php echo (new DateTime($comment['created_at']))->format('d M Y'); ?></small>
                                <p><?php echo nl2br(htmlspecialchars($comment['comment_text'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

            </div>
        <?php else: ?>
            <div class="activity-content comments-section">
                <h4>Commentaires</h4>
                <p>Vous devez <a href="join_activity.php?id=<?php echo $activity_id; ?>">rejoindre l'activité</a> pour pouvoir commenter ou évaluer.</p>
             </div>
        <?php endif; ?>
        
    </div>

    <script src="bootstrap.bundle.min.js"></script>
</body>
</html>