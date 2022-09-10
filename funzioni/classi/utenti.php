<?php
class Utenti extends DbTable
{
    protected static $classToCreate='Utente';
    protected static $dbTable='utenti';
    public $ordine='username';
    public $ordiniAmmessi=['nome','cognome','id','email','username','attivo'];
    public $colonnaValorePerListaOpzioni = "username";

    final protected function setTableHeaderCells() : void
    {
        $this->tableHeaderCells=[
            'nome'=>[
                'label'=>'Nome',
                'isSortable'=>true
            ],
            'email'=>[
                'label'=>'email',
                'isSortable'=>true
            ],
            'username'=>[
                'label'=>'Nome utente',
                'isSortable'=>true
            ],
            'permessi'=>[
                'label'=>'Permessi',
                'isSortable'=>false
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
        if (stripos($_SERVER['REQUEST_URI'], 'utent')) {
            if (isset($_GET['username']) and $_GET['username']) {
                $this->where.=($this->where ? ' AND ' : '')."username ILIKE '%".strtolower($_GET['username'])."%'";
            }
            if (isset($_GET['attivo']) and ($_GET['attivo']=='t' or $_GET['attivo']=='f')) {
                $this->where.=($this->where ? ' AND ' : '').($_GET['attivo']=='t' ? 'attivo' : 'not attivo');
            }
        }
    }

    public static function controllaSeUsernameEsiste(string $username): string
    {
        global $_database;

        $messaggioErrore='';

        $query = 'SELECT id FROM ' . self::$dbTable . ' WHERE username='.pg_escape_literal($username);
        if ($risultato = $_database->query($query)) {
            if ($_database->numRows($risultato)) {
                $messaggioErrore = 'Questo username esiste già';
            }
        }

        return $messaggioErrore;
    }
}

class Utente extends DbTableRecord
{
    protected static $dbTable='utenti';
    protected $username = '';
    protected $passwordHash = '';
    protected $passwordSalt = '';
    protected $email = '';
    protected $lastAccess = '';
    protected $level = 1;
    protected $token = '';
    protected $firstName = '';
    protected $lastName = '';
    protected $active = true;
    protected $secondsSinceLastAccess = 0;
    protected $timesheetEnabled = false;
    protected $cost = 0.0;
    protected $roles = [];

    final public function loadFromUsername(string $username)
    {
        global $_database,$_pagina;
        $query="SELECT *,FLOOR(EXTRACT(EPOCH FROM now() - ultimo_accesso)) AS secondiDaUltimoAccesso FROM ".static::$dbTable." WHERE username=".pg_escape_literal($username);
        if ($risUtente=$_database->query($query)) {
            if ($utente=$_database->fetch($risUtente)) {
                $_database->freeResult($risUtente);
                $this->record=clone $utente;
                $this->setDataByObject($utente);
                return true;
            }
        } else {
            $_pagina->messaggi[]=new MessaggioDebugQuery($query);
        }
        return false;
    }

    final public function loadFromCurrentSession() : bool
    {
        global $_database, $_pagina;

        if (!isset($_COOKIE['authUser']) or !$token=$_COOKIE['authUser']) {
            return false;
        }

        $query="SELECT *,FLOOR(EXTRACT(EPOCH FROM now() - ultimo_accesso)) AS secondiDaUltimoAccesso FROM ".static::$dbTable." WHERE token=".pg_escape_literal($token);

        if ($risUtente=$_database->query($query)) {
            if ($utente=$_database->fetch($risUtente)) {
                $_database->freeResult($risUtente);
                $this->record=clone $utente;
                $this->setDataByObject($utente);
            }
        } else {
            $_pagina->messaggi[]=new MessaggioDebugQuery($query);
            $_pagina->messaggi[]=new MessaggioDebug('Errore: Username o Token utente non validi');
        }

        return true;
    }

    final public function setRowCells() : void
    {
        $this->rowCells['nome'] = '<a href="utente.php?id=' . $this->getId() . '">' . $this->getFullName() . '</a>';
        $this->rowCells['email'] = $this->getEmail();
        $this->rowCells['username'] = $this->getUsername();
        $this->rowCells['permessi'] = Config::$userLevels[$this->getLevel()];
        $this->rowCells['modifica'] = '<a href="' . Config::$basePath . 'admin/utente.php?id=' . $this->getId() . '"><abbr title="Modifica utente"></abbr></a>';

        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    final public function aggiornaUltimoAccesso()
    {
        global $_pagina, $_database;

        $campi = [];
        $this->token = generaStringaCriptataSemplice($this->getUsername());
        $campi['token'] = pg_escape_literal($this->getToken());
        setcookie('authUser', $this->getToken(), time() + DURATA_SESSIONE_UTENTE_SECONDI, '/', '', true, true);
        $campi['ultimo_accesso'] = "'now'";
        $where = 'id=' . $this->getId();
        $_database->queryUpdate(static::$dbTable, $campi, $where);
        $timestamp = new DateTime();
        $this->lastAccess = $timestamp->format('d/m/Y H:i:s');
    }

    final public function eliminaSessione()
    {
        setcookie('authUser', '', time() - 1, '/', '', true, true);
    }

    final public function isActive() : bool
    {
        return $this->active;
    }

    public function setDataByObject($object): void
    {
        parent::setDataByObject($object);

        if (isset($object->username)) {
            $this->username = $object->username;
        }

        if (isset($object->password_hash)) {
            $this->passwordHash = $object->password_hash;
        }

        if (isset($object->password_salt)) {
            $this->passwordSalt = $object->password_salt;
        }

        if (isset($object->email)) {
            $this->email = $object->email;
        }

        if (isset($object->ultimo_accesso)) {
            $this->lastAccess = substr($object->ultimo_accesso, 0, 19);
        }

        if (isset($object->livello)) {
            $this->level = (int)$object->livello;
        }

        if (isset($object->token)) {
            $this->token = $object->token;
        }

        if (isset($object->nome)) {
            $this->firstName = $object->nome;
        }

        if (isset($object->cognome)) {
            $this->lastName = $object->cognome;
        }

        if (isset($object->attivo) and $object->attivo == 'f') {
            $this->attivo = false;
        }

        if (isset($object->secondidaultimoaccesso)) {
            $this->secondsSinceLastAccess = $object->secondidaultimoaccesso;
        }

        if (isset($object->timesheet_abilitato) and $object->timesheet_abilitato == 't') {
            $this->timesheetEnabled = $object->timesheet_abilitato;
        }

        if (isset($object->costo)) {
            $this->cost = number_format($object->costo, 2, ',', '.');
            $this->record->costo = $this->cost;
        }

        return;
    }

    public function getFirstName() : string
    {
        return $this->firstName;
    }

    public function getUsername() : string
    {
        return $this->username;
    }

    public function getLastName() : string
    {
        return $this->lastName;
    }

    public function getFullName() : string
    {
        return $this->firstName . ' ' . $this->lastName;
    }

    public function getEmail() : string
    {
        return $this->email;
    }

    public function getLevel() : int
    {
        return $this->level;
    }

    public function getLastAccess() : string
    {
        return $this->lastAccess;
    }

    public function getPasswordHash() : string
    {
        return $this->passwordHash;
    }

    public function getPasswordSalt() : string
    {
        return $this->passwordSalt;
    }

    public function isTimesheetEnabled() : bool
    {
        return $this->timesheetEnabled;
    }

    public function getSecondsSinceLastAccess() : int
    {
        return $this->secondsSinceLastAccess;
    }

    public function getToken() : string
    {
        return $this->token;
    }

    public function getCost() : float
    {
        return $this->cost;
    }

    public function getRoles() : array
    {
        return $this->roles;
    }

    final public function loadRoles()
    {
        global $_database, $_pagina;

        $query = <<<SQL
        SELECT ruoli_utente.id AS ruolo_id, ruoli_utente.nome AS ruolo_nome
        FROM ruoli_utente JOIN link_utenti_ruoli ON ruoli_utente.id = link_utenti_ruoli.id_ruolo
        WHERE link_utenti_ruoli.id_utente = $this->id;
        SQL;

        if (!$result = pg_query($_database->connection, $query)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nel caricare i ruoli per questo utente');
            $_pagina->messaggi[] = new MessaggioDebug($query);
        } else {
            while ($record = pg_fetch_object($result)) {
                $this->roles[$record->ruolo_id] = $record->ruolo_nome;
            }
        }
    }
}
