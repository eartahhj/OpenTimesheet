<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/time-library.php';
require_once '../funzioni/classi/lotti-ore.php';
require_once '../funzioni/classi/progetti.php';
require_once '../funzioni/classi/fatture.php';

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina = new Pagina('Lotto ore', PAGINA_RISERVATA_UTENTE);
$_pagina->jsOnLoad .= "$('.chosen').chosen();";

$progetti = new Progetti();
$progetti->ottieniListaOpzioni();

$fatture = new FattureProgetti();
$fatture->ottieniListaOpzioni();

$lottoOre = null;
$lottoOreSelezionato = $_REQUEST['id'] ?? 0;

$azione = 'crea';

if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione'] == 'modifica') {
        $azione='modifica';
    }
}

if ($lottoOreSelezionato) {
    $lottoOre = new LottoOre($lottoOreSelezionato);
    $lottoOre->loadAssociatedData();
} else {
    $lottoOre = new LottoOre();
}

if ($lottoOre->getId() and $azione != 'modifica') {
    $azione = 'visualizza';
}

$progettoSelezionato = 0;
if (isset($_GET['progetto']) and $_GET['progetto']) {
    $progettoSelezionato = (int)$_GET['progetto'];
}

$titleDefault = '';
if ($lottoOre->getTitle()) {
    $titleDefault = htmlspecialchars($lottoOre->getTitle());
}

if ($azione=='modifica' or $azione=='crea') {
    $form = new Campi(FORM_MODIFICA_LOTTO_ORE);
    $form->campi['titolo'] = new Campo('titolo', 'Titolo lotto ore', TIPO_STRINGA, array('obbligatorio'=>true, 'default' => $titleDefault));
    $form->campi['progetto'] = new Campo('progetto', 'Progetto', TIPO_INTERO, array('valori'=>$progetti->listaOpzioni,'default'=>$progettoSelezionato, 'obbligatorio'=>true, 'class' => 'chosen'));
    $form->campi['numero_ore'] = new Campo('numero_ore', 'Numero di ore', TIPO_STRINGA, ['obbligatorio' => true, 'default' => $lottoOre->getNumberOfHours()]);
    $form->campi['descrizione'] = new Campo('descrizione', 'Descrizione', TIPO_STRINGA, array('obbligatorio'=>false, 'default' => $titleDefault));
    $form->campi['fattura'] = new Campo('fattura', 'Fattura di riferimento', TIPO_STRINGA, array('obbligatorio'=>false, 'default' => $lottoOre->getFatturaId(), 'valori' => $fatture->listaOpzioni, 'class' => 'chosen'));
    $form->campi['attivo'] = new Campo('attivo', 'Attivo', TIPO_BOOLEANO, ['default' => 't']);
}

