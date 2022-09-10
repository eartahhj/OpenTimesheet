<?php
class Pagina
{
    protected $id=0;
    public $title='';
    public $navigazione='';
    public $navigazioneUtente='';
    public $messaggi=[];
    public $riservata=PAGINA_NONRISERVATA;
    public $css = [];
    public $javascript = [];
    public $jsOnLoad = '';

    public function __construct(string $title='', $riservata=PAGINA_NONRISERVATA)
    {
        global $_navigazione;
        if ($title) {
            $this->title=$title;
        }
        if ($riservata) {
            $this->riservata=$riservata;
        }
        
        $this->navigazione = new Navigazione(
            array(
                'clienti'=>new VoceNavigazione(
                    100,
                    'clienti.php?stato=-1',
                    'Gestione Clienti',
                    'icona-clienti',
                    [
                        'clienti-tutti'=>new VoceNavigazione(101, '/clienti.php?stato=-1', 'Elenco clienti'),
                        'crea-cliente'=>new VoceNavigazione(101, '/cliente.php', 'Nuovo cliente')
                    ]
                ),
                'domini'=>new VoceNavigazione(200,
                    'domini.php',
                    'Domini',
                    'icona-domini',
                    [
                        'domini-tutti'=>new VoceNavigazione(101, '/domini.php', 'Elenco domini'),
                        'crea-dominio'=>new VoceNavigazione(101, '/dominio.php', 'Nuovo dominio')
                    ]
                ),
                'caselle-email'=>new VoceNavigazione(
                    300,
                    'caselle-email.php',
                    'Caselle Email',
                    'icona-email',
                    [
                        'caselle-tutte'=>new VoceNavigazione(101, '/caselle-email.php', 'Elenco caselle'),
                        'crea-casella'=>new VoceNavigazione(101, '/casella-email.php', 'Nuova casella')
                    ]
                ),                
                'admin'=>new VoceNavigazione(
                    500,
                    '',
                    'Admin',
                    'icona-admin',
                    array(
                        'csv'=>new VoceNavigazione(501, '/csv.php', 'Genera CSV', 'icona-csv'),
                        'registrars'=>new VoceNavigazione(502, '/registrars.php', 'Gestione Registrar', 'icona-registrar'),
                        'providers-email'=>new VoceNavigazione(503, '/providers-email.php', 'Gestione Email Provider', 'icona-email'),
                        'utenti'=>new VoceNavigazione(504, '/admin/utenti.php', 'Gestione Utenti', 'icona-utenti'),
                        'utente'=>new VoceNavigazione(505, '/admin/utente.php', 'Nuovo Utente', 'icona-utente')
                    )
                ),
            )
        );

        $this->navigazioneUtente=
        array(
            PAGINA_RISERVATA_UTENTE=>new Navigazione(
                array(
                    'modifica-profilo'=>new VoceNavigazione(1000, '/modifica-profilo.php', 'Modifica profilo')
                ),
                'nav-utente'
            ),
            PAGINA_RISERVATA_ADMIN=>new Navigazione(
                array(
                    'utenti'=>new VoceNavigazione(1001, Config::$adminPath . '/utenti.php', 'Gestione Utenti')
                ),
                'nav-admin'
            ),
            PAGINA_RISERVATA_SUPERADMIN=>new Navigazione(
                array(
                    'superadmin'=>new VoceNavigazione(1002, Config::$adminPath . '/superadmin.php', 'SuperAdmin')
                ),
                'nav-superadmin'
            )
        );
    }

    private function verificaCorrettezzaSessioneLogin()
    {
        global $_utente;

        if (!$_utente) {
            Header("Location: " . Config::$basePath . "login.php");
        } elseif (!$_utente->getId()) {
            $_utente->eliminaSessione();
            Header("Location: " . Config::$basePath . "login.php");
        } else {
            if ((int)$_utente->getSecondsSinceLastAccess() > DURATA_SESSIONE_UTENTE_SECONDI) {
                $_utente->aggiornaUltimoAccesso();
            }
        }
    }

