<?php
class Progetti extends DbTable
{
    const STATO_TUTTI = 1;
    const STATO_ABILITATI = 2;
    const STATO_DISABILITATI = 3;
    const STATO_FINITI = 4;
    const STATO_NON_FINITI = 5;
    const STATO_DISABILITATI_E_FINITI = 6;
    const TIPOLOGIA_PROGETTO = 1;
    const TIPOLOGIA_MANUTENZIONE_POSTFATTURA = 2;
    const TIPOLOGIA_MANUTENZIONE_PREFATTURA = 3;

    protected static $dbTable = 'progetti';
    protected static $classToCreate = 'Progetto';
    public static $classNameReadable = 'Progetti';
    public $ordiniAmmessi = ['nome','id','cliente'];
    public $ordine = 'nome';
    public $colonnaValorePerListaOpzioni = 'nome';
    public $showOnlyActiveProjects = true;
    public static $statiProgetto = [
        self::STATO_TUTTI => 'Tutti',
        self::STATO_ABILITATI => 'Solo abilitati',
        self::STATO_DISABILITATI => 'Solo disabilitati',
        self::STATO_FINITI => 'Solo finiti',
        self::STATO_NON_FINITI => 'Solo non finiti',
        self::STATO_DISABILITATI_E_FINITI => 'Solo disabilitati e finiti'
    ];
    protected static $tipologieProgetto = [
        self::TIPOLOGIA_PROGETTO => 'Progetto',
        self::TIPOLOGIA_MANUTENZIONE_POSTFATTURA => 'Manutenzione che richiede fattura già emessa',
        self::TIPOLOGIA_MANUTENZIONE_PREFATTURA => 'Manutenzione lavorabile prima della fattura',
    ];

    public function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'nome'=>[
                'label'=>'Progetto',
                'isSortable'=>true
            ],
            'cliente'=>[
                'label'=>'Cliente',
                'isSortable'=>true
            ],
            'modifica'=>[
                'label'=>'',
                'isSortable'=>false
            ],
            'report' => [
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
            if (isset($_REQUEST['name']) and $_REQUEST['name']) {
                $this->where.=($this->where?' AND ':'')."nome ILIKE '%".strtolower($_REQUEST['name'])."%'";
            }
            if (isset($_REQUEST['cliente']) and $_REQUEST['cliente']) {
                $this->where.=($this->where?' AND ':'')."cliente=".(int)$_REQUEST['cliente'];
            }

            if (isset($_GET['stato']) and $status = $this->getWhereConditionForStatus((int)$_GET['stato'])) {
                $this->where .= ($this->where ? ' AND ' : '') . $status;
            } else {
                if ($this->showOnlyActiveProjects) {
                    $this->where .= ($this->where ? ' AND ' : '') . " abil";
                }
            }
        }
    }

    public function setShowOnlyActiveProjects(bool $bool) : void
    {
        $this->showOnlyActiveProjects = $bool;
        return;
    }

    public function getWhereConditionForStatus(int $status) : string
    {
        $condition = '';

        switch ($status) {
            case Progetti::STATO_ABILITATI:
                $condition = "abil = 't'";
            break;
            case Progetti::STATO_DISABILITATI:
                $condition = "abil = 'f'";
            break;
            case Progetti::STATO_FINITI:
                $condition = "finito = 't'";
            break;
            case Progetti::STATO_NON_FINITI:
                $condition = "finito = 'f'";
            break;
            case Progetti::STATO_DISABILITATI_E_FINITI:
                $condition = "abil = 'f' AND finito = 't'";
            break;

            default:
                $condition = '';
            break;
        }

        return $condition;
    }

    public static function getTipologie()
    {
        return self::$tipologieProgetto;
    }
}

class Progetto extends DbTableRecord
{
    protected static $dbTable = 'progetti';
    protected $name = '';
    protected $customer = null;
    protected $active = false;
    protected $finished = false;
    protected $lottiOre = [];
    protected $tipologiaId = 0;
    protected $tipologia = null;
    protected $alertEnabled = true;

    public function setDataByObject($object) : void
    {
        parent::setDataByObject($object);

        $this->name = $object->nome ?? '';

        if (isset($object->cliente)) {
            $cliente = (int)$object->cliente;
            if(!class_exists('AnagraficaCliente')) {
                $_pagina->messaggi[] = new MessaggioDebug('Classe AnagraficaCliente non settata');
                require_once 'clienti.php';
            }
            $this->customer = new AnagraficaCliente($cliente);
        }

        if (isset($object->abil) and $object->abil == 't') {
            $this->active = true;
        }

        if (isset($object->finito) and $object->finito == 't') {
            $this->finished = true;
        }

        $this->tipologiaId = $object->tipologia ?? 0;

        return;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function isActive() : bool
    {
        return $this->active;
    }

    public function isFinished() : bool
    {
        return $this->finished;
    }

    final  public function getTipologiaId() : int
    {
        return $this->tipologiaId;
    }

    final public function isEnabled() : bool
    {
        return $this->alertEnabled;
    }

    final public function setRowCells() : void
    {
        $this->rowCells['nome']='<a href="progetto.php?id='.$this->id.'">'.$this->getName().'</a>';
        $this->rowCells['cliente']='';

        if($this->customer) {
            $this->rowCells['cliente']='<a href="' . Config::$basePath . 'cliente.php?id='.$this->customer->getId().'">'.$this->customer->nomeAzienda.'</a>';
        }

        $this->rowCells['modifica']='<a href="progetto.php?id='.$this->id.'"><abbr title="Modifica progetto"></abbr></a>';
        $this->rowCells['report']='<a href="progetto-report.php?progetto='.$this->id.'"><abbr title="Report progetto"></abbr></a>';

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

    public function controllaSeNomeProgettoEsiste(string $nome) : bool
    {
        global $_database, $_pagina;

        $query = "SELECT count(*) AS found FROM ".static::$dbTable." WHERE nome=$nome";

        if ($result = $_database->query($query)) {
            $projects = pg_fetch_object($result);
            if ($projects->found > 0) {
                return true;
            } else {
                return false;
            }
        } else {
            $_pagina->messaggi[]=new MessaggioDebugQuery($queryTrovati);
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella verifica dei domini');
        }
        pg_free_result($risTrovati);
    }

    final public function loadLottiOre()
    {
        global $_database, $_pagina;

        if (!$risLotti=pg_query($_database->connection, "SELECT id FROM lotti_ore WHERE progetto = $this->id ORDER BY id DESC")) {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nell\'estrazione dei lotti di ore associati al progetto');
        } else {
            if (pg_num_rows($risLotti)>0) {
                while ($lottoOre=pg_fetch_object($risLotti)) {
                    $lottoOre = new LottoOre($lottoOre->id);
                    $lottoOre->loadAssociatedData();
                    $this->lottiOre[$lottoOre->id] = $lottoOre;
                }
            }
            pg_free_result($risLotti);
        }
    }

    final public function getLottiOre() : array
    {
        return $this->lottiOre;
    }
}
