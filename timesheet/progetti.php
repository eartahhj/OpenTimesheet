<?php
require_once '../funzioni/funzioni.php';
require_once '../funzioni/controlli.php';
require_once '../funzioni/time-library.php';
require_once '../funzioni/classi/clienti.php';
require_once '../funzioni/classi/progetti.php';

$_pagina = new Pagina('Timesheet - Progetti', PAGINA_RISERVATA_UTENTE);

$_utente = new Utente();
$_utente->loadFromCurrentSession();

$progetti = new Progetti();
$progetti->ottieniRisultatiFiltrati();

$clienti = new Clienti();
$clienti->ottieniListaOpzioni();

$progettoSelezionato = $_GET['progetto'] ?? 0;

$progetto = null;

$form=new Campi(FORM_FILTRA_PROGETTI);
$form->campi['name']=new Campo('name', 'Progetto', TIPO_STRINGA);
$form->campi['cliente']=new Campo('cliente', 'Cliente', TIPO_INTERO, array('valori'=>$clienti->listaOpzioni));
$form->campi['stato']=new Campo('stato', 'Stato', TIPO_INTERO, array('valori'=>$progetti::$statiProgetto, 'default' => Progetti::STATO_ABILITATI));
$form->campi['risultatiPerPagina']=new Campo('risultatiPerPagina', 'Risultati per pagina', TIPO_INTERO, array('valori'=>$_config['risultatiPerPaginaAmmessi']));

if (isset($_REQUEST['name']) and $_REQUEST['name']) {
    $form->campi['name']->default = $_REQUEST['name'];
}
if (isset($_REQUEST['cliente']) and $_REQUEST['cliente']) {
    $form->campi['cliente']->default = (int)$_REQUEST['cliente'];
}
if (isset($_REQUEST['risultatiPerPagina']) and $_REQUEST['risultatiPerPagina']) {
    $form->campi['risultatiPerPagina']->default = (int)$_REQUEST['risultatiPerPagina'];
}
if (isset($_REQUEST['stato']) and $_REQUEST['stato']) {
    $form->campi['stato']->default = (int)$_REQUEST['stato'];
}

$_pagina->creaTesta();
?>
<div class="container">
    <form id="form-progetti-filtra" action="<?=$_SERVER['PHP_SELF']?>" method="get" class="clearfix form-inline">
        <h2>Filtra progetti per:</h2>
        <div class="grid">
            <?php
            $form->creaCampoDiv('name', CAMPO_INPUTTEXT);
            $form->creaCampoDiv('cliente', CAMPO_SELECT, null, 'Selezionare');
            $form->creaCampoDiv('stato', CAMPO_SELECT, null, 'Selezionare');
            $form->creaCampoDiv('risultatiPerPagina', CAMPO_SELECT);
            ?>
        </div>
        <input type="hidden" value="<?=$form->formID?>" name="formID" />
        <div class="campi-bottoni">
            <input type="submit" name="filtra" value="Cerca" class="btn btn-search" />
            <a href="<?=$_SERVER['PHP_SELF']?>" class="btn btn-annulla">Azzera</a>
            <a href="progetto.php?azione=crea" class="btn btn-nuovo">Nuovo progetto</a>
        </div>
    </form>
</div>

<?php
echo $progetti->buildHtml();
?>

<?php
$_pagina->creaFooter();
?>
