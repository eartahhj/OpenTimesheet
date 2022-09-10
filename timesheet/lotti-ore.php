<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/time-library.php';
require_once '../funzioni/classi/lotti-ore.php';
require_once '../funzioni/classi/progetti.php';

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$_pagina = new Pagina('Timesheet - Lotti Ore', PAGINA_RISERVATA_UTENTE);
$_pagina->jsOnLoad .= "$('.chosen').chosen();";

$lottiOre = new LottiOre();
$lottiOre->ottieniRisultatiFiltrati();

$progetti = new Progetti();
$progetti->ottieniListaOpzioni();

$lottoOre = null;
$lottoOreSelezionato = $_GET['id'] ?? 0;

$totaleOreProgetto = '';

$form = new Campi(FORM_FILTRA_LOTTI_ORE);
$form->campi['titolo'] = new Campo('titolo', 'Titolo', TIPO_STRINGA);
$form->campi['progetto'] = new Campo('progetto', 'Progetto', TIPO_INTERO, array('valori'=>$progetti->listaOpzioni));
$form->campi['stato'] = new Campo('stato', 'Stato', TIPO_INTERO, array('valori'=>LottiOre::$stati, 'default' => LottiOre::STATO_ATTIVI));
$form->campi['risultatiPerPagina'] = new Campo('risultatiPerPagina', 'Risultati per pagina', TIPO_INTERO, array('valori'=>$_config['risultatiPerPaginaAmmessi']));

if (isset($_REQUEST['titolo']) and $_REQUEST['titolo']) {
    $form->campi['titolo']->default = $_REQUEST['titolo'];
}
if (isset($_REQUEST['progetto']) and $_REQUEST['progetto']) {
    $form->campi['progetto']->default = (int)$_REQUEST['progetto'];

    $totaleOreProgetto = LottiOre::getTotalHoursForProject((int)$_REQUEST['progetto']);
}
if (isset($_REQUEST['risultatiPerPagina']) and $_REQUEST['risultatiPerPagina']) {
    $form->campi['risultatiPerPagina']->default = (int)$_REQUEST['risultatiPerPagina'];
}
if (isset($_REQUEST['stato']) and $_REQUEST['stato']) {
    $form->campi['stato']->default = $_REQUEST['stato'];
}

$_pagina->creaTesta();
?>
<div class="container">
    <form id="form-lottiore-filtra" action="<?=$_SERVER['PHP_SELF']?>" method="get" class="clearfix form-inline">
        <h2>Filtra lotti ore per:</h2>
        <div class="grid">
            <?php
            $form->creaCampoDivCustom('titolo', CAMPO_INPUTTEXT, 'grid-col');
            $form->creaCampoDivCustom('progetto', CAMPO_SELECT, 'grid-col', null, 'Selezionare');
            $form->creaCampoDivCustom('stato', CAMPO_SELECT, 'grid-col', null, 'Selezionare');
            $form->creaCampoDivCustom('risultatiPerPagina', CAMPO_SELECT, 'grid-col');
            ?>
        </div>
        <input type="hidden" value="<?=$form->formID?>" name="formID" />
        <div class="campi-bottoni">
            <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
            <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
            <a href="progetto.php?azione=crea" class="btn btn-nuovo">Nuovo lotto ore</a>
        </div>
    </form>
</div>

<?php
if ($totaleOreProgetto):
?>
<div class="container">
    <p>Totale lotti ore emessi per questo progetto: <strong><?=$totaleOreProgetto?></strong></p>
</div>
<?php
endif;

echo $lottiOre->buildHtml();
?>

<?php
$_pagina->creaFooter();
?>
