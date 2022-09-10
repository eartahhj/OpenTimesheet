<?php
class ProvidersEmail extends DbTable
{
    protected static $classToCreate='ProviderEmail';
    protected static $dbTable='provider_email';
    public $ordiniAmmessi=['nome','id'];
    public $ordine='nome';
    public $colonnaValorePerListaOpzioni='nome';

    final protected function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'nome'=>[
                'label'=>'Provider',
                'isSortable'=>true
            ],
            'modifica'=>[
                'label'=>'Modifica',
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
        if (stripos($_SERVER['REQUEST_URI'], 'provider') and isset($_REQUEST['nome'])) {
            $this->where="nome ILIKE '%".strtolower($_REQUEST['nome'])."%'";
        }
    }
}

class ProviderEmail extends DbTableRecord
{
    protected static $dbTable='provider_email';
    public $nome='';

    final public function setRowCells() : void
    {
        $this->rowCells=[
            'nome'=>'<a href="provider-email.php?id='.$this->id.'">'.$this->nome.'</a>',
            'modifica'=>'<a href="provider-email.php?id='.$this->id.'"><abbr title="Modifica provider"></abbr></a>'
        ];

        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    public function setDataByObject($object) : void
    {
        if (isset($this->record->nome)) {
            $this->nome = $this->record->nome;
        }

        return;
    }
}