    private function creaNavigazioneUtente()
    {
        global $_utente;
        $numVociNavUtente=count($this->navigazioneUtente[PAGINA_RISERVATA_UTENTE]->lista);
        $numVociNavAdmin=count($this->navigazioneUtente[PAGINA_RISERVATA_ADMIN]->lista);
        echo '<div id="utente-navigazione">';
        foreach ($this->navigazioneUtente as $permesso=>$navigazioneUtente) {
            if ($_utente->getLevel() >= $permesso) {
                echo $navigazioneUtente;
            }
        }
        echo "</div>";
    }

    private function creaBarraUtente()
    {
        global $_utente;

        echo '<div id="utente-barra">';
        echo '<h3>Utente: ' . $_utente->getUsername() . '</h3>';
        $this->creaNavigazioneUtente();
        echo "</div>\n";
    }

    public function creaTesta()
    {
        global $_utente;

        if (isset($_COOKIE['authUser']) and !$_utente->getId() and $this->riservata) {
            $this->messaggi[]=new MessaggioErrore('Sessione non valida o scaduta');
            $_utente->eliminaSessione();
        }
        $numVociNav=count($this->navigazione->lista);
        if ($_utente and $_utente->getId()) {
            $this->navigazione->lista[]=new VoceNavigazione($numVociNav+1, 'login.php', 'Dashboard', 'icona-login');
        } else {
            $this->navigazione->lista[]=new VoceNavigazione($numVociNav+1, 'login.php', 'Login', 'icona-login');
        } ?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <title>Azienda - <?=$this->title?></title>
    <link rel="stylesheet" type="text/css" href="<?=Config::$basePath?>css/stile-base.css" />
    <link rel="stylesheet" type="text/css" href="<?=Config::$basePath?>css/stile-template.css" />
    <?php if ($_utente and $_utente->getId()):?>
    <link rel="stylesheet" type="text/css" href="<?=Config::$basePath?>css/stile-utente.css" />
    <?php endif; ?>
    <?php if ($_utente and $_utente->getId() and $_utente->getLevel() >= PAGINA_RISERVATA_ADMIN):?>
    <link rel="stylesheet" type="text/css" href="<?=Config::$basePath?>css/stile-admin.css" />
    <?php endif; ?>
    <link rel="stylesheet" type="text/css" href="<?=Config::$basePath?>js/chosen/chosen.css" />
    <?php foreach ($this->css as $css):?>
    <link rel="stylesheet" type="text/css" href="<?=Config::$basePath?><?=$css?>" />
    <?php endforeach?>
    <script src="<?=Config::$basePath?>js/jquery.min.js"></script>
    <script src="<?=Config::$basePath?>js/global.js"></script>
    <script src="<?=Config::$basePath?>js/chosen/chosen.jquery.min.js"></script>
    <?php foreach ($this->javascript as $js):?>
    <script src="<?=Config::$basePath?><?=$js?>"></script>
    <?php endforeach?>
    <script type="text/javascript">
    $(function() {
        $('.form-inline select').chosen();
        <?=$this->jsOnLoad?>
    });
    </script>
    </head>
    <body>
    <header>
    <?php
    if ($_utente and $_utente->getId()) {
        $this->creaBarraUtente($_utente);
    }
    ?>
        <div class="container">
            <div class="header-top">
                <h1><img src="<?=Config::$basePath?>/img/logo.png" /></h1>
                <h2>Area interna</h2>
            </div>
            <input type="checkbox" id="nav-handler" />
            <label for="nav-handler"><abbr title="Apri/Chiudi menù"></abbr></label>
            <nav id="nav-main">
                <?=$this->navigazione?>
            </nav>
        </div>
    </header>
    <main>
        <?php
        if ($this->riservata) {
            $this->verificaCorrettezzaSessioneLogin();
            if ($this->riservata > $_utente->getLevel()) {
                $this->messaggi[] = new MessaggioErrore('Permessi insufficienti per questa pagina.');

                foreach ($this->messaggi as $messaggio):?>
                <div id="pagina-messaggi" class="container">
                    <?=$messaggio?>
                </div>
                <?php
                endforeach;

                $this->creaFooter();
                exit();
            }
        }

        foreach ($this->messaggi as $messaggio):?>
        <div id="pagina-messaggi" class="container">
            <?=$messaggio?>
        </div>
        <?php
        endforeach;
    }

