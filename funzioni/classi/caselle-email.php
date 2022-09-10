<?php
class CaselleEmail extends DbTable
{
    const EMAIL_PROVIDER_TWS = 5;

    protected static $classToCreate='CasellaEmail';
    protected static $dbTable='caselle_email';
    public static $classNameReadable='Caselle email';
    public $ordiniAmmessi=['email','id','aggiornamento','attivo'];
    public $ordine='email';
    public $colonnaValorePerListaOpzioni='email';

    final protected function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'email'=>[
                'label'=>'Casella Email',
                'isSortable'=>true
            ],
            'cliente'=>[
                'label'=>'Cliente',
                'isSortable'=>false
            ],
            'provider'=>[
                'label'=>'Provider',
                'isSortable'=>false
            ],
            'attivo'=>[
                'label'=>'Attivo',
                'isSortable'=>true
            ],
            'modifica'=>[
                'label'=>'',
                'isSortable'=>false
            ],
        ];

        return;
    }

    final public function setAssociatedResultsTableHeaderCells(array $tableHeaderCells = []): void
    {
        $this->associatedResultsTableHeaderCells = [
            'email'=>[
                'label'=>'Casella Email',
                'isSortable'=>true
            ],
            'provider'=>[
                'label'=>'Provider',
                'isSortable'=>false
            ],
            'attivo'=>[
                'label'=>'Attivo',
                'isSortable'=>true
            ],
            'modifica'=>[
                'label'=>'',
                'isSortable'=>false
            ],
        ];
    }

    final public function setWhere($where='')
    {
        global $_database;

        if($where) {
            $this->where.=$where;
        } else {
            if (stripos($_SERVER['REQUEST_URI'], 'casell')) {
                if (isset($_REQUEST['email']) and $_REQUEST['email']) {
                    $this->where.=($this->where?' AND ':'')."email ILIKE '%".strtolower($_REQUEST['email'])."%'";
                }

                if (isset($_REQUEST['provider']) and $_REQUEST['provider']) {
                    $this->where.=($this->where?' AND ':'')."provider=".(int)$_REQUEST['provider'];
                }

                if (isset($_REQUEST['attivo'])) {
                    if ($_REQUEST['attivo'] == 't' or $_REQUEST['attivo'] == 'f') {
                        $this->where .= ($this->where ? ' AND ' : '') . "attivo = " . $_database->escapeLiteral($_REQUEST['attivo']);
                    }
                }
            }
        }
    }
}

class CasellaEmail extends DbTableRecord
{
    protected static $dbTable='caselle_email';
    public static $classNameReadable='Casella email';
    public $email='';
    public $dominio='';
    public $cliente=0;
    public $attivo=false;
    public $aliasOf='';
    public $aggiornamento=''; # Timestamp without timezone
    public $provider=0;
    public $note = '';

    final public function setRowCells() : void
    {
        $this->rowCells['email']='<a href="casella-email.php?id='.$this->id.'">'.$this->email.'</a>';
        if($this->cliente) {
            $this->rowCells['cliente']='<a href="cliente.php?id='.$this->cliente->getId().'">'.$this->cliente->nomeAzienda.'</a>';
        } else {
            $this->rowCells['cliente']='';
        }
        $this->rowCells['provider']=$this->provider->nome;
        $this->rowCells['attivo'] = $this->isActive() ? 'Si' : 'No';
        $this->rowCells['modifica']='<a href="casella-email.php?id='.$this->id.'"><abbr title="Modifica casella email"></abbr></a>';

        return;
    }

    final public function setAssociatedResultsRowCells() : void
    {
        $this->associatedResultsRowCells['email']='<a href="casella-email.php?id='.$this->id.'">'.$this->email.'</a>';
        $this->associatedResultsRowCells['provider']=$this->provider->nome;
        $this->associatedResultsRowCells['attivo'] = $this->isActive() ? 'Si' : 'No';
        $this->associatedResultsRowCells['modifica']='<a href="casella-email.php?id='.$this->id.'"><abbr title="Modifica casella email"></abbr></a>';

        return;
    }

    public function setDataByObject($object) : void
    {
        if (isset($this->record->email)) {
            $this->email=htmlspecialchars($this->record->email);
            $this->dominio=substr($this->email, stripos($this->email,'@')+1, strlen($this->email));
        }

        if(!class_exists('Dominio')) {
            require_once 'funzioni/classi/domini.php';
            $_pagina->messaggi[]=new MessaggioDebug('Classe Dominio non settata');
        }

        $dominio=new Dominio();

        if($dominioRecord=$dominio->getRecordFromOneField('name', $this->dominio)) {
            $dominio=new Dominio($dominioRecord->id);
            $this->dominio=clone $dominio;
            unset($dominio, $dominioRecord);
        }

        if(!class_exists('AnagraficaCliente')) {
            require_once 'funzioni/classi/clienti.php';
            $_pagina->messaggi[]=new MessaggioDebug('Classe AnagraficaCliente non settata');
        }

        if (isset($this->record->cliente)) {
            $cliente=(int)$this->record->cliente;
            $this->cliente=new AnagraficaCliente($cliente);
        }
        if (isset($this->record->provider)) {
            $provider=(int)$this->record->provider;
            if(!class_exists('ProviderEmail')) {
                require_once 'funzioni/classi/provider-email.php';
                $_pagina->messaggi[]=new MessaggioDebug('Classe ProviderEmail non settata');
            }
            $this->provider=new ProviderEmail($provider);
        }
        if (isset($this->record->alias_of)) {
            $this->aliasOf=htmlspecialchars($this->aliasOf);
        }
        if (isset($this->record->aggiornamento)) {
            $this->aggiornamento=substr($this->record->aggiornamento, 0, 19);
        }
        if (isset($this->record->attivo)) {
            if ($this->record->attivo == 't') {
                $this->attivo = true;
            }
        }

        $timestamp=new DateTime();
        if ($this->aggiornamento=='') {
            $this->aggiornamento=$timestamp->format("d-m-Y H:i:s.u");
        }

        $this->note = $this->record->note ?? '';

        return;
    }

    public function controllaSeEmailEsiste(string $email='') : bool
    {
        global $_database,$_pagina;
        if ($email=='') {
            $this->messaggi[]=new MessaggioDebug('Errore nella verifica di una mail uguale a quella creato: email non impostato per il controllo.');
            $this->messaggi[]=new MessaggioErrore('Si è verificato un errore nella creazione della nuova casella email. Controllo fallito.');
            return true;
        }
        $queryTrovati="SELECT count(id) AS trovate FROM static::$dbTable WHERE email=".$_database->escapeLiteral($email);
        if ($risTrovati=$_database->query($queryTrovati)) {
            $caselleEmail=$_database->fetch($risTrovati);
            if ($caselleEmail->trovate>0) {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione della casella email. Questa email esiste già.');
                return true;
            } else {
                return false;
            }
        } else {
            $_pagina->messaggi[]=new MessaggioDebugQuery($queryTrovati);
        }
        pg_free_result($risTrovati);
    }

    final public function isActive() : bool
    {
        return $this->attivo;
    }

    final public function returnHtmlTableRow()
    {
        if ($this->isActive()) {
            $this->setTableRowCssClass('active');
        } else {
            $this->setTableRowCssClass('inactive');
        }

        $html = parent::returnHtmlTableRow();

        return $html;
    }
}
