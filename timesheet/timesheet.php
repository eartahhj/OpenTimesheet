<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/time-library.php';
require_once '../funzioni/classi/clienti.php';
require_once '../funzioni/classi/progetti.php';
require_once '../funzioni/classi/timesheet.php';

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$utenti = new Utenti();
$utenti->ordine = "CONCAT(nome, ' ', cognome) ASC";
$utenti->ottieniRecordTutti();
$utentiAttivi = $utentiInattivi = [];

foreach ($utenti->lista as $idUtente => $utente) {
    if ($utente->isActive()) {
        $utentiAttivi[$idUtente] = htmlspecialchars($utente->getFullName());
    } else {
        $utentiInattivi[$idUtente] = htmlspecialchars($utente->getFullName());
    }
}

$progetti = new Progetti();
$progetti->ordine = 'abil DESC, nome ASC';
$progetti->ottieniRecordTutti();
$progetti->ottieniListaOpzioni();

foreach ($progetti->listaOpzioni as $id => $progetto) {
    if (!$progetti->lista[$id]->isActive()) {
        $progetti->listaOpzioni[$id] = '(ARCHIVIATO) ' . $progetto;
    }
}

$dateStart = $_GET['datestart'] ?? date('d/m/Y');
if (!TimeLibrary::isDateFormatValid($dateStart)) {
    $dateStart = date('d/m/Y');
}

$dateEnd = $_GET['dateend'] ?? date('d/m/Y');
if (!TimeLibrary::isDateFormatValid($dateEnd)) {
    $dateEnd = date('d/m/Y');
}

$projectId = $_GET['project'] ?? 0;
$projectName = $_GET['projectname'] ?? '';

$userId = $_GET['user'] ?? 0;
$utenteTimesheet = $_utente;

$_pagina = new Pagina('Timesheet', PAGINA_RISERVATA_UTENTE);

if ($userId) {
    $utenteSelezionato = new Utente();
    if (!$utenteSelezionato->ottieniDaId($userId)) {
        $_pagina->messaggi[] = new MessaggioErrore('Utente non trovato');
    } else {
        $utenteTimesheet = $utenteSelezionato;
        unset($utenteSelezionato);
    }
}

if ($utenteTimesheet->getFullName()) {
    $titolo = 'Timesheet di ' . htmlspecialchars($utenteTimesheet->getFullName());
}

$timesheetDate = '';

if ($dateStart or $dateEnd) {
    if ($dateStart == $dateEnd) {
        $titolo .= ' del ' . $dateStart;
        $timesheetDate = $dateEnd;
    } else {
        if ($dateStart) {
            $titolo .= ' dal ' . $dateStart;
        }

        if ($dateEnd) {
            $titolo .= ' fino al ' . $dateEnd;
        }
    }
}

$timesheet = new Timesheet();

$timesheet->setUserId($utenteTimesheet->getId());
$timesheet->setDateStart($dateStart);
$timesheet->setDateEnd($dateEnd);
$timesheet->setProjectId($projectId);
$timesheet->setProjectName($projectName);

if (isset($_POST['delete-task-single']) and $taskToDelete = intval($_POST['delete-task-single'])) {
    $timesheet->deleteTasksByIds([$taskToDelete => $taskToDelete]);
}

$timesheet->loadTimesheet();

$formTimesheet = new Campi(FORM_TIMESHEET_PERSONAL);

$formTimesheet->campi['datestart']=new Campo('datestart', 'Data inizio', TIPO_STRINGA, array('obbligatorio'=>false, 'default' => $dateStart));
$formTimesheet->campi['dateend']=new Campo('dateend', 'Data fine', TIPO_STRINGA, array('obbligatorio'=>false, 'default' => $dateEnd));
$formTimesheet->campi['projectname']=new Campo('projectname', 'Progetto', TIPO_STRINGA, array('default' => $projectName));

$i = 1;

