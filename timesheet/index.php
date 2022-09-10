<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/classi/clienti.php';
require_once '../funzioni/classi/progetti.php';

$_pagina = new Pagina('Timesheet', PAGINA_RISERVATA_UTENTE);

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$progetti = new Progetti();
$progetti->ottieniRisultatiFiltrati();

$clienti = new Clienti();
$clienti->ottieniListaOpzioni();

$_pagina->creaTesta();
?>

<div class="container">
    <h2><?=htmlspecialchars($_utente->nome) . ' ' . htmlspecialchars($_utente->cognome)?> - Timesheet</h2>
    <p>
        <a href="progetti.php">Tutti i progetti</a>
    </p>
</div>

<?php
$_pagina->creaFooter();
?>
