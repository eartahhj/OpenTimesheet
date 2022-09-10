<?php
$projectId = intval($_GET['progetto'] ?? 0);

if (!$projectId) {
    exit();
}

require_once '../funzioni/funzioni.php';
require_once '../funzioni/time-library.php';
require_once '../funzioni/classi/timesheet.php';

$_utente = new Utente();

if (!$_utente->loadFromCurrentSession()) {
    exit();
}

if ($_utente->getLevel() < PAGINA_RISERVATA_ADMIN) {
    exit();
}

$data = [];
$data['minuti'] = Timesheet::getRemainingTimeInMinutesForProject($projectId);

header('Content-Type: application/json; charset=utf-8');
echo json_encode($data);
exit();
