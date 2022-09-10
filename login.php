<?php
require_once 'funzioni/funzioni.php';
require_once 'funzioni/controlli.php';
require_once 'funzioni/classi/notifiche.php';

$_pagina = new Pagina('Login Area interna');

$_utente = new Utente();
$_utente->loadFromCurrentSession();
$_utente->loadRoles();

$ruoliUtente = $_utente->getRoles();

Notifiche::deleteOldReadNotifications();

if (isset($_POST['marcalette']) and !empty($_POST['notifiche_lette'])) {
    Notifiche::markNotificationsReadForUser($_POST['notifiche_lette'], $_utente->getId());
}

$notificheUtente = Notifiche::getNotificationsForUser($_utente->getId());

$username='';
$password='';

if (isset($_POST) and $_POST['formID']==FORM_LOGIN) {
    if (isset($_POST['username']) and $_POST['username']) {
        $username=strtolower($_POST['username']);
    }
    if (isset($_POST['password']) and $_POST['password']) {
        $password=$_POST['password'];
    }
    if ($username and $password) {
        $_utente = new Utente();
        if (!$_utente->loadFromUsername($username)) {
            unset($_utente);
            $_pagina->messaggi[]=new MessaggioErrore('Username o password errati');
        } else {
            $verificaPassword = verificaHashConSalt($password, $_utente->getPasswordHash(), $_utente->getPasswordSalt());
            if (!$verificaPassword) {
                unset($_utente);
                $_pagina->messaggi[]=new MessaggioErrore('Username o password errati');
            } else {
                $_utente->aggiornaUltimoAccesso();
            }
        }
    } else {
        $_pagina->messaggi[]=new MessaggioErrore('Inserire username e password');
    }
}

if (!$_utente->getId()) {
   $form=new Campi(FORM_LOGIN);
   $form->campi['username']=new Campo('username', 'Nome utente', TIPO_STRINGA, array('obbligatorio'=>true));
   $form->campi['password']=new Campo('password', 'Password', TIPO_STRINGA, array('obbligatorio'=>true));
}

$_pagina->creaTesta();
?>
<div class="container">
<?php if (!$_utente->getId()):?>
    <form action="<?=$_SERVER['PHP_SELF']?>" method="post" class="form-login">
        <?php
        $form->creaCampoDIV('username', CAMPO_INPUTTEXT);
        $form->creaCampoDIV('password', CAMPO_INPUTPASSWORD);
        ?>
        <input type="hidden" name="formID" value="<?=$form->formID?>" />
        <input type="submit" name="send" value="Login" />
    </form>
<?php else:?>
    <div id="dashboard-grid" class="grid">
        <div id="dashboard-welcome" class="dashboard-riquadro">
            <p>Nome utente: <?=$_utente->getUsername()?></p>
            <p>Ultimo accesso: <?=$_utente->getLastAccess()?></p>
        </div>
        <?php if (!empty($notificheUtente)):?>
        <div id="dashboard-notifications" class="dashboard-riquadro">
            <h4>Hai <?=count($notificheUtente)?> notific<?=(count($notificheUtente) == 1 ? 'a' : 'he')?> da leggere</h4>
            <form action="login.php" method="post">
                <ul>
                <?php foreach($notificheUtente as $notifica):?>
                    <li>
                        <div class="grid">
                            <div class="grid-col">
                                <p class="date"><?=$notifica->getDateCreated()?></p>
                                <p class="text"><?=$notifica->getText()?></p>
                            </div>
                            <div class="grid-col">
                                <label>
                                    <input type="checkbox" name="notifiche_lette[]" value="<?=$notifica->getId()?>" class="sr-only">
                                    <span></span>
                                </label>
                            </div>
                        </div>
                    </li>
                <?php endforeach?>
                </ul>
                <p class="buttons">
                    <button type="submit" name="marcalette" value="">Segna come lette</button>
                </p>
            </form>
        </div>
    </div>
    <?php endif?>
<?php endif;?>
</div>
<?php
$_pagina->creaFooter();
