<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/time-library.php';
require_once '../funzioni/classi/clienti.php';
require_once '../funzioni/classi/progetti.php';
require_once '../funzioni/classi/fatture.php';
require_once '../funzioni/classi/lotti-ore.php';

$_pagina = new Pagina('Timesheet - Progetto', PAGINA_RISERVATA_UTENTE);
$_pagina->messaggi[] = new MessaggioInfo('Attenzione: questa pagina è ancora in fase di test, per ora limitarsi alla visualizzazione di dati senza inserire/modificare');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$progetti = new Progetti();
$progetti->ottieniRisultatiFiltrati();

$clienti = new Clienti();
$clienti->ottieniListaOpzioni();

$progettoSelezionato = $_REQUEST['id'] ?? 0;

$progetto = null;

$azione = 'crea';

if (isset($_GET) and isset($_GET['azione'])) {
    if ($_GET['azione'] == 'modifica') {
        $azione='modifica';
    }
}

if ($progettoSelezionato) {
    $progetto = new Progetto($progettoSelezionato);
    $progetto->loadLottiOre();

    $fatture = new FattureProgetti();
    $fatture->setWhere('progetto = ' . $progetto->getId());
    $fatture->ottieniRecordTutti();
} else {
    $progetto = new Progetto();
}

if ($progetto->getId() and $azione != 'modifica') {
    $azione = 'visualizza';
}

$clienteSelezionato = 0;
if (isset($_GET['cliente']) and $_GET['cliente']) {
    $clienteSelezionato = (int)$_GET['cliente'];
}

$nameDefault = '';
if ($progetto->getName()) {
    $nameDefault = htmlspecialchars($progetto->getName());
}

if ($azione=='modifica' or $azione=='crea') {
    $form=new Campi(FORM_MODIFICA_PROGETTO);
    $form->campi['nome']=new Campo('nome', 'Nome progetto', TIPO_STRINGA, array('obbligatorio'=>true, 'default' => $nameDefault));
    $form->campi['cliente']=new Campo('cliente', 'Cliente', TIPO_INTERO, array('valori'=>$clienti->listaOpzioni,'default'=>$clienteSelezionato, 'obbligatorio'=>true));
    $form->campi['descr']=new Campo('descr', 'Descrizione', TIPO_STRINGA);
    $form->campi['stato']=new Campo('stato', 'Stato', TIPO_STRINGA);
    $form->campi['abil']=new Campo('abil', 'Attivo', TIPO_BOOLEANO);
    $form->campi['finito']=new Campo('finito', 'Finito', TIPO_BOOLEANO);
    $form->campi['costo']=new Campo('costo', 'Costo', TIPO_INTERO);
    $form->campi['ore']=new Campo('ore', 'Ore fatturate', TIPO_INTERO);
    $form->campi['costi_esterni']=new Campo('costi_esterni', 'Costi esterni', TIPO_INTERO);
    $form->campi['costi_esterni_note']=new Campo('costi_esterni_note', 'Note sui costi esterni', TIPO_STRINGA);
    $form->campi['fatturare']=new Campo('fatturare', 'Da fatturare', TIPO_BOOLEANO);
    $form->campi['tipologia']=new Campo('tipologia', 'Tipologia', TIPO_INTERO, ['valori' => Progetti::getTipologie()]);
    $form->campi['alert_attivo']=new Campo('alert_attivo', 'Alert Ore', TIPO_BOOLEANO);
}

