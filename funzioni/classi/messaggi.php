<?php
/*
* Impostazione di eventuali messaggi da comunicare all'utente
* Da mostrare a inizio pagina prima di $_pagina->creaTesta()
* Esempi di utilizzo, quando si deve comunicare un messaggio all'utente:*
* $_pagina->messaggi[]=new MessaggioErrore('test'));
* $_pagina->messaggi[]=new MessaggioInfo('info');
* $_pagina->messaggi[]=new MessaggioConferma('ok');
*/

const MESSAGGIO_TIPO_NORMALE='messaggio';
const MESSAGGIO_TIPO_CONFERMA='conferma';
const MESSAGGIO_TIPO_ERRORE='errore';
const MESSAGGIO_TIPO_INFO='info';
const MESSAGGIO_TIPO_AVVISO='avviso';
const MESSAGGIO_TIPO_DEBUG='debug';
const MESSAGGIO_TIPO_DEBUGQUERY='debug';

class Messaggi
{
    public $lista=[];

    public function __construct(array $messaggi=[])
    {
        $this->lista=$messaggi;
    }

    public function add(string $type, string $message, $optionalParam='')
    {
        $classToCreate='Messaggio';
        if($type!=MESSAGGIO_TIPO_NORMALE) {
            $classToCreate.=ucfirst($type);
        }
        if($optionalParam) {
            $this->lista[]=new $classToCreate($message, $optionalParam);
        } else {
            $this->lista[]=new $classToCreate($message);
        }
    }

    public function checkIfMessageTypeExistsAndReturnMessages(string $type) : array
    {
        $found=0;
        $messages=[];
        foreach ($this->lista as $k=>$message) {
            if ($message->tipo==$type) {
                $found++;
                $messages[]=$message;
            }
        }
        return $messages;
    }

    public function __toString()
    {
        global $_config;
        $html='';
        if (!empty($this->lista)) {
            $html.='<div id="pagina-messaggi" class="container">'."\n";
            foreach ($this->lista as $messaggio) {
                if ($messaggio->tipo=='debug' and !$_config['_modalitaDebug']) {
                    continue;
                }
                $html.=$messaggio;
            }
            $html.="</div>\n";
        }
        return $html;
    }
}

class Messaggio
{
    public $id=0;
    public $tipo=MESSAGGIO_TIPO_NORMALE;
    public $testo='';
    public $classeCSS='messaggio';

    public function __construct(string $testo='')
    {
        $this->testo=$testo;
    }

    public function __toString()
    {
        $html='<div class="'.$this->classeCSS.'"><h4>'.$this->testo."</h4></div>\n";
        return $html;
    }
}

class MessaggioConferma extends Messaggio
{
    public function __construct(string $testo='')
    {
        $this->testo=$testo;
        $this->tipo=MESSAGGIO_TIPO_CONFERMA;
        $this->classeCSS='messaggio conferma';
    }

    public function __toString()
    {
        $html='<div class="'.$this->classeCSS.'"><h4>'.$this->testo."</h4></div>\n";
        return $html;
    }
}

class MessaggioErrore extends ErrorException
{
    public $riga=0;
    public $nomeScript='';
    public $codice=0;
    public $stackTrace='';

    public function __construct($messaggio='')
    {
        parent::__construct($messaggio);
        $this->tipo=MESSAGGIO_TIPO_ERRORE;
        $this->classeCSS='messaggio errore';
        $this->testo=$this->getMessage();
        $this->riga=$this->getLine();
        $this->nomeScript=$this->getFile();
        $this->codice=$this->getCode();
        $this->stackTrace=$this->getTraceAsString();
    }

    public function __toString()
    {
        global $_config;
        $html='<div class="'.$this->classeCSS.'"><h4>'.$this->testo."</h4>";
        if ($_config['_modalitaDebug']) {
            $html.="<p>Debug: <strong>riga $this->riga script '$this->nomeScript'</strong>".($this->codice?' con codice'.$this->codice:'')."</p>\n";
            $html.='<p>Stack trace: '.$this->stackTrace."</p>";
        }
        $html.="</div>\n";
        return $html;
    }
}

class MessaggioInfo extends Messaggio
{
    public function __construct(string $testo='')
    {
        $this->tipo=MESSAGGIO_TIPO_INFO;
        $this->classeCSS='messaggio info';
        $this->testo=$testo;
    }
    public function __toString()
    {
        $html='<div class="'.$this->classeCSS.'"><h4>'.$this->testo."</h4></div>\n";
        return $html;
    }
}

class MessaggioAvviso extends Messaggio
{
    public function __construct(string $testo='')
    {
        $this->tipo=MESSAGGIO_TIPO_AVVISO;
        $this->classeCSS='messaggio avviso';
        $this->testo=$testo;
    }
    public function __toString()
    {
        $html='<div class="'.$this->classeCSS.'"><h4>'.$this->testo."</h4></div>\n";
        return $html;
    }
}

class MessaggioDebug extends Messaggio
{
    public function __construct(string $testo='')
    {
        $this->tipo=MESSAGGIO_TIPO_DEBUG;
        $this->classeCSS='messaggio debug';
        $this->testo=$testo;
    }
    public function __toString()
    {
        $html='<div class="'.$this->classeCSS.'"><strong>(DEBUG)</strong> '.$this->testo."</div>\n";
        return $html;
    }
}

class MessaggioDebugQuery extends Messaggio
{
    public function __construct(string $query, string $msgErrore='')
    {
        global $_database;
        if ($msgErrore == '') {
            $msgErrore = $_database->lastError();
        }
        $this->tipo=MESSAGGIO_TIPO_DEBUGQUERY;
        $this->classeCSS='messaggio debug debug-query';
        $this->testo="Errore nella query: <strong>$query</strong> con messaggio <strong>$msgErrore</strong>";
    }
    public function __toString()
    {
        $html='<div class="'.$this->classeCSS.'"><strong>(DEBUG)</strong> '.$this->testo."</div>\n";
        return $html;
    }
}
