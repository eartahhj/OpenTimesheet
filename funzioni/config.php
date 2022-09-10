<?php
const ERROR_NODB="Errore nell'accedere al database";
const FORM_FILTRA_DOMINI='wd2r5az';
const FORM_FILTRA_CLIENTI='aoz1lac';
const FORM_FILTRA_REGISTRAR='mf1t25o';
const FORM_FILTRA_UTENTI='qkr8a2fw';
const FORM_FILTRA_CASELLEEMAIL='tytm2a3k';
const FORM_FILTRA_PROGETTI = 'LxQ7HVCi';
const FORM_FILTRA_FATTURE = 'JDCiX4Vw';
const FORM_FILTRA_LOTTI_ORE = 'FsyE7zZ6';
const FORM_MODIFICA_DOMINIO='p2r9aa1';
const FORM_MODIFICA_REGISTRAR='x9a29ps1';
const FORM_MODIFICA_CLIENTE='m12p38z';
const FORM_MODIFICA_CONTATTO='u8a0xp1f';
const FORM_MODIFICA_CASELLAEMAIL='dcaa1ojd';
const FORM_MODIFICA_PROVIDER='r20aak3c';
const FORM_MODIFICA_FATTURA='81N7mdGD';
const FORM_MODIFICA_PROGETTO = 'SGnfUsXt';
const FORM_MODIFICA_LOTTO_ORE = 'p3z25hnB';
const FORM_TIMESHEET_UTENTE='9zFd2akg';
const FORM_TIMESHEET_ADMIN='m2q63aBex';
const FORM_TIMESHEET_REPORT = 'ezd3rJk';
const FORM_TIMESHEET_PERSONAL = 'fYt5zP7e';
const FORM_LOGIN='z2ku58af';
const FORM_UTENTE_MODIFICAPROFILO='yS9x2zk3a';
const FORM_ADMIN_GESTIONEUTENTI='ks2czm8a9';
const STATO_NORMALE=0;
const STATO_SEGNALATO=1;
const STATO_BLOCCATO=2;
const STATO_NORMALE_E_SEGNALATO=-1;
const PAGINA_NONRISERVATA=0;
const PAGINA_RISERVATA_UTENTE=1;
const PAGINA_RISERVATA_ADMIN=2;
const PAGINA_RISERVATA_SUPERADMIN=3;
const DURATA_SESSIONE_UTENTE_SECONDI=3600*24*365;
const MODALITA_DEBUG_VALORE='xA8s1GzX5KsG2m3dz2';
const PASSWORD_LUNGHEZZA_MINIMA=12;
const PASSWORD_LUNGHEZZA_MASSIMA=24;

# Variabili globali
$_config=[
    'cPath' => '/',
    'pathAdmin'=>'admin',
    'maxPerPagina' => 20,
    'maxPagine' => 9,
    'stato' => [0=>'Normale',1=>'Segnalato',2=>'Bloccato',-1=>'Normale e segnalato'],
    'risultatiPerPaginaAmmessi'=>[20=>20,50=>50,100=>100],
    '_modalitaDebug'=>($_COOKIE['debug']==MODALITA_DEBUG_VALORE),
    'permessiUtente'=>[
     PAGINA_RISERVATA_UTENTE=>'Utente standard',
     PAGINA_RISERVATA_ADMIN=>'Amministratore',
     PAGINA_RISERVATA_SUPERADMIN=>'Super Amministratore'
    ]
];

class Config
{
    public static $basePath = '/';
    public static $adminPath = 'admin';
    public static $maxResultsPerPage = 20;
    public static $maxPages = 9;
    public static $customerStatus = [0=>'Normale',1=>'Segnalato',2=>'Bloccato',-1=>'Normale e segnalato'];
    public static $resultsPerPageAdmitted = [20=>20,50=>50,100=>100];
    public static $debugMode = false;
    public static $userLevels = [
        PAGINA_RISERVATA_UTENTE=>'Utente standard',
        PAGINA_RISERVATA_ADMIN=>'Amministratore',
        PAGINA_RISERVATA_SUPERADMIN=>'Super Amministratore'
    ];
    public static $version = '1.0.0';
    public static $lastUpdate = '2022-09-08';


    public static $allowedFileExtensions = [
        'csv', 'jpg', 'png'
    ];

    public function __construct()
    {
        $this->debugMode = ($_COOKIE['debug']==MODALITA_DEBUG_VALORE);
    }
}

require_once 'database.php';


$_database=new Database;
if (!$_database->connect()) {
    throw new Exception(ERROR_NODB);
}