foreach ($timesheet->getTasks() as $task) {
    $timeSpentInReadableFormat = TimeLibrary::getTimeInReadableFormatByMinutes($task->getMinutesSpent());
    $formTimesheet->campi['timesheet-task-' . $i . '-project'] = new Campo('timesheet-task-' . $i . '-project', 'Progetto', TIPO_INTERO, array('valori' => $progetti->listaOpzioni,'default' => $task->getProjectId(), 'obbligatorio'=>false));
    $formTimesheet->campi['timesheet-task-' . $i . '-description'] = new Campo('timesheet-task-' . $i . '-description', 'Descrizione', TIPO_STRINGA, array('default' => $task->getDescription(), 'obbligatorio'=>false));
    $formTimesheet->campi['timesheet-task-' . $i . '-time'] = new Campo('timesheet-task-' . $i . '-time', 'Tempo', TIPO_STRINGA, array('default' => $timeSpentInReadableFormat, 'obbligatorio'=>false));

    if (!$timesheetDate) {
        $formTimesheet->campi['timesheet-task-' . $i . '-date'] = new Campo('timesheet-task-' . $i . '-date', 'Data', TIPO_DATA, array('default' => $task->getDate(), 'obbligatorio' => false));
    }

    $i++;
}

$totalTimesheetRows = 0;

if (isset($_POST['add-rows']) and $additionalRows = (int)$_POST['add-rows']) {
    $totalTimesheetRows = count($timesheet->getTasks()) + $additionalRows;
} else {
    $totalTimesheetRows = count($timesheet->getTasks()) + 1;
}

$newTasks = [];

for ($j = count($timesheet->getTasks()) + 1; $j <= $totalTimesheetRows; $j++) {
    $newTask = new TimesheetTask();

    $project = null;
    $description = null;
    $time = null;

    if (isset($_POST['salva']) and empty($_POST['delete-tasks']) and empty($_POST['delete-task-single'])) {
        $project = (int)($_POST['timesheet-task-' . $j . '-project'] ?? 1);
        $description = $_POST['timesheet-task-' . $j . '-description'] ?? '';
        $time = $_POST['timesheet-task-' . $j . '-time'] ?? '';
    }

    if ($project) {
        $newTask->setProjectId($project);
        $newTask->canAddTimeOnThisTask();

        if ($newTask->getCanAddTimeOnThisTask() == false) {
            $errorMessage = 'Hai inserito delle ore per un progetto che non ha copertura oraria: ' . $progetti->ottieniRecordSingolo($project)->getName() . '. Perfavore segnalalo al commerciale.';
            $_pagina->messaggi[] = new MessaggioAvviso($errorMessage);
        }
    }

    if ($description) {
        $newTask->setDescription($description);
    }

    if (!$time or !TimeLibrary::isTimeFormatValid($time)) {
        $newTask->setTime($newTask->returnDefaultTimeValue());
    } else {
        $newTask->setTime($time);
    }

    $formTimesheet->campi['timesheet-task-' . $j . '-project'] = new Campo('timesheet-task-' . $j . '-project', 'Progetto', TIPO_INTERO, array('valori'=>$progetti->listaOpzioni, 'default' => $newTask->getProjectId(), 'obbligatorio' => false));
    $formTimesheet->campi['timesheet-task-' . $j . '-description'] = new Campo('timesheet-task-' . $j . '-description', 'Descrizione', TIPO_STRINGA, array('default' => $newTask->getDescription(), 'obbligatorio' => false));
    $formTimesheet->campi['timesheet-task-' . $j . '-time'] = new Campo('timesheet-task-' . $j . '-time', 'Tempo', TIPO_STRINGA, array('default' => $newTask->getTime(), 'obbligatorio' => false));

    if (!$timesheetDate) {
        $formTimesheet->campi['timesheet-task-' . $j . '-date'] = new Campo('timesheet-task-' . $j . '-date', 'Data', TIPO_DATA, array('default' => ($newTask->getDate() ?: $dateEnd), 'obbligatorio' => false));
    }

    $newTasks[$j] = $newTask;
    unset($newTask);
}