if (isset($_POST['crea'])) {
    $trovatiNomiProgettoUguali=$progetto->controllaSeNomeProgettoEsiste(
        $_database->escapeLiteral($form->campi['nome']->valore)
    );

    if ($trovatiNomiProgettoUguali) {
        $_pagina->messaggi[] = new MessaggioInfo('Avviso: sono stati trovati altri progetti con lo stesso nome.');
    }

    if (!$id=$_database->generaNuovoIDTabella($progetto->getDbTable())) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del progetto. ID non settato.');
    } else {
        if (!$form->controllaValori()) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del progetto.');
        } else {
            if (!$progetto->crea($form->valoriDB)) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del progetto.');
            } else {
                $_pagina->messaggi[]=new MessaggioConferma('Progetto creato');
                $progetto=new Progetto($id);
            }
        }
    }

} elseif (isset($_POST['salva'])) {
    if (!$form->controllaValori()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del progetto.');
    } else {
        if (!$progetto->aggiorna($form->valoriDB)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'aggiornamento del progetto.');
        } else {
            $progetto=new Progetto($progetto->getId());
            if ($progetto->cliente and $progetto->cliente->getId()) {
                $progetto->cliente=new AnagraficaCliente($progetto->cliente->getId());
            }
            $_pagina->title='Progetto '.htmlspecialchars($progetto->nome);
            $_pagina->messaggi[]=new MessaggioConferma('Progetto aggiornato');
        }
    }
} elseif (isset($_POST['elimina'])) {
    if (!$progetto->elimina()) {
        $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'eliminazione del progetto.');
    } else {
        $_pagina->messaggi[]=new MessaggioConferma('Progetto eliminato.');
        $progetto=new Progetto();
    }
}

