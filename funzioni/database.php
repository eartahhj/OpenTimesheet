<?php
class Database
{
    private $connectionString='';
    protected $type='';
    public $connection=null;

    public function __construct()
    {
        $this->type='pg';
        $this->connectionString='user=user password=password dbname=dbname connect_timeout=5';
    }

    public function connect()
    {
        if ($this->type=='pg') {
            $this->connection=pg_connect($this->connectionString);
        }

        return $this->connection;
    }

    public function query(string $query)
    {
        if($this->type=='pg') {
            return pg_query($this->connection, $query);
        }
    }

    public function fetch($result)
    {
        if($this->type=='pg') {
            return pg_fetch_object($result);
        }
    }

    public function fetchRow($result, $row=0)
    {
        if($this->type=='pg') {
            return pg_fetch_row($result, $row);
        }
    }

    public function numRows(&$result) : int
    {
        $rows=0;
        if($this->type=='pg') {
            $rows=pg_num_rows($result);
            if($rows==-1) {
                throw new Exception('Error while calculating number of rows');
            }
        }
        return $rows;
    }

    public function freeResult(&$result)
    {
        if($this->type=='pg') {
            return pg_free_result($result);
        }
    }

    public function lastError()
    {
        if($this->type=='pg') {
            return pg_last_error($this->connection);
        }
    }

    public function escapeLiteral(string $data)
    {
        if($this->type=='pg') {
            return pg_escape_literal($this->connection, $data);
        }
    }

    public function escapeString(string $data)
    {
        if($this->type=='pg') {
            return pg_escape_string($this->connection, $data);
        }
    }

    public function generaNuovoIDTabella(string $tabella) : int
    {
        if (!$tabella) {
            $_pagina->messaggi[]=new MessaggioDebug('Errore nella generazione di un nuovo ID: Tabella non settata.');
            $_pagina->messaggi[]=new MessaggioErrore('Si è verificato un errore nella generazione di un nuovo ID.');
            return 0;
        }
        $query="SELECT nextval('{$tabella}_id_seq'::regclass);";
        if ($risNuovoID=$this->query($query)) {
            if (list($id)=$this->fetchRow($risNuovoID)) {
                return $id;
            } else {
                $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione di '.__CLASS__.'. ID non settato.');
            }
        } else {
            $_pagina->messaggi[]=new MessaggioErrore('Errore nella creazione del dominio. Generazione ID fallita.');
            $_pagina->messaggi[]=new MessaggioDebugQuery($query);
            return 0;
        }
        $this->freeResult($risNuovoID);
    }

    public function queryUpdate(string $tabella, array $campi, string $where) : bool
    {
        global $_pagina;

        $query='';
        $i=1;
        $this->query("BEGIN;");
        $query.="UPDATE $tabella SET ";
        foreach ($campi as $nome=>$valore) {
            $query.=($i>1?',':'').$nome."=".$valore;
            $i++;
        }
        $query.=' WHERE '.$where.'; ';
        if ($aggiorna=$this->query($query)) {
            $this->query("COMMIT;");
            $_pagina->messaggi[]=new MessaggioDebug('Query di aggiornamento eseguita: '.$query);
            return true;
        } else {
            $messaggio=$this->lastError();
            $this->query("ROLLBACK;");
            $_pagina->messaggi[]=new MessaggioDebugQuery($query, $messaggio);
            return false;
        }
    }

    public function queryInsert(string $tabella, array $campi) : int
    {
        global $_pagina;

        $this->query("BEGIN;");
        $query='';
        $insert_fields = '';
        $insert_values = '';
        # I campi devono avere come chiave lo stesso nome del campo nel DB
        foreach ($campi as $campo=>$valore) {
            $insert_fields .= ($insert_fields ? ',' : '') . $campo;
            $insert_values .= ($insert_values ? ',' : '') . $valore;
        }
        $query .= "INSERT INTO {$tabella}({$insert_fields}) VALUES({$insert_values}) RETURNING id;";
        if ($crea = $this->query($query)) {
            $this->query("COMMIT;");
            $_pagina->messaggi[]=new MessaggioDebug('Query di creazione eseguita: '.$query);
            $returning = $this->fetch($crea);
            return $returning->id;
        } else {
            $messaggio=$this->lastError();
            $this->query("ROLLBACK;");
            $_pagina->messaggi[]=new MessaggioDebugQuery($query, $messaggio);
            return 0;
        }
    }

    public function queryDelete(string $tabella, int $id) : bool
    {
        global $_pagina;

        $query="DELETE FROM {$tabella} WHERE id=$id";
        $this->query("BEGIN;");
        if ($elimina=$this->query($query)) {
            $this->query("COMMIT;");
            $_pagina->messaggi[]=new MessaggioDebug('Query di eliminazione eseguita: '.$query);
            return true;
        } else {
            $this->query("ROLLBACK");
            $_pagina->messaggi[]=new MessaggioDebugQuery($query, $messaggio);
            return false;
        }
    }

    public function disconnect()
    {
        if($this->type=='pg') {
            pg_close($this->connection);
        }
    }

    public function deleteRecordsByIds(string $table, array $ids) : bool
    {
        global $_pagina;

        $idsString = implode(',', $ids);

        $query = "DELETE FROM {$table} WHERE id IN ({$idsString})";

        $this->query("BEGIN;");
        if ($this->query($query)) {
            $this->query("COMMIT;");
            $_pagina->messaggi[] = new MessaggioDebug('Query di eliminazione eseguita: ' . $query);
            return true;
        } else {
            $this->query("ROLLBACK;");
            $_pagina->messaggi[] = new MessaggioDebugQuery($query, '');
            return false;
        }
    }
}
