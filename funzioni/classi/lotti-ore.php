<?php
class LottiOre extends DbTable
{
    const STATO_TUTTI = '';
    const STATO_ATTIVI = 't';
    const STATO_DISATTIVI = 'f';

    protected static $dbTable = 'lotti_ore';
    protected static $classToCreate = 'LottoOre';
    public static $classNameReadable = 'Lotti Ore';
    public $ordiniAmmessi = ['titolo', 'id', 'progetto', 'numero_ore'];
    public $ordine = 'titolo';
    public $colonnaValorePerListaOpzioni = 'titolo';
    public static $stati = [
        self::STATO_TUTTI => 'Tutti',
        self::STATO_ATTIVI => 'Solo attivi',
        self::STATO_DISATTIVI => 'Solo disattivi'
    ];

    public function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'titolo'=>[
                'label'=>'Titolo',
                'isSortable'=>true
            ],
            'numero_ore'=>[
                'label'=>'Ore',
                'isSortable'=>true
            ],
            'fattura'=>[
                'label'=>'Fattura',
                'isSortable'=>false
            ],
            'progetto'=>[
                'label'=>'Progetto',
                'isSortable'=>true
            ],
            'modifica'=>[
                'label'=>'',
                'isSortable'=>false
            ],
            'report'=>[
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
        parent::setWhere($where);
        if (stripos($_SERVER['REQUEST_URI'], 'progett')) {
            if (isset($_REQUEST['titolo']) and $_REQUEST['titolo']) {
                $this->where.=($this->where?' AND ':'')."titolo ILIKE '%".strtolower($_REQUEST['titolo'])."%'";
            }

            if (isset($_REQUEST['progetto']) and $_REQUEST['progetto']) {
                $this->where.=($this->where?' AND ':'')."progetto=".(int)$_REQUEST['progetto'];
            }

            if (isset($_GET['stato']) and $status = $this->getWhereConditionForStatus($_GET['stato'])) {
                $this->where .= ($this->where ? ' AND ' : '') . $status;
            }

            if (isset($_GET['fattura']) and $fattura = intval($_GET['fattura'])) {
                $this->where .= ($this->where ? ' AND ' : '') . $fattura;
            }
        }
    }

    public function getWhereConditionForStatus(string $status = '') : string
    {
        $condition = '';

        switch ($status) {
            case self::STATO_ATTIVI:
                $condition = "attivo = 't'";
            break;
            case self::STATO_DISATTIVI:
                $condition = "attivo = 'f'";
            break;

            default:
                $condition = '';
            break;
        }

        return $condition;
    }

    public static function getTotalHoursForProject(int $projectId = 0) : string
    {
        global $_database, $_pagina;

        $totalHours = '';

        $query = "SELECT SUM(numero_ore) AS ore FROM lotti_ore WHERE progetto = $projectId";

        if (!$result = pg_query($_database->connection, $query)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nel caricamento del totale lotti di ore per questo progetto');
            $_pagina->messaggi[] = new MessaggioDebug($query);
        } else {
            if ($totaleLottiOreProgetto = pg_fetch_object($result)) {
                $totalHours = $totaleLottiOreProgetto->ore ?? '';
            }
        }

        return $totalHours;
    }
}

class LottoOre extends DbTableRecord
{
    protected static $dbTable = 'lotti_ore';
    protected $title = '';
    protected $projectId = 0;
    protected $project = null;
    protected $customer = null;
    protected $active = false;
    protected $numberOfHours = '00:00'; # Interval
    protected $dateStart = '';
    protected $fatturaId = 0;
    protected $fattura = '';

    public function setProjectId(int $id)
    {
        $this->projectId = $id;
        return;
    }

    public function setDataByObject($object) : void
    {
        parent::setDataByObject($object);

        $this->title = $object->titolo ?? '';

        if (isset($object->attivo) and $object->attivo == 't') {
            $this->active = true;
        }

        $this->numberOfHours = $object->numero_ore ?? '00:00';
        $this->dateStart = $object->data_inizio ?? '';
        $this->projectId = $object->progetto ?? 0;
        $this->fatturaId = $object->fattura ?? 0;

        return;
    }

    public function loadAssociatedData() : void
    {
        parent::loadAssociatedData();

        if (isset($this->projectId)) {
            if(!class_exists('Progetto')) {
                $_pagina->messaggi[] = new MessaggioDebug('Classe Progetto non settata');
                require_once 'progetti.php';
            }

            $this->project = new Progetto($this->projectId);
        }

        if (isset($this->fatturaId)) {
            if(!class_exists('FatturaProgetto')) {
                $_pagina->messaggi[] = new MessaggioDebug('Classe FatturaProgetto non settata');
                require_once 'fatture.php';
            }

            $this->fattura = new FatturaProgetto($this->fatturaId);
        }

        return;
    }

    public function getTitle() : string
    {
        return $this->title;
    }

    public function isActive() : bool
    {
        return $this->active;
    }

    public function getNumberOfHours() : string
    {
        return $this->numberOfHours;
    }

    public function getProjectId() : int
    {
        return $this->projectId;
    }

    public function getProject() : Progetto
    {
        return $this->project;
    }

    public function getFatturaId() : int
    {
        return $this->fatturaId;
    }

    public function getFattura() : FatturaProgetto
    {
        return $this->fattura;
    }

    final public function setRowCells() : void
    {
        $this->rowCells['titolo'] = '<a href="lotto-ore.php?id=' . $this->id . '">' . $this->getTitle() . '</a>';

        $this->rowCells['numero_ore'] = $this->getNumberOfHours();
        $this->rowCells['fattura'] = '';
        if ($this->getFatturaId()) {
            $this->rowCells['fattura'] = '<a href="/fattura.php?id=' . $this->getFatturaId() . '">' . $this->getFattura()->titolo . '</a>';
        }
        $this->rowCells['progetto'] = '';
        $this->rowCells['report'] = '';
        $this->rowCells['modifica'] = '<a href="lotto-ore.php?id='.$this->id.'"><abbr title="Modifica lotto ore"></abbr></a>';

        if ($this->project) {
            $this->rowCells['progetto'] = '<a href="' . Config::$basePath . 'timesheet/progetto.php?id=' . $this->project->getId() . '">' . $this->project->getName() . '</a>';
            $this->rowCells['report'] = '<a href="' . Config::$basePath . 'timesheet/progetto-report.php?progetto=' . $this->project->getId() . '"><abbr title="Report progetto"></abbr></a>';
        }

        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    final public function returnHtmlTableRow()
    {
        $html = '<tr class="' . ($this->attivo ? 'attivo' : 'inattivo') . ($this->tableRowCssClass ? ' ' . $this->tableRowCssClass : '') . '">';

        foreach($this->rowCells as $cellName=>$cellValue) {
            if ($cellName == 'modifica') {
                $cssClass = 'azione-modifica';
            } elseif ($cellName == 'report') {
                $cssClass = 'azione-report';
            }
            $html .= '<td' . ($cssClass ? ' class="' . $cssClass . '"' : '') . '>' . $cellValue . "</td>\n";
        }

        $html .= "</tr>\n";

        return $html;
    }
}