$_pagina->creaTesta();
?>
<section class="container pagina-progetto">
<?php
if ($azione=='visualizza'):
?>
<h2>Progetto: <?=$progetto->getName()?></h2>
<div id="visualizzazione-dati">
    <div class="clearfix">
        <?php if ($progetto->getCustomer()):?>
            <div class="col-l-4">
                <h4>Cliente</h4>
                <p><a href="<?=Config::$basePath?>cliente.php?id=<?=($progetto->getCustomer()->getId())?>"><?=$progetto->getCustomer()->nomeAzienda?></a></p>
            </div>
        <?php endif?>
        <div class="col-l-4">
            <h4>Creazione record</h4>
            <p><?=$progetto->getDateCreated()?></p>
        </div>
        <div class="col-l-4">
            <h4>Ultima modifica</h4>
            <p><?=$progetto->getDateModified()?></p>
        </div>
    </div>
    <p>
        <a class="btn btn-modifica" href="<?=$_SERVER['PHP_SELF']?>?id=<?=$progetto->getId()?>&amp;azione=modifica">Modifica progetto</a>
        <a class="btn btn-report" href="progetto-report.php?progetto=<?=$progetto->getId()?>">Report progetto</a>
    </p>

    <div id="lista-lottiore-associati">
        <?php
        if (!$lottiOre = $progetto->getLottiOre()) {
            echo "<h4>Nessun lotto ore associato a questo progetto</h4>\n";
        } else {
            echo '<h4>' . count($lottiOre).' lott'.(count($lottiOre)==1?'o associato':'i associati')." a {$progetto->getName()}:</h4>\n";
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Lotto ore</th>
                    <th>Numero di ore</th>
                    <th>Fattura di riferimento</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lottiOre as $lottoOre): ?>

                    <tr>
                        <td><a href="lotto-ore.php?id=<?=$lottoOre->getId()?>"><?=$lottoOre->getTitle()?></a></td>
                        <td><?=$lottoOre->getNumberOfHours()?></td>
                        <td>
                            <?php if ($lottoOre->getFatturaId()):?>
                                <a href="/crm/fattura.php?id=<?=$lottoOre->getFatturaId()?>">
                                    <?=htmlspecialchars($lottoOre->getFattura()->titolo)?>
                                </a>
                            <?php endif?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    ?>
        <p><a href="lotto-ore.php?progetto=<?=$progetto->getId()?>" class="btn btn-nuovo">Aggiungi lotto ore</a></p>
    </div>

    <div id="lista-fatture-associate">
        <?php
        if (empty($fatture->lista)) {
            echo "<h4>Nessuna fattura associata a questo progetto</h4>\n";
        } else {
            echo '<h4>' . count($fatture->lista).' fattur'.(count($fatture->lista)==1?'a associata':'e associate')." a {$progetto->getName()}:</h4>\n";
        ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Data di emissione</th>
                    <th>Importo</th>
                    <th>Titolo</th>
                    <th>Codice</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($fatture->lista as $fattura): ?>

                    <tr>
                        <td><?=$fattura->getDataEmissione()?></td>
                        <td>&euro; <?=$fattura->getImporto()?></td>
                        <td>
                            <?php if ($fattura->titolo):?>
                                <a href="/crm/fattura.php?id=<?=$fattura->getId()?>">
                                    <?=htmlspecialchars($fattura->titolo)?>
                                </a>
                            <?php endif?>
                        </td>
                        <td><?=htmlspecialchars($fattura->codice)?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    ?>
        <p><a href="/crm/fattura.php?progetto=<?=$progetto->getId()?>" class="btn btn-nuovo">Aggiungi fattura</a></p>
    </div>
</div>
<?php
endif;

if ($azione=='modifica' or $azione=='crea'): ?>
<h2><?=($azione=='modifica'?'Modifica del progetto '.$progetto->getName():'Creazione nuovo progetto')?></h2>
<?php
$parametriAction=[];
$parametriAction['id']=$progetto->getId();
$parametriAction['azione']=$azione;
$action=creaHttpQueryDaParametri($parametriAction);
?>
<form method="post" action="<?=$action?>" class="form-standard">
    <fieldset>
        <legend>Informazioni anagrafiche</legend>
        <?php
        $form->creaCampoDivCustom('nome', CAMPO_INPUTTEXT, 'col-m-6', $progetto->getRecord());
        $form->creaCampoDivCustom('cliente', CAMPO_SELECT, 'col-m-6', $progetto->getRecord(), 'Selezionare');
        $form->creaCampoDivCustom('descr', CAMPO_TEXTAREA, 'col-m-12', $progetto->getRecord());
        ?>
    </fieldset>
    <fieldset>
        <legend>Informazioni contabili</legend>
        <?php
        $form->creaCampoDivCustom('fatturare', CAMPO_RADIOSINO, 'col-m-12', $progetto->getRecord());
        $form->creaCampoDivCustom('ore', CAMPO_INPUTTEXT, 'col-m-4', $progetto->getRecord());
        $form->creaCampoDivCustom('costo', CAMPO_INPUTTEXT, 'col-m-4', $progetto->getRecord());
        $form->creaCampoDivCustom('costi_esterni', CAMPO_INPUTTEXT, 'col-m-4', $progetto->getRecord());
        $form->creaCampoDivCustom('costi_esterni_note', CAMPO_TEXTAREA, 'col-m-12', $progetto->getRecord());
        ?>
    </fieldset>
    <fieldset>
        <legend>Informazioni commerciali</legend>
        <?php
        $form->creaCampoDivCustom('abil', CAMPO_RADIOSINO, 'col-m-4', $progetto->getRecord());
        $form->creaCampoDivCustom('finito', CAMPO_RADIOSINO, 'col-m-4', $progetto->getRecord());
        $form->creaCampoDivCustom('alert_attivo', CAMPO_RADIOSINO, 'col-m-4', $progetto->getRecord(), 't', 'f');
        $form->creaCampoDivCustom('tipologia', CAMPO_SELECT, 'col-m-12', $progetto->getRecord(), 'Selezionare');
        $form->creaCampoDivCustom('stato', CAMPO_TEXTAREA, 'col-m-12', $progetto->getRecord());
        ?>
    </fieldset>
    <input type="hidden" name="formID" value="<?=$form->formID?>" />
    <div class="campi-bottoni">
    <a href="<?=$_SERVER['PHP_SELF'].($progetto->getId()?'?id='.$progetto->getId():'')?>" class="btn btn-annulla">Annulla modifiche</a>
    <input type="submit" name="<?=$progetto->getId()?'salva':'crea'?>" value="Salva" class="btn btn-save" />
    <?php if ($progetto->getId()): ?>
    <input type="submit" name="elimina" value="Elimina" class="btn btn-elimina" onClick="javascript:return confirm('Sei sicuro di voler eliminare questo progetto?');" />
    <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-nuovo">Nuovo progetto</a>
    </div>
    <?php endif; ?>
</form>
<?php endif;?>
</section>
<?php
$_pagina->creaFooter();
