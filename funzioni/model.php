<?php
interface iDbTable
{
    public function getDbTable() : string;
    public function search();
    public function ottieniRecordSingolo(int $id);
    public function ottieniRecordTutti();
    public function ottieniRisultatiFiltrati();
    public function ottieniListaOpzioni(string $columns);
    public function buildHtml() : string;
    public function deleteRecordsByIds(array $ids) : bool;
}

abstract class DbTable implements iDbTable
{
    protected static $dbTable='';
    protected static $classToCreate='';
    public static $classNameReadable='Tabella del Database';
    protected $tableHeaderCells=[];
    public $filtro='';
    public static $modalitaOutput='tabella';
    public $where='';
    public $lista=[];
    public $listaOpzioni=[];
    public $totale=0;
    public $ordineDir='ASC';
    public $ordine='';
    public $ordiniAmmessi=[];
    public $colonnaValorePerListaOpzioni='';
    public $associatedResultsTableHeaderCells = [];

    abstract protected function setTableHeaderCells(): void;

    abstract protected function setAssociatedResultsTableHeaderCells(): void;

    final protected function setTotal()
    {
        global $_messaggiGlobali,$_database;
        $query="SELECT count(*) AS total FROM ".static::$dbTable.($this->where?' WHERE '.$this->where:'');
        if ($total=$_database->query($query)) {
            list($this->totale)=pg_fetch_row($total);
            $_database->freeResult($total);
        } else {
            $_messaggiGlobali->add(MESSAGGIO_TIPO_ERRORE, 'Errore nella ricerca dei risultati');
            $_messaggiGlobali->add(MESSAGGIO_TIPO_DEBUGQUERY, 'Errore nel calcolo del totale '.strtolower(__CLASS__));
        }
    }

    final public function getDbTable() : string
    {
        return static::$dbTable;
    }

    final public function toggleOrderDirection($order='ASC')
    {
        if ($order=='ASC') {
            return 'DESC';
        }
        if ($order=='DESC') {
            return 'ASC';
        }
    }

    protected function setWhere($where='')
    {
        if($where) {
            $this->where.=($this->where?' ':'').$where;
        }
    }

    public function __construct()
    {
        $this->setTotal();
        $this->setWhere();
        $this->setTableHeaderCells();
        $this->setAssociatedResultsTableHeaderCells();
    }

    public function search()
    {
        global $_database,$_messaggiGlobali;
    }

    public function ottieniRecordSingolo(int $id)
    {
        global $_messaggiGlobali,$_database;
        if (count($this->lista) and isset($this->lista[$id])) {
            return $this->lista[$id];
        }
        $query="SELECT * FROM ".static::$dbTable." WHERE id=$id";
        if (!$risultato=$_database->query($query)) {
            $_messaggiGlobali->add(MESSAGGIO_TIPO_ERRORE, 'Errore nella ricerca del dato richiesto.');
            $_messaggiGlobali->add(MESSAGGIO_TIPO_DEBUGQUERY, 'Errore nella ricerca del singolo record per '.__CLASS__, $query);
        } else {
            $record=$_database->fetch($risultato);
            $_database->freeResult($risultato);
            return $record;
        }
    }

    public function ottieniRecordTutti()
    {
        global $_database,$_messaggiGlobali;

        $query = "SELECT * FROM ".static::$dbTable;
        $query .= ($this->where ? ' WHERE '.$this->where : '');
        $query .= ($this->ordine ? ' ORDER BY ' . $this->ordine : '');
        if ($result=$_database->query($query)) {
            while ($record=$_database->fetch($result)) {
                $object=new static::$classToCreate;
                $object->setRecord($record);
                $object->setDataByObject($record);
                $object->loadAssociatedData();
                $this->lista[$record->id]=clone $object;
                unset($object);
            }
        } else {
            $_messaggiGlobali->add(MESSAGGIO_TIPO_ERRORE, 'Errore nella ricerca dei risultati');
            $_messaggiGlobali->add(MESSAGGIO_TIPO_DEBUGQUERY, 'Errore nella selezione di tutti i record per '.__CLASS, $query);
        }
    }

    public function ottieniRisultatiFiltrati()
    {
        if(!class_exists('Filtro')) {
            require_once('classi/filtri.php');
        }
        $this->filtro=new Filtro('id', static::$dbTable, $this->ordine, $this->ordiniAmmessi, $this->where);
        $this->ordineDir=$this->filtro->ordineDir;
        if (isset($this->filtro->risultati) and count($this->filtro->risultati) > 0) {
            foreach ($this->filtro->risultati as $index => $record) {
                $object = new static::$classToCreate($record->id);
                $object->setDataByObject($record);
                $object->loadAssociatedData();
                $this->lista[$index] = clone $object;
                unset($object);
            }
        }
    }

