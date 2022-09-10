<?php
ini_set('display_errors', 'On');
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

require_once('config.php');
require_once('classi/messaggi.php');

$_messaggiGlobali=new Messaggi();

require_once('model.php');

function generaStringaCriptataConHash($input) : array
{
    $stringa='';
    $stringa=cryptString($input, '', true);
    return $stringa;
}

function generaStringaCriptataSemplice($input) : string
{
    $stringa='';
    $stringa=cryptString($input);
    return $stringa;
}

function verificaHashConSalt($inputUtente, $hash, $salt)
{
    $hash_input=cryptString($inputUtente, $salt);
    if ($hash_input==$hash) {
        return true;
    }
    return false;
}

function creaHttpQueryDaParametri(array $params) : string
{
    $http_query='';
    if (!empty($params)) {
        $http_query=$_SERVER['PHP_SELF'].'?'.http_build_query($params);
    }
    return $http_query;
}


/* PBKDF2 - see: https://defuse.ca/php-pbkdf2.htm */
function cryptString(string $dato, string $salt='', bool $salt_store=false)
{
    global $_database;
    $algorithm='sha512';
    $iterations=1000;
    $key_length=16;
    $raw_output=false;
    $algorithm=strtolower($algorithm);
    if (!$salt or $salt=='') {
        $salt=bin2hex(openssl_random_pseudo_bytes(16));
    }
    if (!in_array($algorithm, hash_algos(), true)) {
        $_messaggiGlobali[]=new MessaggioDebug('CRYPT ERROR: Invalid hash algorithm.');
        $_messaggiGlobali[]=new MessaggioErrore('Errore nell\'identificazione utente');
    }
    if ($iterations<=0 or $key_length<=0) {
        $_messaggiGlobali[]=new MessaggioDebug('CRYPT ERROR: Invalid parameters.');
        $_messaggiGlobali[]=new MessaggioErrore('Errore nell\'identificazione utente');
    }
    if (function_exists("hash_pbkdf2")) {
        if (!$raw_output) {
            $key_length=$key_length*2;
        }
        if ($salt_store) {
            return array('hash'=>hash_pbkdf2($algorithm, $dato, $salt, $iterations, $key_length, $raw_output),'salt'=>$salt);
        } else {
            return hash_pbkdf2($algorithm, $dato, $salt, $iterations, $key_length, $raw_output);
        }
    }
    $hash_length=strlen(hash($algorithm, "", true));
    $block_count=ceil($key_length/$hash_length);
    $output = "";
    for ($i=1;$i<=$block_count;$i++) {
        $last=$salt.pack("N", $i);
        $last=$xorsum=hash_hmac($algorithm, $last, $dato, true);
        for ($j=1;$j<$iterations;$j++) {
            $xorsum^=($last=hash_hmac($algorithm, $last, $dato, true));
        }
        $output.=$xorsum;
    }
    if ($raw_output) {
        if ($salt_store) {
            return array('hash'=>substr($output, 0, $key_length),'salt'=>$salt);
        }
        else {
            return substr($output, 0, $key_length);
        }
    } else {
        if ($salt_store) {
            return array('hash'=>bin2hex(substr($output, 0, $key_length)),'salt'=>$salt);
        }
        else {
            return bin2hex(substr($output, 0, $key_length));
        }
    }
}

function controllaRequisitiPassword(string $password) : string
{
    $messaggio='';
    if (strlen($password)<PASSWORD_LUNGHEZZA_MINIMA or strlen($password)>PASSWORD_LUNGHEZZA_MASSIMA) {
        $messaggio.='La password deve essere di lunghezza compresa tra '.PASSWORD_LUNGHEZZA_MINIMA.' e '. PASSWORD_LUNGHEZZA_MASSIMA.' caratteri.';
    }
    $caratteri=str_split($password);
    $numeri=0;
    foreach ($caratteri as $carattere) {
        if (is_numeric($carattere)) {
            $numeri++;
        }
    }
    if ($numeri<2) {
        $messaggio.=($messaggio? ' ':'').' La password deve contenere almeno due caratteri numerici';
    }
    return $messaggio;
}

function rimuoviParametroDaURL($param) : string
{
    $urlQuery=parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
    parse_str($urlQuery, $cleanURL);
    if (is_string($param)) {
        if (isset($cleanURL[$param])) {
            unset($cleanURL[$param]);
        } elseif (is_array($param)) {
            foreach ($param as $par) {
                if (isset($cleanURL[$par])) {
                    unset($cleanURL[$par]);
                }
            }
        }
    }
    $urlQuery=http_build_query($cleanURL);
    return $urlQuery;
}

function obtainGetParams($params, $acceptedValues='')
{
    $returnedParams='';
    if (!is_array($acceptedValues)) {
        $acceptedValues=[$acceptedValues];
    }
    if (isset($_GET) and !empty($_GET)) {
        if (is_array($params)) {
            foreach ($params as $name=>$param) {
                if (count($acceptedValues)==1) {
                    if (isset($_GET[$name]) and $acceptedValues[0]!=$_GET[$name]) {
                        return null;
                    }
                } elseif (count($acceptedValues)>1) {
                    if (isset($_GET[$name]) and !in_array($_GET[$name], $acceptedValues)) {
                        return null;
                    }
                }
                $returnedParams[$name]=$_GET[$name] ?? null;
            }
        } else {
            $returnedParams=$_GET[$params] ?? null;
        }
    }
    return $returnedParams;
}

function isFileExtensionAllowed(string $extension, array $allowedExtensions=[])
{
    global $_config;

    if (!$allowedExtensions) {
        $allowedExtensions=$_config->allowedFileExtensions;
    }
    if (!in_array($extension, $allowedExtensions)) {
        return false;
    }
    return true;
}

function returnFileExtension(string $fileName)
{
    $extension='';
    $extension=substr($fileName, strripos($fileName, '.')+1);
    return strtolower($extension);
}

function returnFileExtensionFromUpload(string $fieldName='file')
{
    if (isset($_FILES[$fieldName]) and $_FILES[$fieldName]) {
        $fileName=$_FILES[$fieldName]['name'];
        return $this->returnFileExtension($fileName);
    } else {
        return '';
    }
}

function returnImageExtensionFromUpload(string $fieldName='foto')
{
    return $this->returnFileExtensionFromUpload($fieldName);
}

require_once('classi/utenti.php');
require_once('navigazione.php');
require_once('pagina.php');
