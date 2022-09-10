<?php
require_once('funzioni/funzioni.php');

$_utente = new Utente();
$_utente->loadFromCurrentSession();

if (!empty($_utente) and $_utente->getId()) {
   $_utente->eliminaSessione();
}

$_pagina=new Pagina('Logout Area interna');

$_pagina->creaTesta();
?>
<div class="container">
   <h3>Logout eseguito.</h3>
   <ul>
       <li><a href="<?=$_config['cPath']?>/login.php">Effettua il login</a></li>
       <li><a href="<?=$_config['cPath']?>/index.php">Vai alla Homepage</a></li>
   </ul>
</div>
<?php
$_pagina->creaFooter();