    public function ottieniListaOpzioni(string $columns = '')
    {
        global $_pagina;

        if (count($this->lista)) {
            foreach ($this->lista as $id=>$valore) {
                if ($valore->getProperty($this->colonnaValorePerListaOpzioni)) {
                    $this->listaOpzioni[$id] = $valore->getProperty($this->colonnaValorePerListaOpzioni);
                }
            }
            if (count($this->listaOpzioni)) {
                return;
            }
            $_pagina->messaggi[] = new MessaggioDebug('La listaOpzioni di ' . get_class() . ' deve essere generata da zero. Questo non è un errore, ma è meglio verificare che colonnaValorePerListaOpzioni sia impostato correttamente.');
        }

        global $_database;

        if (!$columns) {
            $columns = "id, {$this->colonnaValorePerListaOpzioni}";
        }

        $query="SELECT $columns FROM " . static::$dbTable;

        if($this->where) {
            $query .= " WHERE {$this->where}";
        }

        $query .= " ORDER BY $this->ordine";

        if (!$risultato=$_database->query($query)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nella selezione dei dati');
            $_pagina->messaggi[] = new MessaggioDebugQuery('Errore nella creazione di una lista di opzioni per '.__CLASS__, $query);
        } else {
            while ($record=pg_fetch_object($risultato)) {
                $this->listaOpzioni[$record->id]=$record->{$this->colonnaValorePerListaOpzioni};
            }
            pg_free_result($risultato);
        }
    }