if (isset($_POST['crea'])) {
    if (!$id = $_database->generaNuovoIDTabella($lottoOre->getDbTable())) {
        $_pagina->messaggi[] = new MessaggioErrore('Errore nella creazione del lotto ore. ID non settato.');
    } else {
        if (!$form->controllaValori()) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nella creazione del lotto ore.');
        } else {
            if (!$lottoOre->crea($form->valoriDB)) {
                $_pagina->messaggi[] = new MessaggioErrore('Errore nella creazione del lotto ore.');
            } else {
                $_pagina->messaggi[] = new MessaggioConferma('Lotto ore creato');
                $lottoOre = new LottoOre($id);
            }
        }
    }

} elseif (isset($_POST['salva'])) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[] = new MessaggioErrore('Errore nell\'aggiornamento del lotto ore.');
    } else {
        if (!$lottoOre->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nell\'aggiornamento del lotto ore.');
        } else {
            $lottoOre=new LottoOre($lottoOre->getId());
            $_pagina->title='Lotto ore '.htmlspecialchars($lottoOre->getTitle());
            $_pagina->messaggi[] = new MessaggioConferma('Lotto ore aggiornato');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$lottoOre->elimina()) {
        $_pagina->messaggi[] = new MessaggioErrore('Errore nell\'eliminazione del lotto ore.');
    } else {
        $_pagina->messaggi[] = new MessaggioConferma('Lotto ore eliminato.');
        $lottoOre=new LottoOre();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-progetto">
<?php
if ($azione=='visualizza'):
?>
<h2>Progetto: <?=$lottoOre->getTitle()?></h2>
<div id="visualizzazione-dati" class="clearfix">
    <?php if ($progetto = $lottoOre->getProject()):?>
        <div class="col-l-4">
            <h4>Progetto</h4>
            <p><a href="<?=Config::$basePath?>timesheet/progetto.php?id=<?=($progetto->getId())?>"><?=htmlspecialchars($progetto->getName())?></a></p>
        </div>
    <?php endif?>
    <div class="col-l-4">
        <h4>Numero di ore</h4>
        <p><?=$lottoOre->getNumberOfHours()?></p>
    </div>
    <div class="col-l-4">
        <h4>Attivo</h4>
        <p><?=$lottoOre->isActive() ? 'Si' : 'No'?></p>
    </div>
    <div class="col-l-4">
        <h4>Creazione record</h4>
        <p><?=$lottoOre->getDateCreated()?></p>
    </div>
    <div class="col-l-4">
        <h4>Ultima modifica</h4>
        <p><?=$lottoOre->getDateModified()?></p>
    </div>
</div>
<a class="btn btn-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$lottoOre->getId()?>&amp;azione=modifica">Modifica lotto ore</a>
<a class="btn btn-report" href="progetto-report.php?id=<?=$lottoOre->getId()?>">Report progetto</a>
<?php
endif;

if ($azione=='modifica' or $azione=='crea'): ?>
<h2><?=($azione=='modifica'?'Modifica del lotto ore ' . $lottoOre->getTitle() : 'Creazione nuovo lotto ore')?></h2>
<?php
$parametriAction=[];
$parametriAction['id'] = $lottoOre->getId();
$parametriAction['azione'] = $azione;
$action = creaHttpQueryDaParametri($parametriAction);
?>
<form method="post" action="<?=$action?>" class="form-standard">
    <fieldset>
        <legend>Informazioni generali</legend>
        <?php
        $form->creaCampoDivCustom('titolo', CAMPO_INPUTTEXT, 'col-m-6', $lottoOre->getRecord());
        $form->creaCampoDivCustom('progetto', CAMPO_SELECT, 'col-m-6', $lottoOre->getRecord(), 'Selezionare');
        $form->creaCampoDivCustom('descrizione', CAMPO_TEXTAREA, 'col-m-12', $lottoOre->getRecord());
        ?>
    </fieldset>
    <fieldset>
        <legend>Informazioni contabili</legend>
        <?php
        $form->creaCampoDivCustom('numero_ore', CAMPO_INPUTTEXT, 'col-m-4', $lottoOre->getRecord());
        $form->creaCampoDivCustom('fattura', CAMPO_SELECT, 'col-m-4', $lottoOre->getRecord(), 'Selezionare');
        $form->creaCampoDivCustom('attivo', CAMPO_RADIOSINO, 'col-m-4', $lottoOre->getRecord());
        ?>
    </fieldset>
    <input type="hidden" name="formID" value="<?=$form->formID?>" />
    <div class="campi-bottoni">
    <a href="<?=$_SERVER['PHP_SELF'].($lottoOre->getId()?'?id=' . $lottoOre->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
    <input type="submit" name="<?=$lottoOre->getId()?'salva':'crea'?>" value="Salva" class="btn btn-save" />
    <?php if ($lottoOre->getId()): ?>
    <input type="submit" name="elimina" value="Elimina" class="btn btn-elimina" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo progetto?');" />
    <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-nuovo">Nuovo lotto ore</a>
    </div>
    <?php endif; ?>
</form>
<?php endif;?>
</section>
<?php
$_pagina->creaFooter();
