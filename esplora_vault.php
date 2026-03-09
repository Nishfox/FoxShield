<?php
error_reporting(0);
header('Content-Type: application/json');

$input = isset($_GET['caso']) ? trim($_GET['caso']) : '';

if(empty($input)) {
    echo json_encode(["status" => "error", "msg" => "ID CASO MANCANTE."]);
    exit;
}

$base_vault = "FoxShield_Vault";
if(!is_dir($base_vault)) {
    echo json_encode(["status" => "error", "msg" => "VAULT NON TROVATO SUL NAS."]);
    exit;
}

// ---------------------------------------------------------
// MOTORE DI RICERCA INTELLIGENTE (Ignora Maiuscole/Minuscole)
// ---------------------------------------------------------
$target_dir = "";
$input_lower = strtolower($input);
$input_caso_lower = "caso_" . $input_lower;

$items = scandir($base_vault);
foreach($items as $item) {
    if($item == "." || $item == "..") continue;
    $item_lower = strtolower($item);
    
    // Trova la cartella a prescindere da come è scritta
    if($item_lower == $input_lower || $item_lower == $input_caso_lower) {
        $target_dir = $base_vault . "/" . $item;
        break;
    }
}

if(empty($target_dir) || !is_dir($target_dir)) {
    echo json_encode(["status" => "error", "msg" => "CARTELLA NON TROVATA. NESSUN RISCONTRO SUL NAS per: $input"]);
    exit;
}

// Lettura ID_utente.txt
$testo_utente = "Dati non disponibili.";
if(file_exists($target_dir . "/ID_utente.txt")) {
    $testo_utente = file_get_contents($target_dir . "/ID_utente.txt");
}

$files_data = [];
$folders = []; 

function scan_vault($dir, &$results, &$folders, $root) {
    $items = scandir($dir);
    foreach($items as $item) {
        if($item == "." || $item == ".." || $item == "ID_utente.txt" || $item == "Thumbs.db") continue;
        $full_path = $dir . "/" . $item;
        
        if(is_dir($full_path)) {
            $folders[] = $item; 
            scan_vault($full_path, $results, $folders, $root);
        } else {
            $ext = strtolower(pathinfo($full_path, PATHINFO_EXTENSION));
            $type = 'doc';
            if(in_array($ext, ['mp4', 'webm', 'mov'])) $type = 'video';
            elseif(in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) $type = 'foto';
            elseif(in_array($ext, ['mp3', 'wav', 'ogg', 'm4a'])) $type = 'audio';
            
            $parent = basename(dirname($full_path));
            $results[] = [
                "name" => $item,
                "folder" => ($parent == basename($root)) ? "ROOT" : $parent,
                "type" => $type,
                "path" => $full_path,
                "size" => round(filesize($full_path) / 1024 / 1024, 2) . ' MB',
                "date" => date("d/m/Y H:i", filemtime($full_path)),
                "t" => filemtime($full_path)
            ];
        }
    }
}

scan_vault($target_dir, $files_data, $folders, $target_dir);
usort($files_data, function($a, $b) { return $b['t'] - $a['t']; });

echo json_encode([
    "status" => "ok", 
    "files" => $files_data, 
    "folders" => array_unique($folders),
    "dati_target" => $testo_utente
]);
?>