    public function returnFoundAndNotFoundRecordsFromArray(array $data, string $columnToSearch)
    {
        global $_database, $_pagina;

        $resultsFound=$resultsNotFound=[];

        $where="$columnToSearch IN(";
        foreach($data as $value) {
            $where.="'$value',";
        }
        $where=substr($where, 0, -1);
        $where.=")";
        $query="SELECT $columnToSearch FROM ".static::$dbTable." WHERE $where";
        if(!$result=$_database->query($query)) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella ricerca dei dati richiesti');
            $_pagina->messaggi[]=new MessaggioDebugQuery($query);
        } else {
            while($row=$_database->fetch($result)) {
                $resultsFound[]=$row->$columnToSearch;
            }
            $resultsNotFound=array_diff($data, $resultsFound);
        }
        $results['found']=$resultsFound;
        $results['notfound']=$resultsNotFound;
        return $results;
    }

    public function returnFoundRecordsFromArray(array $data, string $columnToSearch)
    {
        $results=$this->returnFoundAndNotFoundRecordsFromArray($data, $columnToSearch);
        return $results['found'];
    }

    public function returnNotFoundRecordsFromArray(array $data, string $columnToSearch)
    {
        $results=$this->returnFoundAndNotFoundRecordsFromArray($data, $columnToSearch);
        return $results['notfound'];
    }

    public function insertRecordFromArray(array $fields) : bool
    {
        global $_database, $_pagina;

        $columns = $values = '';

        foreach ($fields as $column => $value) {
            $columns .= ($columns ? ',' : '') . $column;
            $values .= ($values ? ',' : '') . $_database->escapeLiteral($value);
        }

        $query = "INSERT INTO " . static::$dbTable . " ({$columns}) VALUES({$values});";

        if($_database->query($query)) {
            $_pagina->messaggi[]=new MessaggioConferma('Record inserito.');
        } else {
            $_pagina->messaggi[]=new MessaggioErrore("Errore nell'inserimento del record nel Database.");
            $_pagina->messaggi[]=new MessaggioDebugQuery($query);
            return false;
        }

        return true;
    }

    public function returnHtmlResultsHeader()
    {
        $html='';
        $html.='<div class="lista-risultati-header">'."\n";
        $html.='<h5>Trovati <strong>'.$this->filtro->totaleRisultati.' risultat'.($this->filtro->totaleRisultati==1?'o':'i').'</strong>';
        $html.=' | Mostrati <strong>';
        $paginaCorrente=(int)$_GET['pagina'];
        if($paginaCorrente==0) {
            $paginaCorrente=1;
        }
        if($paginaCorrente>1) {
            $html.=(($paginaCorrente-1)*$this->filtro->maxPerPagina+1).'-';
        } else {
            $html.='1-';
        }
        $limiteMaxRisultati=$paginaCorrente*$this->filtro->maxPerPagina;
        if($limiteMaxRisultati>$this->filtro->totaleRisultati) {
            $html.=$this->filtro->totaleRisultati;
        } else {
            $html.=$limiteMaxRisultati;
        }
        $html.='</strong>';
        $html.="</h5>\n";
        $html.='</div>'."\n";
        return $html;
    }

    protected function returnHtmlResultsTableHeader()
    {
        $html='';
        $nuovoOrdineDir=$this->toggleOrderDirection($this->filtro->ordineDir);
        foreach($this->tableHeaderCells as $cellName=>$cellData) {
            $html.='<th>';
            $isSortable=$cellData['isSortable'] ?? null;
            if($isSortable) {
                $html.='<a href="?ordine='.$cellName.'&amp;ordineDir='.($this->filtro->ordine==$cellName?$nuovoOrdineDir:$this->filtro->ordineDir).'" class="'.($this->filtro->ordine==$cellName?$this->filtro->ordineDir:$nuovoOrdineDir).'">';
            }
            $html.=$cellData['label'];
            $html.=($isSortable?'</a>':'');
            $html.="</th>\n";
        }
        return $html;
    }

    protected function returnHtmlResultsBody()
    {
        $html = '';
        $html .= '<table class="table">'."\n";
        $html .= "<thead><tr>{$this->returnHtmlResultsTableHeader()}</tr></thead>\n";
        $html .= '<tbody>';

        foreach ($this->lista as $id=>$row) {
            $row->setRowCells();
            $html .= $row->returnHtmlTableRow();
        }

        $html .= "\n</tbody>\n</table>\n";

        return $html;
    }

    public function buildHtml() : string
    {
        global $_pagina, $_config;

        $html.='<div id="lista-risultati" class="container lista-risultati">'."\n";

        if (empty($this->lista)) {
            $html.='<h4>Nessun dato da visualizzare.</h4>'."\n";
        } else {
            $html.=$this->filtro;
            $html.=$this->returnHtmlResultsHeader();
            $html.=$_pagina->creaPaginazione($this->filtro->totaleRisultati, $this->filtro->maxPerPagina);
            $html.=$this->returnHtmlResultsBody();
            $html.=$_pagina->creaPaginazione($this->filtro->totaleRisultati, $this->filtro->maxPerPagina);
        }

        $html.="</div>\n";

        return $html;
    }

    public function deleteRecordsByIds(array $ids) : bool
    {
        global $_database;

        return $_database->deleteRecordsByIds(static::$dbTable, $ids);
    }

    public function buildHtmlAssociatedResults(DbTable $results = null): string
    {
        global $_pagina, $_config;

        $html .= '<div class="container lista-risultati">'."\n";

        if (empty($this->lista)) {
            $html.='<h4>Nessun dato da visualizzare.</h4>'."\n";
        } else {
            $html.=$this->filtro;
            $html.=$this->returnHtmlAssociatedResultsHeader();
            $html.=$_pagina->creaPaginazione($this->filtro->totaleRisultati, $this->filtro->maxPerPagina);
            $html.=$this->returnHtmlAssociatedResultsBody();
            $html.=$_pagina->creaPaginazione($this->filtro->totaleRisultati, $this->filtro->maxPerPagina);
        }

        $html.="</div>\n";

        return $html;
    }

    public function getTableHeaderCells(): array
    {
        return $this->tableHeaderCells;
    }

    public function returnHtmlAssociatedResultsHeader(DbTable $results = null): string
    {
        $html='';
        $html.='<div class="lista-risultati-header">'."\n";
        $html.='<h5>Trovati <strong>'.$this->filtro->totaleRisultati.' risultat'.($this->filtro->totaleRisultati==1?'o':'i').'</strong>';
        $html.=' | Mostrati <strong>';
        $paginaCorrente=(int)$_GET['pagina'];
        if($paginaCorrente==0) {
            $paginaCorrente=1;
        }
        if($paginaCorrente>1) {
            $html.=(($paginaCorrente-1)*$this->filtro->maxPerPagina+1).'-';
        } else {
            $html.='1-';
        }
        $limiteMaxRisultati=$paginaCorrente*$this->filtro->maxPerPagina;
        if($limiteMaxRisultati>$this->filtro->totaleRisultati) {
            $html.=$this->filtro->totaleRisultati;
        } else {
            $html.=$limiteMaxRisultati;
        }
        $html.='</strong>';
        $html.="</h5>\n";
        $html.='</div>'."\n";
        return $html;
    }

    public function returnHtmlAssociatedResultsBody(DbTable $results = null): string
    {
        $html = '';
        $html .= '<table class="table">'."\n";
        $html .= '<thead><tr>';

        $nuovoOrdineDir=$this->toggleOrderDirection($this->filtro->ordineDir);

        foreach($this->associatedResultsTableHeaderCells as $cellName=>$cellData) {
            $html.='<th>';
            $isSortable=$cellData['isSortable'] ?? null;
            if($isSortable) {
                $html.='<a href="?ordine=' . $cellName . ($_REQUEST['id'] ? '&amp;id=' . intval($_REQUEST['id']) : '') . '&amp;ordineDir='.($this->filtro->ordine==$cellName?$nuovoOrdineDir:$this->filtro->ordineDir).'" class="'.($this->filtro->ordine==$cellName?$this->filtro->ordineDir:$nuovoOrdineDir).'">';
            }
            $html.=$cellData['label'];
            $html.=($isSortable?'</a>':'');
            $html.="</th>\n";
        }

        $html .= '</tr></thead>' . "\n";
        $html .= '<tbody>';

        foreach ($this->lista as $id=>$row) {
            $row->setAssociatedResultsRowCells();
            $html .= $row->returnHtmlAssociatedResultsTableRow();
        }

        $html .= "\n</tbody>\n</table>\n";

        return $html;
    }
}

