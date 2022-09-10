<?php
class Contatti extends DbTable
{
    protected static $classToCreate='Contatto';
    protected static $dbTable='contatti';
    public static $classNameReadable='Contatti';
    public $ordiniAmmessi=['nomecognome','id','cliente','localita','attivo'];
    public $ordine='nomecognome';

    final protected function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'nomecognome'=>[
                'label'=>'Nome e Cognome',
                'isSortable'=>true
            ],
            'azienda'=>[
                'label'=>'Azienda',
                'isSortable'=>false
            ],
            'attivo'=>[
                'label'=>'Attivo',
                'isSortable'=>false
            ],
            'modifica'=>[
                'label'=>'Mod.',
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
        parent::setWhere($where);

        if (stripos($_SERVER['REQUEST_URI'], 'contatt') and isset($_REQUEST['nomecognome']) and $_REQUEST['nomecognome']) {
            $this->where.=($this->where?' AND ':'')."nomecognome ILIKE '%".strtolower($_REQUEST['nomecognome'])."%'";
        }
    }
}


class Contatto extends DbTableRecord
{
    protected static $dbTable='contatti'; # Tabella di riferimento nel DB
    public $cliente=0;
    public $azienda='';
    public $nomecognome='';
    public $note='';
    public $indirizzo='';
    public $localita='';
    public $provincia='';
    public $cap='';
    public $telefono='';
    public $fax='';
    public $email='';
    public $pec='';

    final public function setRowCells() : void
    {
        $this->rowCells=[
            'nomecognome'=>'<a href="contatto.php?id='.$this->id.'">'.$this->nomeCognome.'</a>',
            'azienda'=>$this->azienda,
            'attivo'=>($this->attivo?'Si':'No'),
            'modifica'=>'<a href="contatto.php?id='.$this->id.'"><abbr title="Modifica contatto"></abbr></a>'
        ];

        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    public function setDataByObject($object) : void
    {
        parent::setDataByObject($object);

        if (isset($this->record->cliente)) {
            $cliente=(int)$this->record->cliente;
            $this->cliente=new AnagraficaCliente($cliente);
        }
        if (isset($this->record->azienda)) {
            $this->azienda=$this->record->azienda;
        }
        if (isset($this->record->nomecognome)) {
            $this->nomeCognome=$this->record->nomecognome;
        }
        if (isset($this->record->indirizzo)) {
            $this->indirizzo=$this->record->indirizzo;
        }
        if (isset($this->record->localita)) {
            $this->localita=$this->record->localita;
        }
        if ($this->localita) {
            $this->indirizzo.=' '.$this->localita;
        }
        if (isset($this->record->cap)) {
            $this->cap=$this->record->cap;
        }
        if ($this->cap) {
            $this->indirizzo.=' '.$this->cap;
        }
        if (isset($this->record->provincia)) {
            $this->provincia=$this->record->provincia;
        }
        if ($this->provincia) {
            $this->indirizzo.=' '.$this->provincia;
        }
        if (isset($this->record->telefono)) {
            $this->telefono=$this->record->telefono;
        }
        if (isset($this->record->fax)) {
            $this->fax=$this->record->fax;
        }
        if (isset($this->record->email)) {
            $this->email=strtolower($this->record->email);
        }
        if (isset($this->record->note)) {
            $this->note=$this->record->note;
        }
        if (isset($this->record->attivo)) {
            $this->attivo=$this->record->attivo;
        }

        return;
    }
}
