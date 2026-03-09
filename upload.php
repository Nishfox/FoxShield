<?php
/**
 * PROJECT: OverWatch__RecoRdeR // Terminal
 * SYSTEM: FOXSHIELD VAULT - DUAL DOCUMENT & TIMELINE SYSTEM
 * MOD: PERMANENT ID LOCK SYSTEM + OVERRIDE LIMITI RAM NAS
 */

// --- OVERRIDE LIMITI DI MEMORIA PER IL NAS SYNOLOGY ---
// Questi comandi dicono al server di accettare file enormi e di non andare in panico
ini_set('memory_limit', '1024M');
ini_set('upload_max_filesize', '500M');
ini_set('post_max_size', '500M');
ini_set('max_execution_time', '300'); // 5 minuti di tempo massimo

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");
date_default_timezone_set('Europe/Rome');

// --- CONFIGURAZIONE CLOUDINARY ---
$cloud_name = "dvvrowjvr";
$api_key    = "111812921511211"; 
$api_secret = "HxYYL4uhwHXEhoLZcP0ZEHdyTxQ";

$save_folder = "FoxShield_Vault";
if (!file_exists($save_folder)) { mkdir($save_folder, 0777, true); }

if (isset($_FILES['file'])) {
    // RECUPERO DATI POST
    $case_id        = $_POST['case_id']       ?? 'UNKNOWN';
    $nome           = $_POST['nome']          ?? 'SCONOSCIUTO';
    $cognome        = $_POST['cognome']       ?? 'SCONOSCIUTO';
    $indirizzo      = $_POST['indirizzo']     ?? 'N/A';
    $telefono       = $_POST['telefono']      ?? 'N/A';
    $email          = $_POST['email']         ?? 'N/A';
    $operatore      = $_POST['operatore']     ?? 'Jean@live.it';
    $gps            = $_POST['gps']           ?? 'N/A';
    $usa_timestamp  = $_POST['usa_timestamp'] ?? 'false'; 
    $relazione_testo = trim($_POST['relazione'] ?? '');
    $stato_relazione = ($relazione_testo !== '') ? "Allegata" : "Non Allegata";

    $timestamp_id = date("Ymd_His");          
    $data_leggibile = date("d-m-Y H:i:s");    
    $data_cartella = date("Y-m-d");          

    // STRUTTURA CARTELLE: Root Caso -> Sottocartella Data
    $root_case_path = $save_folder . "/CASO_" . $case_id;
    $day_folder = $root_case_path . "/DATA_" . $data_cartella;
    
    if (!file_exists($day_folder)) { mkdir($day_folder, 0777, true); }

    // ============================================================
    // LOGICA PERMANENTE ID_utente.txt (SOLO PRIMA VOLTA)
    // ============================================================
    $id_file_path = $root_case_path . "/ID_utente.txt";
    if (!file_exists($id_file_path)) {
        $id_text = "===========================================\n";
        $id_text .= "        FOXSHIELD ID TARGET PERMANENT\n";
        $id_text .= "===========================================\n";
        $id_text .= "ID CASO      : $case_id\n";
        $id_text .= "NOME         : $nome\n";
        $id_text .= "COGNOME      : $cognome\n";
        $id_text .= "INDIRIZZO    : $indirizzo\n";
        $id_text .= "TELEFONO     : $telefono\n";
        $id_text .= "EMAIL        : $email\n";
        $id_text .= "OPERATORE    : $operatore\n";
        $id_text .= "CREATO IL    : $data_leggibile\n";
        $id_text .= "===========================================\n";
        file_put_contents($id_file_path, $id_text);
    }
    // ============================================================

    // SALVATAGGIO FILE FISICO
    $file_tmp = $_FILES['file']['tmp_name'];
    $extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
    if (!$extension) {
        $extension = (strpos($_FILES['file']['type'], 'video') !== false) ? 'mp4' : 'webm';
    }

    $final_filename = "CERTIFICATO_" . $timestamp_id . "_" . $nome . "_" . $cognome . "." . $extension;
    $final_path = $day_folder . "/" . $final_filename;

    $status_msg = "STANDARD_ACQUISITION";
    $processed_url = "";

    // GESTIONE CLOUDINARY PER TIMESTAMP (SOLO SE RICHIESTO)
    if ($usa_timestamp === "true" && strpos($_FILES['file']['type'], 'video') !== false) {
        $ch = curl_init();
        $timestamp_cloud = time();
        $overlay_text = "l_text:Arial_30_bold:" . rawurlencode("FOXSHIELD | " . $data_leggibile) . ",co_white,g_north_east,x_30,y_30,b_rgb:00000090";
        $signature = sha1("timestamp=$timestamp_cloud&transformation=$overlay_text" . $api_secret);
        
        $post_data = [
            "file" => new CURLFile($file_tmp),
            "timestamp" => $timestamp_cloud,
            "api_key" => $api_key,
            "signature" => $signature,
            "transformation" => $overlay_text
        ];

        curl_setopt($ch, CURLOPT_URL, "https://api.cloudinary.com/v1_1/$cloud_name/video/upload");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        $result = json_decode($response, true);
        curl_close($ch);

        if (isset($result['secure_url'])) { $processed_url = $result['secure_url']; }
    }

    // FINALIZZAZIONE SALVATAGGIO
    if ($processed_url != "") {
        $ch_dl = curl_init($processed_url);
        $fp = fopen($final_path, 'wb');
        curl_setopt($ch_dl, CURLOPT_FILE, $fp);
        curl_setopt($ch_dl, CURLOPT_FOLLOWLOCATION, true);
        curl_exec($ch_dl);
        curl_close($ch_dl);
        fclose($fp);
        $status_msg = "PIXEL_CERTIFIED_SUCCESS";
    } else {
        move_uploaded_file($file_tmp, $final_path);
        $status_msg = ($usa_timestamp === "true") ? "CLOUD_ERROR_RAW_SAVED" : "STANDARD_LOCAL_SAVE";
    }

    // CREAZIONE VERBALE ACQUISIZIONE SINGOLA
    $info_file = $day_folder . "/VERBALE_" . $timestamp_id . ".txt";
    $testo = "===========================================\n";
    $testo .= "        FOXSHIELD SYSTEM CERTIFICATION\n";
    $testo .= "===========================================\n";
    $testo .= "DATA            : $data_leggibile\n";
    $testo .= "OPERATORE       : $operatore\n"; // Scrive id operatore attuale
    $testo .= "ID CASO         : $case_id\n";
    $testo .= "GPS             : $gps\n";
    $testo .= "FILE FISICO     : $final_filename\n";
    $testo .= "STATUS          : $status_msg\n";
    $testo .= "===========================================\n";
    file_put_contents($info_file, $testo);

    // CREAZIONE RELAZIONE SE PRESENTE
    if ($stato_relazione === "Allegata") {
        $doc_file = $day_folder . "/RELAZIONE_" . $timestamp_id . ".doc";
        $testo_doc = "RAPPORTO INVESTIGATIVO - $data_leggibile\n";
        $testo_doc .= "OPERATORE: $operatore\n";
        $testo_doc .= "TESTO RELAZIONE:\n$relazione_testo\n";
        file_put_contents($doc_file, $testo_doc);
    }
    
    echo json_encode(["message" => "OK", "timestamp" => $data_leggibile, "status" => $status_msg]);
} else {
    echo json_encode(["error" => "NESSUN DATO RICEVUTO"]);
}
?>