interface iDbTableRecord
{
    public function setRecord(&$record);
    public function getDbTable() : string;
    public function getId() : int;
    public function getRecord();
    public function getRecordFromOneField(string $field, $fieldValue);
    public function setDataByObject($object) : void;
    public function ottieniDaId(int $id) : bool;
    public function crea(array $campi) : int;
    public function aggiorna(array $campi) : bool;
    public function elimina() : bool;
    public function buildHtml() : string;
    public function getProperty(string $propertyName);
    public function loadAssociatedData() : void;
}

abstract class DbTableRecord implements iDbTableRecord
{
    protected static $dbTable='';
    protected static $classToCreate='';
    public static $classNameReadable='Record';
    protected $filtro='';
    protected $id=0;
    protected $record=null;
    protected $rowCells=[];
    protected $tableRowCssClass='';
    public $errors=[];
    protected $dateCreated = '';
    protected $dateModified = '';
    protected $createdByUser = 0;
    protected $modifiedByUser = 0;
    protected $associatedResultsRowCells = [];

    abstract public function setRowCells() : void;

    abstract public function setAssociatedResultsRowCells(): void;

    public function getRowCells() : array
    {
        return $this->rowCells;
    }

    public function getAssociatedResultsRowCells(): array
    {
        return $this->associatedResultsRowCells;
    }

    public function setDataByObject($object) : void
    {
        $this->setRecord($object);

        $this->id = $object->id ?? 0;

        if (isset($object->timestamp_creazione) and $object->timestamp_creazione) {
            $this->dateCreated = substr($object->timestamp_creazione, 0, 19);
        }

        if (isset($object->timestamp_modifica) and $object->timestamp_modifica) {
            $this->dateModified = substr($object->timestamp_modifica, 0, 19);
        } elseif (isset($object->aggiornamento) and $object->aggiornamento) {
            $this->dateModified = substr($object->aggiornamento, 0, 19);
        }

        if (isset($object->utente_creazione) and $object->utente_creazione) {
            $this->createdByUser = (int)$object->utente_creazione;
        }

        if (isset($object->utente_modifica) and $object->utente_modifica) {
            $this->modifiedByUser = (int)$object->utente_modifica;
        }

        return;
    }

    public function setTableRowCssClass(string $cssClass)
    {
        $this->tableRowCssClass=$cssClass;
    }

    public function returnHtmlTableRow()
    {
        $html = '<tr' . ($this->tableRowCssClass ? ' class="' . $this->tableRowCssClass . '"' : '') . '>';

        foreach($this->rowCells as $cellName=>$cellValue) {
            $html .= '<td' . ($cellName == 'modifica' ? ' class="azione-modifica"' : '') . '>' . $cellValue . "</td>\n";
        }

        $html .= "</tr>\n";

        return $html;
    }

