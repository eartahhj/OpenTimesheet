<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/time-library.php';
require_once '../funzioni/classi/progetti.php';
require_once '../funzioni/classi/timesheet.php';
require_once '../funzioni/classi/fatture.php';

$_pagina = new Pagina('Timesheet - Progetto', PAGINA_RISERVATA_UTENTE);

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$progetti = new Progetti();
$progetti->ottieniListaOpzioni();

$utenti = new Utenti();
$utenti->ordine = "CONCAT(nome, ' ', cognome) ASC";
$utenti->ottieniRecordTutti();
foreach ($utenti->lista as $idUtente => $utente) {
    $listaUtentiNomeCognome[$idUtente] = htmlspecialchars($utente->nome . ' ' . $utente->cognome);
}

$progettoSelezionato = $_REQUEST['progetto'] ?? 0;

$progetto = null;

$timesheet = new Timesheet();

$reportUserId = 0;
if (isset($_GET['user']) and $_GET['user']) {
    $reportUserId = (int)$_GET['user'];
    $timesheet->setUserId($reportUserId);
}

$reportDateStart = '';
if (isset($_GET['datestart']) and $_GET['datestart']) {
    $reportDateStart = $_GET['datestart'];
    $timesheet->setDateStart($reportDateStart);
}

$reportDateEnd = '';
if (isset($_GET['dateend']) and $_GET['dateend']) {
    $reportDateEnd = $_GET['dateend'];
    $timesheet->setDateEnd($reportDateEnd);
}

$reportDescription = '';
if (isset($_GET['description']) and $_GET['description']) {
    $reportDescription = $_GET['description'];
    $timesheet->setDescription($reportDescription);
}

if ($progettoSelezionato) {
    $progetto = new Progetto($progettoSelezionato);
} else {
    $progetto = new Progetto();
}

if ($progetto->getId()) {
    $timesheet->setProjectId($progetto->getId());
    $timesheet->loadProjectReport();
    if (!$timesheet->getTotalWorkedTimeInMinutes()) {
        $_pagina->messaggi[] = new MessaggioInfo('Nessuno ha lavorato a questo progetto');
    }
}


$form = new Campi(FORM_TIMESHEET_REPORT);
$form->campi['user']=new Campo('user', 'Utente', TIPO_INTERO, array('valori'=>$listaUtentiNomeCognome,'default'=>$reportUserId, 'obbligatorio'=>false));
$form->campi['progetto']=new Campo('progetto', 'Progetto', TIPO_INTERO, array('valori'=>$progetti->listaOpzioni,'default'=>$progettoSelezionato, 'obbligatorio'=>false));
$form->campi['datestart']=new Campo('datestart', 'Data inizio', TIPO_STRINGA, array('obbligatorio'=>false, 'default' => $reportDateStart));
$form->campi['dateend']=new Campo('dateend', 'Data fine', TIPO_STRINGA, array('obbligatorio'=>false, 'default' => $reportDateEnd));
$form->campi['description']=new Campo('descrizione', 'Descrizione', TIPO_STRINGA, array('default' => $reportDescription));

$_pagina->messaggi[] = new MessaggioInfo('Qui non vengono più contate le ore negative, vedi Lotti Ore.');

$_pagina->creaTesta();
?>

<section id="pagina-report-progetto" class="container">
    <form id="form-report-progetto-filtra" action="<?=$_SERVER['PHP_SELF']?>" method="get" class="clearfix form-inline">
        <h2>Filtra report per:</h2>
        <div class="grid">
            <?php
            $form->creaCampoDiv('user', CAMPO_SELECT, null, 'Selezionare');
            $form->creaCampoDiv('progetto', CAMPO_SELECT, null, 'Selezionare');
            $form->creaCampoDiv('datestart', CAMPO_INPUTTEXT);
            $form->creaCampoDiv('dateend', CAMPO_INPUTTEXT);
            $form->creaCampoDiv('description', CAMPO_INPUTTEXT);
            ?>
        <label><input type="checkbox" value="t" name="calculate-costs"<?=((isset($_GET['calculate-costs']) and $_GET['calculate-costs'] == 't') ? 'checked="checked"' : '')?>> Calcola costi</label>
        </div>
        <input type="hidden" value="<?=$form->formID?>" name="formID" />
        <div class="campi-bottoni">
            <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
            <a href="<?=$_SERVER['PHP_SELF']?>?progetto=<?=$progetto->getId()?>" class="btn btn-annulla">Azzera</a>
            <a href="progetto.php?azione=crea" class="btn btn-nuovo">Nuovo progetto</a>
        </div>
    </form>

<?php
if (!$timesheet->getTasks()):
?>
<h4>Nessun report da mostrare</h4>
<?php
else:
?>
<h2>
    Report progetto: <em><?=$progetto->getName()?></em>
    <?php if ($reportDateStart or $reportDateEnd):?>
        <span>
            <?php if ($reportDateStart):?>
                &nbsp;dal <?=$reportDateStart?>
            <?php endif?>
            <?php if ($reportDateEnd):?>
                fino al <?=$reportDateEnd?>
            <?php endif?>
        </span>
    <?php endif?>
</h2>
<h3>
    Stato economico attuale:
    <?php
    if ($timesheet->getRemainingTimeInMinutes() > 0) {
        echo '<span class="positivo">In positivo';
    } elseif ($timesheet->getRemainingTimeInMinutes() == 0) {
        echo '<span class="pareggio">Pareggio';
    } else {
        echo '<span class="negativo">In perdita';
    }
    echo ' (' . TimeLibrary::getTimeInReadableFormatByMinutes($timesheet->getRemainingTimeInMinutes()) . ')</span>';
    ?>
