<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['joinedStaticActivities']) && is_array($input['joinedStaticActivities'])) {
        $_SESSION['joined_static_activities'] = $input['joinedStaticActivities'];
        echo json_encode(['success' => true, 'message' => 'Activités synchronisées']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Données invalides']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
}
?>