if (isset($_POST['salva'])) {
    if ($formTimesheet->controllaValori()) {
        $righeTimesheetInviate = $_POST['timesheet-rows'] ?? 0;

        if (!$righeTimesheetInviate) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nella computazione delle righe del timesheet');
        } else {
            if (empty($_POST['delete-tasks'])) {
                $timesheet->updateUserTimesheet();
            }
        }
        if (!empty($_POST['delete-tasks']) and $tasksToDelete = $_POST['delete-tasks']) {
            $timesheet->deleteTasksByIds($tasksToDelete);
        }

        $timesheet->loadTimesheet();
    }
}

$totalWorkedHoursForThisPeriod = Timesheet::getTotalTimeWorkedOnPeriodForUser($utenteTimesheet->getId(), $dateStart, $dateEnd);

$_pagina->creaTesta();
?>

<script type="text/javascript">
$(function() {
    $('.timesheet-column-time-buttons').each(function() {
        let row = $(this).parents('.timesheet-row');
        let rowId = $(row).attr('data-row-id');
        let htmlButtons = '<button type="button" onclick="increaseTimeByMinutes(' + rowId + ', 5);" class="btn btn-only-icon btn-timecontrol" title="Aumenta di 5 minuti">+5m</button>' +
        '<button type="button" onclick="decreaseTimeByMinutes(' + rowId + ', 5);" class="btn btn-only-icon btn-timecontrol" title="Diminuisci di 5 minuti">-5m</button>' +
        '<button type="button" onclick="increaseTimeByMinutes(' + rowId + ', 15);" class="btn btn-only-icon btn-timecontrol" title="Aumenta di 15 minuti">+15m</button>' +
        '<button type="button" onclick="decreaseTimeByMinutes(' + rowId + ', 15);" class="btn btn-only-icon btn-timecontrol" title="Diminuisci di 15 minuti">-15m</button>' +
        '<button type="button" onclick="increaseTimeByHours(' + rowId + ', 1);" class="btn btn-only-icon btn-timecontrol" title="Aumenta di 1 ora">+1h</button>' +
        '<button type="button" onclick="decreaseTimeByHours(' + rowId + ', 1);" class="btn btn-only-icon btn-timecontrol" title="Diminuisci di 1 ora">-1h</button>';
        $(row).find('.timesheet-column-time-buttons').append(htmlButtons);
    });

    $('.timesheet-row select').chosen();
});

function getTimeHoursAndMinutesObjectFromString(timeString)
{
    let isNegative = (timeString.substring(0, 1) == '-');

    if (isNegative) {
        timeString = timeString.substr(1, timeString.length);
    }

    let timeObject = timeString.split(':');
    let timeHours = parseInt(timeObject[0]);
    let timeMinutes = parseInt(timeObject[1]);

    return {'hours' : timeHours, 'minutes' : timeMinutes, 'isNegative' : isNegative};
}

function getTimeHoursAndMinutesObjectFromField(field)
{
    let input = $(field);
    let timeString = $(input).val();

    return getTimeHoursAndMinutesObjectFromString(timeString);
}

function getTimeStringByMinutes(minutes)
{
    let timeString = '';

    let timeHours = Math.floor(Math.abs(minutes) / 60);
    let timeMinutes = Math.abs(minutes) % 60;

    if (timeHours < 10) {
        timeHours = '0' + timeHours;
    }

    if (timeMinutes < 10) {
        timeMinutes = '0' + timeMinutes;
    }

    timeString = timeHours + ':' + timeMinutes;

    if (minutes < 0) {
        timeString = '-' + timeString;
    }

    return timeString;
}

function convertTimeObjectToMinutes(timeObject)
{
    let hours = parseInt(timeObject.hours);
    let minutes = parseInt(timeObject.minutes);

    minutes = minutes + (hours * 60);

    if (timeObject.isNegative) {
        minutes = -Math.abs(minutes);
    }

    return minutes;
}

