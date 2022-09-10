<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/time-library.php';
require_once '../funzioni/vendor/email-sender.php';

$query = <<<SQL
WITH lotti AS (
    SELECT
        progetto AS progetto_lotti,
        EXTRACT(EPOCH FROM SUM(numero_ore))/60 AS minuti_disponibili
    FROM lotti_ore
    GROUP BY progetto
),
timesheet AS (
    SELECT
        progetto AS progetto_ts,
        EXTRACT(EPOCH FROM SUM(tempo))/60 AS minuti_lavorati
    FROM timesheet
    WHERE tempo > '00:00:00'
    GROUP BY progetto
)
SELECT
	CASE
		WHEN progetto_lotti IS NOT NULL THEN progetto_lotti
		WHEN progetto_ts IS NOT NULL THEN progetto_ts
	END progetto_id,
	CASE
		WHEN minuti_disponibili IS NULL THEN minuti_lavorati
		WHEN minuti_disponibili IS NOT NULL AND minuti_lavorati IS NOT NULL THEN (minuti_disponibili - minuti_lavorati)
	END minuti_rimasti,
	progetti.nome AS progetto_nome
FROM lotti FULL OUTER JOIN timesheet ON progetto_ts = progetto_lotti JOIN progetti ON progetti.id = progetto_ts
WHERE progetti.abilitato = 't'
ORDER BY minuti_rimasti DESC
SQL;

if (!$result = $_database->query($query)) {
    exit();
}

$emailBody = '';

while ($record = $_database->fetch($result)) {
    $emailBody .= '<p>' . htmlspecialchars($record->progetto_nome) . ': ';
    if (intval($record->minuti_rimasti) < 0) {
        $emailBody .= 'NEGATIVO';
    } else {
        $emailBody .= 'POSITIVO';
    }
    $emailBody .= ' con ' . TimeLibrary::getTimeInReadableFormatByMinutes($record->minuti_rimasti) . '</p>';
}

$email = new Email();
$email->setSubject(_('Report dei progetti da controllare'));
$email->setFrom('email@dominio.it');
$email->addAddressTo('email@dominio.it');
$email->setBody($emailBody);
$email->send();
?>
