<?php
const TIMESHEET_HOURLY_COST_EURO = 65;

class TimeLibrary
{
    public static $daysOfWeekAsStrings = [1 => 'Lunedì', 2 =>'Martedì', 3 => 'Mercoledì', 4 => 'Giovedì', 5 => 'Venerdì', 6 => 'Sabato', 7 => 'Domenica'];

    public static function getTimeInReadableFormatByMinutes(int $minutes)
    {
        $negativeTime = false;

        if ($minutes < 0) {
            $negativeTime = true;
        }

        $minutes = abs($minutes);

        if ($minutes >= 60) {
            $hours = self::getHoursFromMinutes($minutes);

            if ($hours < 10) {
                $hours = '0' . $hours;
            }
            $minutes = $minutes % 60;

            if ($minutes < 10) {
                $minutes = '0' . $minutes;
            }
        } else {
            $hours = '00';

            if ($minutes == 0) {
                $minutes = '00';
            } elseif ($minutes < 10) {
                $minutes = '0' . $minutes;
            }
        }

        $totalTime = $hours . ':' . $minutes;

        if ($negativeTime) {
            $totalTime = '-' . $totalTime;
        }

        return $totalTime;
    }

    public static function getDaysFromMinutes(int $minutes) : int
    {
        if ($minutes < 3600) {
            return 0;
        }

        return intdiv($minutes, 60 * 24);
    }

    public static function getHoursFromMinutes(int $minutes) : int
    {
        if ($minutes < 60) {
            return 0;
        }

        return intdiv($minutes, 60);
    }

    public static function getEurosByMinutes(int $minutes) : string
    {
        $euros = ($minutes / 60) * TIMESHEET_HOURLY_COST_EURO;
        $euros = number_format($euros, 2, ',', '.');
        return $euros;
    }

    final public static function isTimeFormatValid(string $time) : bool
    {
        if (strpos($time, '-') !== false) {
            if (strpos($time, '-') != 0) {
                return false;
            }
            $time = substr($time, 1);
        }

        if (substr_count($time, ':') != 1) {
            return false;
        }

        return true;
    }

    final public static function getDayOfWeekAsStringFromNumber(int $dayNumber)
    {
        return self::$daysOfWeekAsStrings[$dayNumber];
    }

    final public static function getMinutesFromTimeInterval(string $timeInterval) : int
    {
        global $_database, $_pagina;

        $minutes = 0;
        $query = "SELECT EXTRACT(EPOCH FROM '{$timeInterval}'::interval)/60 AS minutes";

        if (!$result = $_database->query($query)) {
            $_pagina->messaggi[] = new MessaggioErrore("Errore nel convertire l'intervallo in minuti.");
            $_pagina->messaggi[] = new MessaggioDebug($query);
        } else {
            if ($record = $_database->fetch($result)) {
                $minutes = intval($record->minutes);
            }
        }

        return $minutes;
    }

    final public static function isDateFormatValid(string $date, string $format = 'd/m/Y') : bool
    {
        $dateFormat = DateTime::createFromFormat($format, $date);
        return $dateFormat && ($dateFormat->format($format) == $date);
    }
}
