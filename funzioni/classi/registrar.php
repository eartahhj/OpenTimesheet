<?php
class Registrars extends DbTable
{
    protected static $classToCreate='Registrar';
    protected static $dbTable='registrar';
    public $ordiniAmmessi=['name','id'];
    public $ordine='name';
    public $colonnaValorePerListaOpzioni='name';

    final protected function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'name'=>[
                'label'=>'Registrar',
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
        if (stripos($_SERVER['REQUEST_URI'], 'registrar') and isset($_REQUEST['name'])) {
            $this->where="name ILIKE '%".strtolower($_REQUEST['name'])."%'";
        }
    }
}

class Registrar extends DbTableRecord
{
    protected static $dbTable='registrar';
    public $nome='';

    final public function setRowCells() : void
    {
        $this->rowCells=[
            'registrar'=>'<a href="registrar.php?id='.$this->id.'">'.$this->nome.'</a>',
            'modifica'=>'<a href="registrar.php?id='.$this->id.'"><abbr title="Modifica registrar"></abbr></a>'
        ];

        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    public function setDataByObject($object) : void
    {
        if (isset($this->record->name)) {
            $this->nome=htmlspecialchars($this->record->name);
        }

        return;
    }
}