    public function creaFooter()
    {
        global $_database; ?>
    </main>
    <footer>
        <p>differentCRM - Versione: <a href="<?=Config::$basePath?>/changelog.txt"><?=Config::$version?></a> - Ultimo aggiornamento: <?=Config::$lastUpdate?> |
            <a href="mailto:email@dominio.it?subject=<?=htmlentities('Bug')?>">Segnala un bug</a></p>
    </footer>
    </body>
</html>
    <?php
    $_database->disconnect();
    }


    public function creaPaginazione(int $numeroRisultati, int $risultatiPerPagina=0) : string
    {
        if (!$risultatiPerPagina) {
            $risultatiPerPagina = Config::$maxResultsPerPage;
        }

        $paginaCorrente=1;
        $html='';

        if (isset($_GET['pagina'])) {
            $paginaCorrente=(int)$_GET['pagina'];
        }

        if($paginaCorrente==0) {
            $paginaCorrente=1;
        }

        $numeroPagine=ceil($numeroRisultati/$risultatiPerPagina);
        if($numeroPagine<=1) {
            return '';
        }

        $urlQuery = rimuoviParametroDaURL('pagina');
        $maxPagine = Config::$maxPages;

        if($maxPagine>=$numeroPagine) {
            $maxPagine=$numeroPagine;
        }

        $offsetPagine=$maxPagine;

        if ($paginaCorrente>ceil($maxPagine/2)) {
            $offsetPagine=ceil($maxPagine/2)-1;
        }

        $primaPaginaVisualizzata=$paginaCorrente-$offsetPagine;
        $ultimaPaginaVisualizzata=$paginaCorrente+$offsetPagine;

        if($primaPaginaVisualizzata<=0) {
            $primaPaginaVisualizzata=1;
        }

        if($ultimaPaginaVisualizzata>$numeroPagine) {
            $ultimaPaginaVisualizzata=$numeroPagine;
        }

        if ($numeroPagine) {
            $html='<nav class="paginazione">';
            $html.='<ul>';
            if ($primaPaginaVisualizzata>1) {
                $html.='<li><a href="?pagina=1'.($urlQuery?'&'.$urlQuery:'').'">«</a></li>';
                $html.='<li><a href="?pagina='.($primaPaginaVisualizzata-1).($urlQuery?'&'.$urlQuery:'').'">&lt;</a></li>';
            }
            for ($i=$primaPaginaVisualizzata;$i<=$ultimaPaginaVisualizzata;$i++) {
                $html.='<li'.($i==$paginaCorrente?' class="active"':'').'><a href="?pagina='.$i.($urlQuery?'&'.$urlQuery:'').'">'.$i.'</a></li>';
            }
            if ($ultimaPaginaVisualizzata<$numeroPagine) {
                $html.='<li><a href="?pagina='.($ultimaPaginaVisualizzata+1).($urlQuery?'&'.$urlQuery:'').'">&gt;</a></li>';
                $html.='<li><a href="?pagina='.$numeroPagine.($urlQuery?'&'.$urlQuery:'').'">»</a></li>';
            }
            $html.='</ul>';
            $html.='</nav>';
        }

        return $html;
    }
}

$errori = $_messaggiGlobali->checkIfMessageTypeExistsAndReturnMessages(MESSAGGIO_TIPO_ERRORE);

if (count($errori)) {
    $_pagina=new Pagina('Azienda - Errore');
    $_pagina->creaTesta();
    foreach ($errori as $errore) {
        echo $errore;
    }
    unset($errori);
    $_pagina->creaCoda();
}
