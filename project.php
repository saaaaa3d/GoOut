<?php
session_start();
require 'db.php'; // T2ekked mn path

// Checki wach logged in
if (!isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}

// L-Logout
if (isset($_GET['logout'])) {
    session_destroy();
    unset($_SESSION['user']);
    header('Location: index.php');
    exit();
}

$username = $_SESSION['user']; // Smit l-user li logged in

// --- JBED L-ID DYAL L-USER L-7ALI ---
$user_id = null; // B défaut
try {
    $stmtUser = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmtUser->execute([$username]);
    $user = $stmtUser->fetch();
    if ($user) {
        $user_id = $user['id']; // Khdina l-ID dyalo
    } else {
        session_destroy();
        header('Location: index.php?error=user_not_found');
        exit();
    }
} catch (PDOException $e) {
     echo "Erreur f jbedan user ID: " . $e->getMessage();
}


// --- JBED LES ACTIVITÉS MN DATABASE ---
try {
    $stmtActivities = $pdo->query("SELECT * FROM activities ORDER BY activity_date DESC");
    $db_activities = $stmtActivities->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $db_activities = [];
    echo "Erreur f jbedan les activités: " . $e->getMessage();
}

// --- JBED L-IDs DYAL LES ACTIVITÉS LI M-JOINIHOM (MN DATABASE) ---
$joined_activity_ids_db = []; // Array dyal database khawi f l-wel
if ($user_id) { // Njbdo ghir ila lqina l-ID dyal l-user
    try {
        $stmtJoined = $pdo->prepare("SELECT activity_id FROM user_activities WHERE user_id = ?");
        $stmtJoined->execute([$user_id]);
        $joined_activity_ids_db = $stmtJoined->fetchAll(PDO::FETCH_COLUMN); // Kayjib ghir les IDs
    } catch (PDOException $e) {
        echo "Erreur f jbedan activités rejointes: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ton Aventure - GoOut</title>
    
    <link href="bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    <style>
        /* CSS Kaml kif kan (b style dyal buttons edit/delete) */
        :root {
            --primary-color: #0d6efd;
            --secondary-color: #6c757d;
            --success-color: #198754;
            --text-dark: #212529;
            --text-light: #f8f9fa;
            --bg-light: #f8f9fa;
            --primary-green: #11998e;
            --secondary-green: #38ef7d;
            --hero-gradient: linear-gradient(90deg, var(--primary-green), var(--secondary-green));
            --danger-color: #dc3545;
        }
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background-color: var(--bg-light); }
        .navbar { background-color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.05); padding: 1rem 2rem; display: flex; justify-content: space-between; }
        .navbar-brand { font-weight: bold; font-size: 1.5rem; color: var(--text-dark); text-decoration: none; }
        .navbar-brand i { color: var(--primary-green); font-size: 1.8rem; vertical-align: middle; margin-right: 5px; }
        .search-bar { position: relative; width: 300px; }
        .search-bar input { border-radius: 20px; border: 1px solid #e0e0e0; padding-left: 35px; }
        .search-bar i { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--secondary-color); }
        .user-menu a { color: var(--secondary-color); font-size: 1.5rem; margin-left: 15px; text-decoration: none; transition: color 0.2s ease; }
        .user-menu a:hover { color: var(--text-dark); }
        .user-menu a.active { color: var(--primary-green); }
        .hero-section { background: var(--hero-gradient); color: white; padding: 60px 20px; text-align: center; }
        .hero-section h1 { font-weight: bold; font-size: 2.5rem; }
        .hero-section p { font-size: 1.1rem; opacity: 0.9; }
        .hero-stats { display: flex; justify-content: center; gap: 40px; margin-top: 30px; }
        .stat-item { font-size: 1.2rem; }
        .stat-item span { display: block; font-size: 2rem; font-weight: bold; }
        .content-area { max-width: 1200px; margin: -40px auto 40px auto; background-color: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); padding: 30px; position: relative; z-index: 2; }
        .filter-bar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; flex-wrap: wrap; }
        .filter-title { font-size: 1.1rem; font-weight: 600; color: var(--text-dark); }
        .btn-create { background: var(--hero-gradient); color: white; border-radius: 20px; font-weight: 500; padding: 8px 20px; border: none; }
        .btn-create:hover { color: white; filter: brightness(1.1); }
        .filter-tags { margin-top: 20px; display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }
        .filter-tags .btn { border-radius: 20px; border: 1px solid #ddd; background-color: white; color: var(--secondary-color); height: 38px; }
        .filter-tags .btn.active, .filter-tags .btn:hover { background: var(--hero-gradient); color: white; border-color: var(--primary-green); }
        .filter-select { border-radius: 20px; border: 1px solid #ddd; background-color: white; color: var(--secondary-color); padding: 0 10px; height: 38px; -webkit-appearance: none; -moz-appearance: none; appearance: none; background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e"); background-repeat: no-repeat; background-position: right 0.75rem center; background-size: 16px 12px; }
        .filter-select:focus { outline: none; border-color: var(--primary-green); box-shadow: 0 0 0 3px rgba(17, 153, 142, 0.2); }
        #reset-filter-btn { border-radius: 50%; width: 38px; padding: 0; margin-left: 5px; background-color: #f8f9fa; }
        .card-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 25px; }
        .activity-card { border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 5px rgba(0,0,0,0.05); transition: opacity 0.3s ease-out, transform 0.3s ease-out, height 0.3s ease-out 0.1s, padding 0.3s ease-out 0.1s, margin 0.3s ease-out 0.1s, border 0.3s ease-out 0.1s; position: relative; }
        .activity-card.filtered-out { opacity: 0; transform: scale(0.8); height: 0; padding: 0; margin: 0; border-width: 0; pointer-events: none; }
        .activity-card:hover { transform: translateY(-5px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .card-actions { position: absolute; top: 10px; right: 10px; background-color: rgba(255, 255, 255, 0.8); backdrop-filter: blur(4px); border-radius: 5px; padding: 5px; display: flex; gap: 5px; opacity: 0; transition: opacity 0.3s ease; z-index: 10; pointer-events: none; }
        .activity-card:hover .card-actions { opacity: 1; pointer-events: auto; }
        .card-actions .btn-action { color: var(--text-dark); background: none; border: none; padding: 5px; font-size: 1rem; line-height: 1; }
        .card-actions .btn-action:hover { color: var(--primary-green); }
        .card-actions .btn-delete:hover { color: var(--danger-color); }
        .card-tag { position: absolute; top: 10px; right: 10px; z-index: 5; transition: right 0.3s ease; background-color: rgba(0,0,0,0.6); color: white; font-size: 0.8rem; padding: 4px 8px; border-radius: 5px; }
        .activity-card.is-organizer:hover .card-tag { right: 80px; }
        .card-image img { width: 100%; height: 200px; object-fit: cover; background-color: #eee; }
        .card-content { padding: 20px; }
        .card-content h3 { font-size: 1.25rem; font-weight: 600; margin-bottom: 10px; }
        .card-content .description { font-size: 0.95rem; color: var(--secondary-color); margin-bottom: 15px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        .card-info { font-size: 0.9rem; color: var(--secondary-color); margin-bottom: 8px; }
        .card-info i { margin-right: 8px; color: var(--primary-green); width: 16px; }
        .card-organizer { font-size: 0.85rem; color: #888; margin-top: 15px; }
        .btn-join { width: 100%; margin-top: 15px; font-weight: 600; background-color: var(--primary-color); }
        .btn-join.joined { background-color: var(--success-color); border-color: var(--success-color); cursor: default; pointer-events: none; }
        .btn-join:disabled, .btn-join[disabled] { background-color: var(--secondary-color); border-color: var(--secondary-color); cursor: not-allowed; opacity: 0.65; }
    </style>
</head>
<body>

    <nav class="navbar">
        <a class="navbar-brand" href="project.php"> <i class="bi bi-compass"></i> GoOut </a>
        <div class="search-bar"> <i class="bi bi-search"></i> <input type="text" class="form-control" id="search-bar" placeholder="Rechercher une activité..."> </div>
        <div class="user-menu"> <a href="profile.php" title="<?php echo htmlspecialchars($username); ?>"> <i class="bi bi-person-circle"></i> </a> <a href="project.php?logout=true" title="Déconnexion"> <i class="bi bi-box-arrow-right"></i> </a> </div>
    </nav>

    <header class="hero-section">
        <h1>Participe à ta prochaine aventure !</h1>
        <p>Découvre des activités passionnantes et rencontre de nouvelles personnes près de chez toi.</p>
        <div class="hero-stats">
             <div class="stat-item"><span><?php echo 6 + count($db_activities); ?></span> Activités</div>
             <div class="stat-item"><span>120+</span> Participants</div>
             <div class="stat-item"><span>15</span> Villes</div>
        </div>
    </header>

    <main class="content-area">

        <div class="filter-bar">
            <span class="filter-title"><i class="bi bi-filter"></i> Filtrer les activités</span>
            <a href="create_activity.php" class="btn btn-create"><i class="bi bi-plus-lg"></i> Créer une activité</a>
        </div>
        <div class="filter-tags">
             <button class="btn filter-btn active" data-filter="tous">Tous</button>
             <button class="btn filter-btn" data-filter="randonnée">Randonnée</button>
             <button class="btn filter-btn" data-filter="pique-nique">Pique-nique</button>
             <button class="btn filter-btn" data-filter="culture">Culture</button>
             <button class="btn filter-btn" data-filter="sport">Sport</button>
             <button class="btn filter-btn" data-filter="autre">Autre</button>
             <select class="filter-select" id="filter-date"> <option value="tous">Toutes les dates</option> </select>
             <select class="filter-select" id="filter-lieu"> <option value="tous">Tous les lieux</option> </select>
             <button class="btn" id="reset-filter-btn" title="Réinitialiser les filtres"> <i class="bi bi-arrow-counterclockwise"></i> </button>
        </div>
        <hr class="my-4">

        <div class="card-grid">

            <div class="activity-card" data-category="randonnée" data-date="2025-11-15" data-location="marrakech" data-activity-id="static-toubkal">
                 <div class="card-image"> <img src="media/sommet-mont-toubkal.jpg" alt="Toubkal"> <span class="card-tag">Randonnée</span> </div>
                 <div class="card-content">
                    <h3>Ascension du Jbel Toubkal</h3> <p class="description">L'ascension du plus haut sommet d'Afrique du Nord. Nécessite une bonne condition physique.</p>
                    <div class="card-info"><i class="bi bi-calendar-event"></i> 15 Nov 2025 (2 jours)</div>
                    <div class="card-info"><i class="bi bi-geo-alt-fill"></i> Jbel Toubkal, Maroc</div>
                    <div class="card-info"><i class="bi bi-people-fill"></i> 4/8 participants</div>
                    <div class="card-info"><i class="bi bi-geo-fill"></i> 31.060, -7.915</div>
                    <p class="card-organizer">Organisé par Atlas Aventures</p>
                    <a href="#" class="btn btn-outline-secondary btn-sm" style="width: 100%; margin-bottom: 5px;" onclick="alert('Hadi card static, khassha tzad l database b l-formulaire 3ad ykhdem l-détails.'); return false;">Détails</a>
                    <a href="#" class="btn btn-primary btn-join join-static">Rejoindre</a>
                 </div>
            </div>
            <div class="activity-card" data-category="sport" data-date="2025-12-20" data-location="ifrane" data-activity-id="static-michlifen">
                 <div class="card-image"> <img src="media/ve1e8dykfinqfite6etv.jpg" alt="Michlifen"> <span class="card-tag">Sport</span> </div>
                 <div class="card-content">
                    <h3>Journée Ski à Michlifen</h3><p class="description">Profitons de la neige ! Location de matériel possible sur place. Ouvert à tous les niveaux.</p>
                    <div class="card-info"><i class="bi bi-calendar-event"></i> 20 Déc 2025 à 09:00</div>
                    <div class="card-info"><i class="bi bi-geo-alt-fill"></i> Michlifen, Ifrane</div>
                    <div class="card-info"><i class="bi bi-people-fill"></i> 10/20 participants</div>
                    <div class="card-info"><i class="bi bi-geo-fill"></i> 33.491, -5.113</div>
                    <p class="card-organizer">Organisé par Club Alpin Ifrane</p>
                    <a href="#" class="btn btn-outline-secondary btn-sm" style="width: 100%; margin-bottom: 5px;" onclick="alert('Hadi card static, khassha tzad l database b l-formulaire 3ad ykhdem l-détails.'); return false;">Détails</a>
                    <a href="#" class="btn btn-primary btn-join join-static">Rejoindre</a>
                </div>
            </div>
             <div class="activity-card" data-category="randonnée" data-date="2025-11-08" data-location="chefchaouen" data-activity-id="static-akchour">
                 <div class="card-image"> <img src="media/caption.jpg" alt="Akchour"> <span class="card-tag">Randonnée</span> </div>
                 <div class="card-content">
                    <h3>Cascades d'Akchour</h3> <p class="description">Randonnée vers le Pont de Dieu et les magnifiques cascades d'Akchour. Prévoyez de quoi nager !</p>
                    <div class="card-info"><i class="bi bi-calendar-event"></i> 8 Nov 2025 à 10:00</div>
                    <div class="card-info"><i class="bi bi-geo-alt-fill"></i> Akchour, Chefchaouen</div>
                    <div class="card-info"><i class="bi bi-people-fill"></i> 12/15 participants</div>
                    <div class="card-info"><i class="bi bi-geo-fill"></i> 35.263, -5.176</div>
                    <p class="card-organizer">Organisé par Rando Rif</p>
                    <a href="#" class="btn btn-outline-secondary btn-sm" style="width: 100%; margin-bottom: 5px;" onclick="alert('Hadi card static, khassha tzad l database b l-formulaire 3ad ykhdem l-détails.'); return false;">Détails</a>
                    <a href="#" class="btn btn-primary btn-join join-static">Rejoindre</a>
                </div>
            </div>
             <div class="activity-card" data-category="culture" data-date="2025-11-22" data-location="ouarzazate" data-activity-id="static-benhaddou">
                 <div class="card-image"> <img src="media/30.jpg" alt="Benhaddou"> <span class="card-tag">Culture</span> </div>
                 <div class="card-content">
                    <h3>Visite Ksar Aït Benhaddou</h3> <p class="description">Voyage dans le temps. Visite guidée du célèbre ksar classé UNESCO.</p>
                    <div class="card-info"><i class="bi bi-calendar-event"></i> 22 Nov 2025 à 11:00</div>
                    <div class="card-info"><i class="bi bi-geo-alt-fill"></i> Aït Benhaddou, Ouarzazate</div>
                    <div class="card-info"><i class="bi bi-people-fill"></i> 7/10 participants</div>
                    <div class="card-info"><i class="bi bi-geo-fill"></i> 31.047, -7.130</div>
                    <p class="card-organizer">Organisé par Youssef G.</p>
                    <a href="#" class="btn btn-outline-secondary btn-sm" style="width: 100%; margin-bottom: 5px;" onclick="alert('Hadi card static, khassha tzad l database b l-formulaire 3ad ykhdem l-détails.'); return false;">Détails</a>
                    <a href="#" class="btn btn-primary btn-join join-static">Rejoindre</a>
                </div>
            </div>
             <div class="activity-card" data-category="sport" data-date="2025-11-01" data-location="essaouira" data-activity-id="static-essaouira">
                 <div class="card-image"> <img src="media/kitesurf-rodrigues.jpg" alt="Essaouira"> <span class="card-tag">Sport</span> </div>
                 <div class="card-content">
                    <h3>Initiation Kitesurf</h3> <p class="description">Apprenez les bases du kitesurf avec des instructeurs pro...</p>
                     <div class="card-info"><i class="bi bi-calendar-event"></i> 1 Nov 2025 (Week-end)</div>
                     <div class="card-info"><i class="bi bi-geo-alt-fill"></i> Plage d'Essaouira</div>
                    <div class="card-info"><i class="bi bi-people-fill"></i> 3/5 participants</div>
                    <div class="card-info"><i class="bi bi-geo-fill"></i> 31.498, -9.761</div>
                    <p class="card-organizer">Organisé par Essaouira Kite School</p>
                    <a href="#" class="btn btn-outline-secondary btn-sm" style="width: 100%; margin-bottom: 5px;" onclick="alert('Hadi card static, khassha tzad l database b l-formulaire 3ad ykhdem l-détails.'); return false;">Détails</a>
                    <a href="#" class="btn btn-primary btn-join join-static">Rejoindre</a>
                </div>
            </div>
             <div class="activity-card" data-category="pique-nique" data-date="2025-11-02" data-location="marrakech" data-activity-id="static-takerkoust">
                 <div class="card-image"> <img src="media/idees-pique-nique.jpg" alt="Takerkoust"> <span class="card-tag">Pique-nique</span> </div>
                 <div class="card-content">
                    <h3>Pique-nique Lac Takerkoust</h3> <p class="description">Échappée au bord du lac. Chacun ramène quelque chose...</p>
                    <div class="card-info"><i class="bi bi-calendar-event"></i> 2 Nov 2025 à 12:00</div>
                    <div class="card-info"><i class="bi bi-geo-alt-fill"></i> Lalla Takerkoust, Marrakech</div>
                    <div class="card-info"><i class="bi bi-people-fill"></i> 20/30 participants</div>
                    <div class="card-info"><i class="bi bi-geo-fill"></i> 31.368, -8.143</div>
                    <p class="card-organizer">Organisé par Amine R.</p>
                    <a href="#" class="btn btn-outline-secondary btn-sm" style="width: 100%; margin-bottom: 5px;" onclick="alert('Hadi card static, khassha tzad l database b l-formulaire 3ad ykhdem l-détails.'); return false;">Détails</a>
                    <a href="#" class="btn btn-primary btn-join join-static">Rejoindre</a>
                </div>
            </div>

            <?php if (!empty($db_activities)): ?>
                <?php foreach ($db_activities as $activity): ?>
                    <?php
                        $activity_date_obj = new DateTime($activity['activity_date']);
                        $filter_date_format = $activity_date_obj->format('Y-m-d');
                        $filter_location = strtolower(str_replace(' ', '-', $activity['location_name']));
                        $activity_card_id_db = $activity['id'];
                        $is_organizer = ($activity['organizer_username'] == $username);
                        $is_joined = in_array($activity_card_id_db, $joined_activity_ids_db);
                        $organizer_class = $is_organizer ? 'is-organizer' : '';
                    ?>
                    <div class="activity-card <?php echo $organizer_class; ?>"
                         data-category="<?php echo htmlspecialchars($activity['category']); ?>"
                         data-date="<?php echo $filter_date_format; ?>"
                         data-location="<?php echo htmlspecialchars($filter_location); ?>"
                         data-activity-id="<?php echo $activity_card_id_db; ?>">

                        <div class="card-image">
                            <?php if (!empty($activity['image_path']) && file_exists($activity['image_path'])): ?>
                                <img src="<?php echo htmlspecialchars($activity['image_path']); ?>" alt="<?php echo htmlspecialchars($activity['title']); ?>">
                            <?php else: ?>
                                <img src="placeholder.jpg" alt="Image par défaut"> <?php endif; ?>
                            <span class="card-tag"><?php echo htmlspecialchars(ucfirst($activity['category'])); ?></span>

                            <?php if ($is_organizer): ?>
                                <div class="card-actions">
                                     <a href="edit_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-action" title="Modifier"><i class="bi bi-pencil-fill"></i></a>
                                     <a href="delete_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-action btn-delete" title="Supprimer" onclick="return confirm('Wach bsse7?');"><i class="bi bi-trash-fill"></i></a>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-content">
                             <h3><?php echo htmlspecialchars($activity['title']); ?></h3>
                            <p class="description"><?php echo htmlspecialchars($activity['description']); ?></p>
                            <div class="card-info"><i class="bi bi-calendar-event"></i> <?php echo $activity_date_obj->format('d M Y \à H:i'); ?></div>
                            <div class="card-info"><i class="bi bi-geo-alt-fill"></i> <?php echo htmlspecialchars($activity['location_name']); ?></div>
                            <div class="card-info"><i class="bi bi-people-fill"></i> <span class="current-participants">0</span>/<?php echo htmlspecialchars($activity['max_participants']); ?></div>
                            <?php if (!empty($activity['location_coords'])): ?> <div class="card-info"><i class="bi bi-geo-fill"></i> <?php echo htmlspecialchars($activity['location_coords']); ?></div> <?php endif; ?>
                            <p class="card-organizer">Organisé par <?php echo htmlspecialchars($activity['organizer_username']); ?></p>

                            <a href="activity_details.php?id=<?php echo $activity['id']; ?>" class="btn btn-outline-secondary btn-sm" style="width: 100%; margin-bottom: 5px;">
                                <i class="bi bi-info-circle-fill"></i> Détails
                            </a>
                            
                            <?php if ($is_organizer): ?>
                                <button class="btn btn-secondary btn-join" disabled>Vous organisez</button>
                            <?php elseif ($is_joined): ?>
                                <button class="btn btn-success btn-join joined" disabled>Rejoint</button>
                            <?php else: ?>
                                <a href="join_activity.php?id=<?php echo $activity['id']; ?>" class="btn btn-primary btn-join">Rejoindre</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>

            <?php if (empty($db_activities) && 6 == 0): ?>
                 <p style="grid-column: 1 / -1; text-align: center; color: var(--secondary-color);">Aucune activité trouvée...</p>
            <?php endif; ?>

        </div> </main>

    <script src="bootstrap.bundle.min.js"></script> <script>
        document.addEventListener('DOMContentLoaded', () => {
             // L-Code dyal Filter khllih kif kan...
             const categoryButtons = document.querySelectorAll('.filter-btn');
             const dateFilter = document.getElementById('filter-date');
             const locationFilter = document.getElementById('filter-lieu');
             const searchBar = document.getElementById('search-bar');
             const resetBtn = document.getElementById('reset-filter-btn');
             const activityCards = document.querySelectorAll('.activity-card');

             const today = new Date(); today.setHours(0,0,0,0);
             function checkDateMatch(cardDateString, filterValue) { /* ... function code ... */ if (!cardDateString || filterValue === 'tous') return true; const cardDate = new Date(cardDateString); cardDate.setHours(0,0,0,0); if (filterValue === 'aujourdhui') { return cardDate.getTime() === today.getTime(); } if (filterValue === 'ce-mois') { return cardDate.getMonth() === today.getMonth() && cardDate.getFullYear() === today.getFullYear(); } if (filterValue === 'weekend') { const dayOfWeek = today.getDay(); const startOfWeekend = new Date(today); startOfWeekend.setDate(today.getDate() + (6 - dayOfWeek + (dayOfWeek === 0 ? -7 : 0) ) % 7); const endOfWeekend = new Date(startOfWeekend); endOfWeekend.setDate(startOfWeekend.getDate() + 1); return cardDate.getTime() >= startOfWeekend.getTime() && cardDate.getTime() <= endOfWeekend.getTime(); } return false; }
             function applyFilters() { /* ... function code ... */ const activeCategoryBtn = document.querySelector('.filter-btn.active'); const categoryValue = activeCategoryBtn ? activeCategoryBtn.getAttribute('data-filter') : 'tous'; const dateValue = dateFilter.value; const locationValue = locationFilter.value; const searchValue = searchBar.value.toLowerCase().trim(); activityCards.forEach(card => { const cardCategory = card.getAttribute('data-category'); const cardDate = card.getAttribute('data-date'); const cardLocation = card.getAttribute('data-location'); const cardText = card.textContent.toLowerCase(); const isCategoryMatch = (categoryValue === 'tous' || categoryValue === cardCategory); const isDateMatch = checkDateMatch(cardDate, dateValue); const isLocationMatch = (locationValue === 'tous' || locationValue === cardLocation); const isSearchMatch = cardText.includes(searchValue); if (isCategoryMatch && isDateMatch && isLocationMatch && isSearchMatch) { card.classList.remove('filtered-out'); } else { card.classList.add('filtered-out'); } }); }
             categoryButtons.forEach(button => { /* ... listener code ... */ button.addEventListener('click', () => { categoryButtons.forEach(btn => btn.classList.remove('active')); button.classList.add('active'); applyFilters(); }); });
             dateFilter.addEventListener('change', applyFilters);
             locationFilter.addEventListener('change', applyFilters);
             searchBar.addEventListener('input', applyFilters);
             resetBtn.addEventListener('click', () => { /* ... reset code ... */ categoryButtons.forEach(btn => btn.classList.remove('active')); const tousButton = document.querySelector('.filter-btn[data-filter="tous"]'); if (tousButton) tousButton.classList.add('active'); dateFilter.value = 'tous'; locationFilter.value = 'tous'; searchBar.value = ''; applyFilters(); });


             // --- Script dyal localStorage L-LQDIMA ---
             const staticJoinButtons = document.querySelectorAll('.join-static');
             let joinedStaticActivities = JSON.parse(localStorage.getItem('joinedStaticActivities')) || [];

             function updateStaticJoinButtons() {
                 staticJoinButtons.forEach(button => {
                     const card = button.closest('.activity-card');
                     if (!card) return;
                     const activityId = card.getAttribute('data-activity-id'); // static-toubkal, etc.
                     if (activityId && joinedStaticActivities.includes(activityId)) {
                         button.textContent = 'Rejoint';
                         button.classList.add('joined', 'btn-success'); // Zid loun khder
                         button.classList.remove('btn-primary');
                         button.disabled = true;
                         button.style.pointerEvents = 'none';
                     } else {
                         button.textContent = 'Rejoindre';
                         button.classList.remove('joined', 'btn-success');
                         button.classList.add('btn-primary');
                         button.disabled = false;
                         button.style.pointerEvents = 'auto';
                     }
                 });
             }

             staticJoinButtons.forEach(button => {
                 button.addEventListener('click', (e) => {
                     e.preventDefault();
                     const card = e.target.closest('.activity-card');
                     if (!card) return;
                     const activityId = card.getAttribute('data-activity-id');
                     if (activityId && !joinedStaticActivities.includes(activityId)) {
                         joinedStaticActivities.push(activityId);
                         localStorage.setItem('joinedStaticActivities', JSON.stringify(joinedStaticActivities));
                         updateStaticJoinButtons();
                     }
                 });
             });
             updateStaticJoinButtons(); // Check status on page load

             applyFilters(); // Apply filters on initial load
        });
    </script>

</body>
</html>