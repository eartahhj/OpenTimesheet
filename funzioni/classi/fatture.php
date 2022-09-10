<?php
class FattureTipologia extends DbTableRecord
{
    protected static $dbTable = 'fatture_tipologie';
    public static $classNameReadable = 'Tipologia di fatture';
    public $descrizione = '';

    final public function setRowCells() : void
    {
        global $_config;

        $this->rowCells=[
            'descrizione' => htmlspecialchars($this->descrizione)
        ];

        return;
    }

    final public function setDataByObject($object) : void
    {
        parent::setDataByObject($object);

        if (isset($object->descrizione)) {
            $this->descrizione = htmlspecialchars($object->descrizione);
        }

        return;
    }

    final public function buildHtml() : string
    {
        $html = parent::buildHtml();

        if ($this->descrizione) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Descrizione</h4>';
            $html.='<p>'.$this->descrizione.'</p>';
            $html.='</div>'."\n";
        }

        return $html;
    }
}

class FattureTipologie extends DbTable
{
    protected static $classToCreate='FattureTipologia';
    protected static $dbTable='fatture_tipologie';
    public static $classNameReadable='Tipologie di Fatture';
    public $ordiniAmmessi=['id','descrizione'];
    public $ordine='descrizione';
    public $colonnaValorePerListaOpzioni = 'id';

    final protected function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'descrizione'=>[
                'label'=>'Descrizione',
                'isSortable'=>true
            ]
        ];

        return;
    }

    final public function setWhere($where='')
    {
        parent::setWhere($where);
        if (stripos($_SERVER['REQUEST_URI'], 'fattur')) {
            if (isset($_REQUEST['descrizione']) and $_REQUEST['descrizione']) {
                $this->where.=($this->where?' AND ':'')."descrizione ILIKE '%".strtolower($_REQUEST['descrizione'])."%'";
            }
        }
    }
}

class FattureProgetti extends DbTable
{
    protected static $classToCreate = 'FatturaProgetto';
    protected static $dbTable = 'fatture_progetto';
    public static $classNameReadable = 'Fattura';
    public $ordiniAmmessi = ['id', 'codice', 'titolo', 'progetto', 'cliente', 'importo'];
    public $ordine = 'id';
    public $colonnaValorePerListaOpzioni = 'titolo';

    final protected function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'titolo'=>[
                'label'=>'Titolo',
                'isSortable'=>true
            ],
            'progetto'=>[
                'label'=>'Progetto',
                'isSortable'=>true
            ],
            'cliente'=>[
                'label'=>'Cliente',
                'isSortable'=>true
            ],
            'data_emissione'=>[
                'label'=>'Data di emissione',
                'isSortable'=>true
            ],
            'importo'=>[
                'label'=>'Importo',
                'isSortable'=>true
            ],
            'modifica'=>[
                'label'=>'',
                'isSortable'=>false
            ]
        ];
    }

    final public function setAssociatedResultsTableHeaderCells(): void
    {
        $this->associatedResultsTableHeaderCells = $this->tableHeaderCells;
    }

    final public function setWhere($where='')
    {
        parent::setWhere($where);
        if (stripos($_SERVER['REQUEST_URI'], 'fattur')) {
            if (isset($_REQUEST['codice']) and $_REQUEST['codice']) {
                $this->where.=($this->where?' AND ':'')."codice ILIKE '%".strtolower($_REQUEST['codice'])."%'";
            }

            if (isset($_REQUEST['titolo']) and $_REQUEST['titolo']) {
                $this->where.=($this->where?' AND ':'')."titolo=".(int)$_REQUEST['titolo'];
            }

            if (isset($_REQUEST['cliente']) and $_REQUEST['cliente']) {
                $this->where .= ($this->where ? ' AND ' : '') . "cliente=" . intval($_REQUEST['cliente']);
            }

            if (isset($_REQUEST['progetto']) and $_REQUEST['progetto']) {
                $this->where .= ($this->where ? ' AND ' : '') . "progetto=" . intval($_REQUEST['progetto']);
            }
        }
    }

    final public static function getTotalEurosForProject(int $id)
    {
        global $_database, $_pagina;

        $euro = 0;

        $query = "SELECT SUM(importo) AS totale_euro FROM " . static::$dbTable . " WHERE progetto = $id";

        if (!$result = pg_query($_database->connection, $query)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nel calcolo del totale importi fatture di questo progetto');
            $_pagina->messaggi[] = new MessaggioDebug($query);
        } else {
            if ($record = pg_fetch_object($result)) {
                $euro = number_format($record->totale_euro, 2, ',', '.');
            }
        }

        return $euro;
    }

    final public function buildHtml() : string
    {
        global $_pagina, $_config;

        $html = '';



        $html .= parent::buildHtml();

        return $html;
    }
}

class FatturaProgetto extends DbTableRecord
{
    protected static $dbTable='fatture_progetto';
    public static $classNameReadable='Fatture Progetto';
    public $progettoId = 0;
    public $cliente = null;
    public $progetto = null;
    public $codice = '';
    public $tipologia = 0;
    public $dataEmissione = '';
    public $dataScadenza = '';
    public $dataPagamento = '';
    public $metodoPagamento = '';
    public $titolo = '';
    public $descrizione = '';
    public $importo = 0;
    public $pagata = false;
    protected $fatturaId = 0;

