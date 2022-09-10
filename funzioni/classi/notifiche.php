<?php
class Notifiche extends DbTable
{
    protected static $dbTable = 'notifiche';

    final public function setTableHeaderCells() : void
    {
        return;
    }

    final public function setAssociatedResultsTableHeaderCells(): void
    {
        $this->associatedResultsTableHeaderCells = $this->tableHeaderCells;
    }

    final public static function getNotificationsForUser(int $userId) : array
    {
        global $_database, $_pagina;

        $notifications = [];

        $query = "SELECT * FROM " . static::$dbTable . " WHERE id_utente = $userId AND letta = 'f' ORDER BY timestamp_creazione DESC";

        if (!$result = pg_query($_database->connection, $query)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nel caricare le notifiche per questo utente');
            $_pagina->messaggi[] = new MessaggioDebug($query);
        } else {
            while ($record = pg_fetch_object($result)) {
                $notification = new Notifica();
                $notification->setDataByObject($record);
                $notifications[$notification->getId()] = $notification;
            }
        }

        return $notifications;
    }

    final public static function markNotificationsReadForUser(array $notificationsIds, int $userId) : bool
    {
        global $_database, $_pagina;

        $ids = implode($notificationsIds, ',');

        $query = "UPDATE " . static::$dbTable . " SET letta = 't', timestamp_modifica = 'NOW()' WHERE id_utente = $userId AND id IN($ids);";

        if (!$result = pg_query($_database->connection, $query)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nel marcare come lette le notifiche per questo utente');
            $_pagina->messaggi[] = new MessaggioDebug($query);
            return false;
        }

        return true;
    }

    final public static function addNotificationForUser(string $text, int $userId) : bool
    {
        global $_database, $_pagina;

        $text = $_database->escapeLiteral($text);

        $query = "INSERT INTO " . static::$dbTable . "(testo, id_utente) VALUES ({$text}, {$userId});";

        if (!$result = $_database->query($query)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nel marcare come lette le notifiche per questo utente');
            $_pagina->messaggi[] = new MessaggioDebug($query);
            return false;
        }

        return true;
    }

    final public static function deleteOldReadNotifications() : bool
    {
        global $_database, $_pagina;

        $query = "DELETE FROM " . static::$dbTable . " WHERE letta = 't' AND timestamp_creazione < (NOW() - '48 hours'::interval);";

        if (!$result = $_database->query($query)) {
            $_pagina->messaggi[] = new MessaggioErrore('Errore nel cancellare le vecchie notifiche');
            $_pagina->messaggi[] = new MessaggioDebug($query);
            return false;
        }

        return true;
    }
}

class Notifica extends DbTableRecord
{
    protected $text = '';
    protected $read = false;
    protected $type = 0;
    protected $userId = 0;

    final public function setRowCells() : void
    {
        return;
    }

    final public function setAssociatedResultsRowCells(): void
    {
        $this->associatedResultsRowCells = $this->rowCells;
    }

    final public function setDataByObject($object) : void
    {
        parent::setDataByObject($object);

        $this->text = $object->testo ?? '';
        $this->type = $object->type ?? 0;
        $this->userId = $object->testo ?? '';

        if (isset($object->letta) and $object->letta == 't') {
            $this->read = true;
        }

        return;
    }

    final public function getText() : string
    {
        return $this->text;
    }

    final public function isRead() : bool
    {
        return $this->read;
    }

    final public function getType() : int
    {
        return $this->type;
    }

    final public function getUserId() : int
    {
        return $this->userId;
    }
}
