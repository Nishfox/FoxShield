<?php
header("Content-Type: application/json");
$db_file = 'users_db.json';

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';

// Carica utenti esistenti
$db = json_decode(file_get_contents($db_file), true);

if ($action === 'register') {
    // Controllo se utente esiste già
    foreach ($db['users'] as $u) {
        if ($u['username'] === $data['username']) {
            echo json_encode(["status" => "error", "msg" => "UTENTE GIÀ ESISTENTE"]);
            exit;
        }
    }
    // Aggiungi nuovo utente
    $db['users'][] = [
        "username" => $data['username'],
        "password" => $data['password'],
        "operatore" => $data['email'] // Usiamo l'email come ID operatore
    ];
    file_put_contents($db_file, json_encode($db, JSON_PRETTY_PRINT));
    echo json_encode(["status" => "success", "msg" => "REGISTRAZIONE COMPLETATA"]);

} else if ($action === 'login') {
    foreach ($db['users'] as $u) {
        if ($u['username'] === $data['username'] && $u['password'] === $data['password']) {
            echo json_encode([
                "status" => "success", 
                "username" => $u['username'], 
                "operatore" => $u['operatore']
            ]);
            exit;
        }
    }
    echo json_encode(["status" => "error", "msg" => "CREDENZIALI ERRATE"]);
}
?>