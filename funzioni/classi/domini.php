<?php
class Domini extends DbTable
{
    protected static $classToCreate='Dominio';
    protected static $dbTable='domini';
    public static $classNameReadable='Domini';
    public $ordiniAmmessi=['name','id','aggiornamento','registrar','attivo'];
    public $ordine='name';
    public $colonnaValorePerListaOpzioni='name';

    final protected function setTableHeaderCells(): void
    {
        $this->tableHeaderCells=[
            'name'=>[
                'label'=>'Dominio',
                'isSortable'=>true
            ],
            'cliente'=>[
                'label'=>'Cliente',
                'isSortable'=>false
            ],
            'registrar'=>[
                'label'=>'Registrar',
                'isSortable'=>false
            ],
            'attivo'=>[
                'label'=>'Attivo',
                'isSortable'=>true
            ],
            'modifica'=>[
                'label'=>'',
                'isSortable'=>false
            ]
        ];

        return;
    }

    final public function setAssociatedResultsTableHeaderCells(): void
    {
        $this->associatedResultsTableHeaderCells = $this->tableHeaderCells;
    }

    final public function setWhere($where='')
    {
        global $_database;

        parent::setWhere($where);
        if (stripos($_SERVER['REQUEST_URI'], 'domin')) {
            if (isset($_REQUEST['name']) and $_REQUEST['name']) {
                $this->where.=($this->where?' AND ':'')."name ILIKE '%".strtolower($_REQUEST['name'])."%'";
            }

            if (isset($_REQUEST['registrar']) and $_REQUEST['registrar']) {
                $this->where.=($this->where?' AND ':'')."registrar=".(int)$_REQUEST['registrar'];
            }

            if (isset($_REQUEST['attivo'])) {
                if ($_REQUEST['attivo'] == 't' or $_REQUEST['attivo'] == 'f') {
                    $this->where .= ($this->where ? ' AND ' : '') . "attivo = " . $_database->escapeLiteral($_REQUEST['attivo']);
                }
            }
        }
    }
}

class Dominio extends DbTableRecord
{
    protected static $dbTable='domini';
    public static $classNameReadable='Dominio';
    public $nome='';
    public $userdns='';
    public $notecliente='';
    public $cliente=0;
    public $datareg='';
    public $contratto='';
    public $crediti=0;
    public $aggiornamento = '';
    public $registrar=0;
    public $caselleEmailAssociate=[];
    public $dataChiusura = '';
    public $note = '';
    public $active = true;

    final public function setRowCells() : void
    {
        $this->rowCells['dominio'] = '<a href="dominio.php?id='.$this->id.'">'.$this->nome.'</a>';
        $this->rowCells['cliente'] = '';
        if($this->cliente) {
            $this->rowCells['cliente']='<a href="cliente.php?id='.$this->cliente->getId().'">'.$this->cliente->nomeAzienda.'</a>';
        }
        $this->rowCells['registrar'] = '';
        if($this->registrar) {
            $this->rowCells['registrar'] = $this->registrar->nome;
        }
        $this->rowCells['attivo'] = $this->isActive() ? 'Si' : 'No';
        $this->rowCells['modifica'] = '<a href="dominio.php?id='.$this->id.'"><abbr title="Modifica dominio"></abbr></a>';

        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    public function setDataByObject($object) : void
    {
        parent::setDataByObject($object);

        if (isset($this->record->name)) {
            $this->nome=strtolower(htmlspecialchars($this->record->name));
        }
        if (isset($this->record->userdns)) {
            $this->userdns=htmlspecialchars($this->record->userdns);
        }
        if (isset($this->record->notecliente)) {
            $this->notecliente=htmlspecialchars($this->record->notecliente);
        }
        if (isset($this->record->cliente)) {
            $cliente=(int)$this->record->cliente;
            if(!class_exists('AnagraficaCliente')) {
                $_pagina->messaggi[]=new MessaggioDebug('Classe AnagraficaCliente non settata');
                require_once 'clienti.php';
            }
            $this->cliente=new AnagraficaCliente($cliente);
        }
        if (isset($this->record->datareg)) {
            $this->datareg=htmlspecialchars($this->record->datareg);
        }
        if (isset($this->record->crediti)) {
            $this->crediti=htmlspecialchars($this->record->crediti);
        }
        if (isset($this->record->registrar)) {
            $registrar=(int)$this->record->registrar;
            if(!class_exists('Registrar')) {
                $_pagina->messaggi[]=new MessaggioDebug('Classe Registrar non settata');
                require_once('funzioni/classi/registrar.php');
            }
            $this->registrar=new Registrar($registrar);
        }

        $this->aggiornamento = $this->dateModified;

        if (isset($this->record->attivo)) {
            if ($this->record->attivo == 'f') {
                $this->active = false;
            }
        }

        if (isset($this->record->data_chiusura)) {
            $this->dataChiusura = $this->record->data_chiusura;
        }

        if (isset($this->record->note)) {
            $this->note = $this->record->note;
        }

        return;
    }

    final public function isActive() : bool
    {
        return $this->active;
    }

    public function controllaSeNomeDominioEsiste(string $nome='') : bool
    {
        global $_database,$_pagina;
        if ($nome=='') {
            $this->messaggi[]=new MessaggioDebug('Errore nella verifica di un nome di dominio uguale a quello creato: Nome non impostato per il controllo.');
            $this->messaggi[]=new MessaggioErrore('Si è verificato un errore nella creazione del nuovo dominio. Controllo fallito.');
            return true;
        }
        $queryTrovati="SELECT count(id) as trovati FROM ".static::$dbTable." WHERE name=$nome";
        if ($risTrovati=$_database->query($queryTrovati)) {
            $nomiDominioUguali=pg_fetch_object($risTrovati);
            if ($nomiDominioUguali->trovati>0) { # Trovato un nome di dominio uguale
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del dominio. Questo nome di dominio esiste già.');
                return true;
            } else {
                return false; # Nome di dominio uguale NON trovato
            }
        } else {
            $_pagina->messaggi[]=new MessaggioDebugQuery($queryTrovati);
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella verifica dei domini');
        }
        pg_free_result($risTrovati);
        return true;
    }

    final public function associaCaselleEmail()
    {
        global $_pagina;
        
        if(!class_exists('CaselleEmail')) {
            $_pagina->messaggi[]=new MessaggioErrore('Classe CaselleEmail non settata');
            require_once('funzioni/classi/caselle-email.php');
        }
        $caselleEmail=new CaselleEmail;
        $where="email LIKE '%@{$this->nome}'";
        $caselleEmail->setWhere($where);
        $caselleEmail->ottieniRisultatiFiltrati();
        if(count($caselleEmail->lista)) {
            $this->caselleEmailAssociate=$caselleEmail;
        }
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