</h3>

<?php
if ($timesheet->getRemainingTimeInMinutes() < 0) {
    $labelResiduo = 'Ore perse';
    $classeCssResiduo = 'negativo';
} else {
    $labelResiduo = 'Residuo ore';
    $classeCssResiduo = 'positivo';
}
?>

<table id="report-summary" class="table">
    <thead>
        <tr>
            <th>Totale lotti ore disponibili</th>
            <th>Totale ore lavorate</th>
            <th>Totale fatture emesse</th>
            <th class="<?=$classeCssResiduo?>"><?=$labelResiduo?></th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td><?=TimeLibrary::getTimeInReadableFormatByMinutes($timesheet->getAvailableTimeToWorkMinutes())?> (&euro; <?=TimeLibrary::getEurosByMinutes($timesheet->getAvailableTimeToWorkMinutes())?>)</td>
            <td><?=$timesheet->getTotalWorkedTimeInHoursReadableFormat()?> (&euro; <?=$timesheet->getTotalMoneySpentByMinutes()?>)</td>
            <td>&euro; <?=FattureProgetti::getTotalEurosForProject($progetto->getId())?></td>
            <td class="<?=$classeCssResiduo?>">
                <?php if (!isset($_GET['filtra'])):?>
                <?=$timesheet->getRemainingTimeInReadableFormat()?>
                (&euro; <?=$timesheet->getRemainingMoneyToSpend()?>)
                <?php endif?>
            </td>
        </tr>
    </tbody>
</table>

<table id="report-complete" class="table">
    <thead>
        <tr>
            <th>Data</th>
            <th>Tempo</th>
            <th>Utente</th>
            <th>Descrizione</th>
            <th>Totale progressivo</th>
        </tr>
    </thead>
    <?php
    $i = 0;
    foreach ($timesheet->getTasks() as $task):
        $dataTaskCorrente = $task->getDate();
        if ($dataTaskPrecedente != $dataTaskCorrente):
            if ($i > 0):?>
            </tbody>
            <?php
            endif;
    ?>
    <tbody class="timesheet-report-rowgroup-samedate">
        <?php
        endif;

        if ($task->getUserId()) {
            $taskUser = new Utente();
            $taskUser->ottieniDaId($task->getUserId());
        }

        $rowCssClass = '';

        if ($task->getMinutesSpent() < 0) {
            $rowCssClass = 'fattura';
        }
        ?>
        <tr<?=($rowCssClass ? ' class="' . $rowCssClass . '"' : '')?>>
            <td><a href="timesheet.php?data=<?=$task->getDate()?>"><?=$task->getDate()?></a></td>
            <td>
                <?=TimeLibrary::getTimeInReadableFormatByMinutes($task->getMinutesSpent())?>
                <?php if (isset($_GET['calculate-costs']) and $_GET['calculate-costs'] == 't' and $task->getMinutesSpent() > 0):?>
                (&euro; <?=$task->getMoneySpentByMinutes()?>)
                <?php endif?>
            </td>
            <td>
                <?php if ($_utente->getLevel() < PAGINA_RISERVATA_ADMIN):?>
                    <?php if ($_utente->getId() != $taskUser->getId()):?>
                        [redacted]
                    <?php else:?>
                        <?=htmlspecialchars($taskUser->getFullName())?>
                    <?php endif?>
                <?php else:?>
                <a href="utente.php?id=<?=$task->getUserId()?>"><?=htmlspecialchars($taskUser->getFullName())?></a>
                <?php endif?>
            </td>
            <td>
                <?php if ($_utente->getLevel() < PAGINA_RISERVATA_ADMIN and $_utente->getId() != $taskUser->getId()):?>
                    [redacted]
                <?php else:?>
                    <?=htmlspecialchars($task->getDescription())?>
                <?php endif?>
            </td>
            <td>
                <?=TimeLibrary::getTimeInReadableFormatByMinutes($timesheet->getProgressiveTimes()[$i])?>
            </td>
        </tr>

        <?php
        if ($i == count($timesheet->getTasks()) - 1):
        ?>
        </tbody>
        <?php
        endif;

        $dataTaskPrecedente = $dataTaskCorrente;

        $i++;

    endforeach;
    ?>
    <tfoot>
        <tr class="final-summary">
            <td colspan="2">Totale ore lavorate: <?=$timesheet->getTotalWorkedTimeInHoursReadableFormat()?></td>
            <td>(&euro; <?=$timesheet->getTotalMoneySpentByMinutes()?>)</td>
            <td colspan="2" class="<?=$classeCssResiduo?>">
                <?php if (!isset($_GET['filtra'])):?>
                <?=$labelResiduo . ': ' . $timesheet->getRemainingTimeInReadableFormat()?>
                (&euro; <?=$timesheet->getRemainingMoneyToSpend()?>)
                <?php endif?>
            </td>
        </tr>
    </tfoot>
</table>
<?php
endif;
?>

<?php if ($reportDateStart or $reportDateEnd):?>
    <p>
        Report
        <?php if ($reportDateStart):?>
            &nbsp;dal <?=$reportDateStart?>
        <?php endif?>
        <?php if ($reportDateEnd):?>
            fino al <?=$reportDateEnd?>
        <?php endif?>
    </p>
<?php endif?>

</section>

<?php
$_pagina->creaFooter();