function increaseTimeByMinutes(row, minutes)
{
    let actualTimeObject = getTimeHoursAndMinutesObjectFromField('#timesheet-task-' + row + '-time');
    let actualTimeInMinutes = convertTimeObjectToMinutes(actualTimeObject);

    let newTimeInMinutes = actualTimeInMinutes + minutes;

    let newTimeString = getTimeStringByMinutes(newTimeInMinutes);

    return updateTimeFieldValue('#timesheet-task-' + row + '-time', newTimeString);
}

function increaseTimeByHours(row, hours)
{
    return increaseTimeByMinutes(row, hours * 60);
}

function decreaseTimeByMinutes(row, minutes)
{
    let actualTimeObject = getTimeHoursAndMinutesObjectFromField('#timesheet-task-' + row + '-time');
    let actualTimeInMinutes = convertTimeObjectToMinutes(actualTimeObject);

    let newTimeInMinutes = actualTimeInMinutes + (-minutes);

    let newTimeString = getTimeStringByMinutes(newTimeInMinutes);

    return updateTimeFieldValue('#timesheet-task-' + row + '-time', newTimeString);
}


function decreaseTimeByHours(row, hours)
{
    return decreaseTimeByMinutes(row, hours * 60);
}

function updateTimeFieldValue(field, newValue)
{
    $(field).val(newValue);
    return true;
}

function emptyRowFields(rowSelector)
{
    $(rowSelector).find('input[type="text"]').val('');
    $(rowSelector).find('select').val('');
    return true;
}
</script>