    public function returnHtmlAssociatedResultsTableRow()
    {
        $html = '<tr' . ($this->tableRowCssClass ? ' class="' . $this->tableRowCssClass . '"' : '') . '>';

        foreach($this->associatedResultsRowCells as $cellName=>$cellValue) {
            $html .= '<td' . ($cellName == 'modifica' ? ' class="azione-modifica"' : '') . '>' . $cellValue . "</td>\n";
        }

        $html .= "</tr>\n";

        return $html;
    }

    final public function setRecord(&$record)
    {
        $this->record=$record;
    }

    final public function getDbTable() : string
    {
        return static::$dbTable;
    }

    final public function getId() : int
    {
        return $this->id;
    }

    final public function getRecord()
    {
        return $this->record;
    }

    final public function getDateCreated() : string
    {
        return $this->dateCreated;
    }

    final public function getDateModified() : string
    {
        return $this->dateModified;
    }

    final public function getModifiedByUser() : int
    {
        return $this->modifiedByUser;
    }

    final public function getCreatedByUser() : int
    {
        return $this->createdByUser;
    }

    final public function getRecordFromOneField(string $field, $fieldValue)
    {
        global $_database, $_pagina;
        if(is_numeric($fieldValue)) {
            $fieldValue=(int)$fieldValue;
        }
        if(is_string($fieldValue)) {
            $fieldValue=$_database->escapeLiteral($fieldValue);
        }
        $query="SELECT * FROM ".static::$dbTable." WHERE $field=$fieldValue";
        if($result=$_database->query($query)) {
            if(!$record=$_database->fetch($result)) {
                $_pagina->messaggi[]=new MessaggioDebugQuery($query, 'Record non trovato');
            } else {
                return $record;
            }
        } else {
            $_pagina->messaggi[]=new MessaggioDebugQuery($query);
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella ricerca del dato');
        }
        return '';
    }

    public function __construct(int $id = 0)
    {
        if ($id) {
            if ($this->ottieniDaId($id)) {
                $this->id = $id;
            }
        }
    }

    public function ottieniDaId(int $id) : bool
    {
        global $_messaggiGlobali,$_database;
        $query="SELECT * FROM ".static::$dbTable." WHERE id=$id";
        if (!$risultato=$_database->query($query)) {
            $_messaggiGlobali->add(MESSAGGIO_TIPO_ERRORE, 'Errore nella ricerca del dato richiesto.');
            $_messaggiGlobali->add(MESSAGGIO_TIPO_DEBUGQUERY, 'Errore nella query', $query);
        } else {
            $rows=$_database->numRows($risultato);
            if ($rows!=1) {
                $_messaggiGlobali->add(MESSAGGIO_TIPO_ERRORE, 'Errore nella ricerca del dato richiesto.');
                $_messaggiGlobali->add(MESSAGGIO_TIPO_DEBUGQUERY, 'Nessun record trovato per l\'id indicato', $query);
            } else {
                $record=$_database->fetch($risultato);
                $_database->freeResult($risultato);
                $this->record=clone $record;
                $this->setDataByObject($record);
                unset($record);
                return true;
            }
        }

        return false;
    }

    public function crea(array $campi) : int
    {
        global $_messaggiGlobali, $_database;

        if (empty($campi)) {
            $_messaggiGlobali->add(MESSAGGIO_TIPO_ERRORE, 'Nessun campo specificato');
            return false;
        }

        return $risultato = $_database->queryInsert(static::$dbTable, $campi);
    }

    public function aggiorna(array $campi=[]) : bool
    {
        global $_messaggiGlobali,$_database;
        if (empty($campi)) {
            $_messaggiGlobali->add(MESSAGGIO_TIPO_ERRORE, 'Nessun campo specificato per aggiornare '.__CLASS__);
            return false;
        }
        $campi['timestamp_modifica'] = "'now()'";
        $where='id='.$this->id;
        try {
            $risultato=$_database->queryUpdate(static::$dbTable, $campi, $where);
        } catch(ErrorException $e) {
            $_messaggiGlobali->add(MESSAGGIO_TIPO_ERRORE, $e->getMessage());
        }
        return $risultato;
    }

    public function elimina() : bool
    {
        global $_database;
        return $risultato=$_database->queryDelete(static::$dbTable, $this->id);
    }

    public function buildHtml() : string
    {
        global $_config, $_pagina;
        $html='';
        return $html;
    }

    public function __toString()
    {
        return '';
    }

    public function getProperty(string $propertyName)
    {
        return $this->{$propertyName} ?? null;
    }

    public function loadAssociatedData() : void
    {
        return;
    }
}