    final public function setRowCells() : void
    {
        global $_config;

        $this->rowCells=[
            'titolo' => htmlspecialchars($this->titolo),
            'progetto' => '<a href="' . Config::$basePath . 'timesheet/progetto.php?id=' . $this->progetto->getId() . '">' . $this->progetto->getName() . '</a>',
            'cliente'=>'<a href="cliente.php?id='.$this->cliente->getId().'">'.$this->cliente->nomeAzienda.'</a>',
            'data_emissione' => $this->getDataEmissione(),
            'importo' => '&euro; ' . $this->getImporto(),
            'modifica'=>'<a href="fattura.php?id='.$this->id.'"><abbr title="Modifica fattura"></abbr></a>'
        ];

        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    final public function setDataByObject($object) : void
    {
        parent::setDataByObject($object);

        if (isset($object->titolo)) {
            $this->titolo = htmlspecialchars($object->titolo);
        }

        if (isset($object->descrizione)) {
            $this->descrizione = htmlspecialchars($object->descrizione);
        }

        if(isset($object->codice)) {
            $this->codice = htmlspecialchars($object->codice);
        }

        if(isset($object->tipologia)) {
            $this->tipologia = (int)$object->tipologia;
        }

        if (isset($object->data_emissione)) {
            $this->dataEmissione = $object->data_emissione;
        }

        if (isset($object->data_scadenza)) {
            $this->dataScadenza = $object->data_scadenza;
        }

        if (isset($object->data_pagamento)) {
            $this->dataPagamento = $object->data_pagamento;
        }

        if (isset($object->metodo_pagamento)) {
            $this->metodoPagamento = htmlspecialchars($object->metodo_pagamento);
        }

        if (isset($object->importo)) {
            $this->importo = $object->importo;
        }

        if (isset($object->progetto)) {
            $this->progettoId = (int)$object->progetto;

            if(!class_exists('Progetto')) {
                $_pagina->messaggi[] = new MessaggioDebug('Classe Progetto non settata');
                require_once 'progetti.php';
            }
            $this->progetto = new Progetto($this->progettoId);
        }

        if (isset($object->cliente)) {
            $cliente = (int)$object->cliente;
            if(!class_exists('AnagraficaCliente')) {
                $_pagina->messaggi[] = new MessaggioDebug('Classe AnagraficaCliente non settata');
                require_once 'clienti.php';
            }
            $this->cliente = new AnagraficaCliente($cliente);
        }

        $this->fatturaId = intval($object->fattura ?? 0);

        return;
    }

    final public function getImporto() : string
    {
        return number_format($this->importo, 2, ',', '.');
    }

    final public function getDataEmissione() : string
    {
        return $this->dataEmissione;
    }

    final public function buildHtml() : string
    {
        $html = parent::buildHtml();

        if ($this->titolo) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Titolo</h4>';
            $html.='<p>'.$this->titolo.'</p>';
            $html.='</div>'."\n";
        }

        if ($this->dataEmissione) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Data di emissione</h4>';
            $html.='<p>'.$this->dataEmissione.'</p>';
            $html.='</div>'."\n";
        }

        if ($this->descrizione) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Descrizione</h4>';
            $html.='<p>'.$this->descrizione.'</p>';
            $html.='</div>'."\n";
        }

        if ($this->importo) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Importo</h4>';
            $html.='<p>' . $this->importo . ' €</p>';
            $html.='</div>'."\n";
        }

        if ($this->progetto) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Progetto</h4>';
            $html.='<p><a href="' . Config::$basePath . 'timesheet/progetto.php?id=' . $this->progettoId . '">' . $this->progetto->getName() . '</a></p>';
            $html.='</div>'."\n";
        }

        if ($this->cliente) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Cliente</h4>';
            $html.='<p><a href="/cliente.php?id=' . $this->cliente->getId() . '">' . $this->cliente->nomeAzienda . '</a></p>';
            $html.='</div>'."\n";
        }

        if ($this->dataScadenza) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Data di scadenza</h4>';
            $html.='<p>'.$this->dataScadenza.'</p>';
            $html.='</div>'."\n";
        }

        if ($this->dataPagamento) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Data del pagamento</h4>';
            $html.='<p>'.$this->dataScadenza.'</p>';
            $html.='</div>'."\n";
        }

        if ($this->metodoPagamento) {
            $html.='<div class="col-l-4">';
            $html.='<h4>Metodo di pagamento</h4>';
            $html.='<p>'.$this->metodoPagamento.'</p>';
            $html.='</div>'."\n";
        }

        $html.='<div class="col-l-4">';
        $html.='<h4>Metodo di pagamento</h4>';
        $html.='<p>' . ($this->pagata ? 'Si' : 'No') . '</p>';
        $html.='</div>'."\n";


        return $html;
    }
}