<div class="container">
    <?php if ($_utente->getLevel() >= PAGINA_RISERVATA_SUPERADMIN):?>
        <form id="form-timesheet-filtra" action="<?=$_SERVER['PHP_SELF']?>" method="get">
            <h2>Filtra timesheet per:</h2>
            <div class="clearfix">
                <div class="campo col-m-3 campo-select">
                    <h4><label for="form-timesheet-filtra-utente">Utente</label></h4>
                    <div class="campo-html">
                        <select id="form-timesheet-filtra-utente" class="" name="user">
                            <option value="">Selezionare</option>
                        <?php if (!empty($utentiAttivi)):?>
                            <optgroup label="Utenti attivati">
                            <?php foreach ($utentiAttivi as $idUtente => $utente):?>
                                <option value="<?=$idUtente?>"<?=($idUtente == $utenteTimesheet->getId() ? ' selected="selected"' : '')?>><?=htmlspecialchars($utente)?></option>
                            <?php endforeach?>
                            </optgroup>
                        <?php endif?>

                        <?php if (!empty($utentiInattivi)):?>
                            <optgroup label="Utenti disattivati">
                            <?php foreach ($utentiInattivi as $idUtente => $utente):?>
                                <option value="<?=$idUtente?>"<?=($idUtente == $utenteTimesheet->getId() ? ' selected="selected"' : '')?>><?=htmlspecialchars($utente)?></option>
                            <?php endforeach?>
                            </optgroup>
                        <?php endif?>
                        </select>
                    </div>
                </div>
                <?php
                $formTimesheet->creaCampoDivCustom('datestart', CAMPO_INPUTTEXT, 'col-m-3');
                $formTimesheet->creaCampoDivCustom('dateend', CAMPO_INPUTTEXT, 'col-m-3');
                $formTimesheet->creaCampoDivCustom('projectname', CAMPO_INPUTTEXT, 'col-m-3');
                ?>
            </div>
            <label><input type="checkbox" value="t" name="calculate-costs"<?=((isset($_GET['calculate-costs']) and $_GET['calculate-costs'] == 't') ? 'checked="checked"' : '')?>> Calcola costi</label>
            <input type="hidden" value="<?=$formTimesheet->formID?>" name="formID" />
            <div class="campi-bottoni">
                <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
                <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
            </div>
        </form>
    <?php endif?>

    <h2><?=$titolo?></h2>

    <?php $httpGetData = http_build_query($_GET, '', '&amp;');?>

    <form  id="form-timesheet-ore" action="<?=$_SERVER['PHP_SELF'] . ($httpGetData ? '?' . $httpGetData : '')?>" method="post">
        <?php if (!empty($formTimesheet->err)):?>
            <div class="form-errori">
            <?php foreach ($formTimesheet->err as $error):?>
                <div class="messaggio errore">
                    <p><?=$error?></p>
                </div>
            <?php endforeach?>
            </div>
        <?php endif?>

        <div id="timesheet-table-header" class="grid <?=($timesheetDate ? 'grid-6-cols' : 'grid-7-cols')?> timesheet-row" data-row-id="<?=$i?>">
            <div class="timesheet-column timesheet-column-deleterow">
                <h4>X</h4>
            </div>
            <div class="timesheet-column timesheet-column-status">
                <span></span>
            </div>
            <?php if (!$timesheetDate):?>
            <div class="timesheet-column">
                <h4>Data</h4>
            </div>
            <?php endif?>
            <div class="timesheet-column">
                <h4>Progetto</h4>
            </div>
            <div class="timesheet-column">
                <h4>Descrizione</h4>
            </div>
            <div class="timesheet-column">
                <h4>Tempo</h4>
            </div>
            <div class="timesheet-column">
                <h4>Azioni</h4>
            </div>
        </div>

        <div id="timesheet-table-body">
            <?php
            $i = 1;
            $dataTaskCorrente = '';
            $dataTaskPrecedente = '';
            foreach ($timesheet->getTasks() as $task):
                $dataTaskCorrente = $task->getDate();
                if ($dataTaskPrecedente != $dataTaskCorrente):
                    if ($i > 1):
                        if ($dataTaskPrecedente and isset($totalWorkedHoursForThisPeriod[$dataTaskPrecedente])):?>
                        <div class="timesheet-row-day-total">
                            Totale giorno: <?=$totalWorkedHoursForThisPeriod[$dataTaskPrecedente]?>
                        </div>
                        <?php
                        endif;
                        ?>
                    </div>
                </div>
                    <?php
                    endif;
                    ?>
                <div class="timesheet-rowgroup-samedate">
                    <span><?=TimeLibrary::getDayOfWeekAsStringFromNumber($task->getDayOfWeek()) . ' ' . $task->getDate()?></span>
                    <div class="timesheet-rowgroup-samedate-tasks">
                    <?php
                    endif;
                    ?>

                    <div id="timesheet-row-<?=$i?>" class="grid <?=($timesheetDate ? 'grid-6-cols' : 'grid-7-cols')?> timesheet-row<?=($task->getCanAddTimeOnThisTask() ? '' : ' timesheet-row-alert-warning')?>" data-row-id="<?=$i?>">
                        <div class="timesheet-column timesheet-column-checkbox">
                            <label>
                                <input type="checkbox" name="delete-tasks[]" value="<?=$task->getId()?>" />
                            </label>
                        </div>
                        <div class="timesheet-column timesheet-column-status">
                            <span class="icon <?=($task->getCanAddTimeOnThisTask() ? 'icon-ok' : 'icon-warning')?>" title="<?=($task->getCanAddTimeOnThisTask() ? 'Puoi inserire ore in questo progetto' : 'Questo progetto non ha più copertura oraria')?>">
                            </span>
                        </div>
                        <?php if (!$timesheetDate):?>
                        <div class="timesheet-column timesheet-column-date">
                            <?=$formTimesheet->creaCampoDivCustom('timesheet-task-' . $i . '-date', CAMPO_INPUTTEXT, 'timesheet-field');?>
                        </div>
                        <?php endif?>
                        <div class="timesheet-column timesheet-column-project">
                            <?=$formTimesheet->creaCampoDivCustom('timesheet-task-' . $i . '-project', CAMPO_SELECT, 'timesheet-field', null, 'Selezionare');?>
                        </div>
                        <div class="timesheet-column timesheet-column-description">
                            <?=$formTimesheet->creaCampoDivCustom('timesheet-task-' . $i . '-description', CAMPO_INPUTTEXT, 'timesheet-field');?>
                        </div>
                        <div class="timesheet-column timesheet-column-time">
                            <?=$formTimesheet->creaCampoDivCustom('timesheet-task-' . $i . '-time', CAMPO_INPUTTEXT, 'timesheet-field');?>
                        </div>
                        <div class="timesheet-column-time-buttons">
                            <input type="hidden" name="timesheet-task-<?=$i?>-id" value="<?=$task->getId()?>">
                            <input type="submit" name="delete-task-single" value="<?=$task->getId()?>" title="Elimina riga" onclick="return confirm('Eliminare veramente?');" class="btn btn-only-icon btn-delete">
                            <button type="button" onclick="return emptyRowFields('#timesheet-row-<?=$i?>')" title="Svuota riga" class="btn btn-only-icon btn-reset"></button>
                        </div>
                    </div>

                    <?php
                    if ($i == count($timesheet->getTasks())):
                        if (isset($totalWorkedHoursForThisPeriod[$dataTaskCorrente])):?>
                        <div class="timesheet-row-day-total">
                            Totale giorno: <?=$totalWorkedHoursForThisPeriod[$task->getDate()]?>
                        </div>
                        <?php
                        endif;
                    ?>
                    </div>
                </div>
                <?php
                endif;

                $dataTaskPrecedente = $dataTaskCorrente;

                $i++;

            endforeach;
            ?>
        </div>

        <div id="timesheet-table-foot">
            <?php foreach ($newTasks as $j => $newTask):?>
                <div id="timesheet-row-<?=$j?>" class="grid <?=($timesheetDate ? 'grid-6-cols' : 'grid-7-cols')?> timesheet-row<?=($newTask->getCanAddTimeOnThisTask() ? '' : ' timesheet-row-alert-warning')?>" data-row-id="<?=$j?>">
                    <div class="timesheet-column timesheet-column-empty">
                    </div>
                    <div class="timesheet-column timesheet-column-empty">
                    </div>
                    <?php if (!$timesheetDate):?>
                    <div class="timesheet-column timesheet-column-date">
                        <?=$formTimesheet->creaCampoDivCustom('timesheet-task-' . $j . '-date', CAMPO_INPUTTEXT, 'timesheet-field');?>
                    </div>
                    <?php endif?>
                    <div class="timesheet-column timesheet-column-project">
                        <?=$formTimesheet->creaCampoDivCustom('timesheet-task-' . $j . '-project', CAMPO_SELECT, 'timesheet-field', null, 'Selezionare');?>
                    </div>
                    <div class="timesheet-column timesheet-column-description">
                        <?=$formTimesheet->creaCampoDivCustom('timesheet-task-' . $j . '-description', CAMPO_INPUTTEXT, 'timesheet-field');?>
                    </div>
                    <div class="timesheet-column timesheet-column-time">
                        <?=$formTimesheet->creaCampoDivCustom('timesheet-task-' . $j . '-time', CAMPO_INPUTTEXT, 'timesheet-field');?>
                    </div>
                    <div class="timesheet-column-time-buttons">
                        <button type="button" onclick="return emptyRowFields('#timesheet-row-<?=$j?>')" title="Svuota riga" class="btn btn-only-icon btn-reset"></button>
                    </div>
                </div>
            <?php endforeach;?>
        </div>

        <div class="timesheet-buttons">
            <input type="hidden" name="timesheet-rows" value="<?=$totalTimesheetRows?>">
            <input type="submit" name="salva" value="Salva" class="btn btn-save" />
            <a href="<?=$_SERVER['PHP_SELF']?>?data=<?=$dataTimesheet?>" class="btn btn-annulla">Azzera</a>
        </div>
    </form>
</div>

<?php
$_pagina->creaFooter();
