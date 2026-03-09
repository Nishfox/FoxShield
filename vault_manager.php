<?php
/**
 * VAULT MANAGER - FOXSHIELD DATABASE ENGINE
 */

header("Content-Type: application/json");
$case_id = $_GET['case_id'] ?? '';

if (empty($case_id)) {
    echo json_encode([]);
    exit;
}

$vault_path = "FoxShield_Vault/CASO_" . $case_id;
$results = [];

if (file_exists($vault_path)) {
    // Esplora tutte le sottocartelle (le date)
    $dates = array_diff(scandir($vault_path), array('..', '.'));
    
    foreach ($dates as $date_folder) {
        $full_date_path = $vault_path . "/" . $date_folder;
        if (is_dir($full_date_path)) {
            $files = array_diff(scandir($full_date_path), array('..', '.'));
            foreach ($files as $file) {
                $results[] = [
                    "name" => "[" . $date_folder . "] " . $file,
                    "path" => $full_date_path . "/" . $file
                ];
            }
        }
    }
}

echo json_encode($results);
?>