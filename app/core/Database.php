<?php

class Database
{
    private static ?PDO $conn = null;

    public static function connect(): PDO
    {
        if (self::$conn === null) {
            $dbPath = __DIR__ . '/../../database/load_monitor.sqlite';
            $dir = dirname($dbPath);
            if (!is_dir($dir)) {
                mkdir($dir, 0777, true);
            }

            self::$conn = new PDO(
                "sqlite:" . $dbPath,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                ]
            );

            // SQLite pragmas
            self::$conn->exec('PRAGMA journal_mode=WAL');
            self::$conn->exec('PRAGMA foreign_keys=ON');

            // Register MySQL-compatible functions
            self::registerMySQLCompat(self::$conn);
        }

        return self::$conn;
    }

    /**
     * Register MySQL-compatible SQL functions so existing queries work on SQLite.
     */
    private static function registerMySQLCompat(PDO $pdo): void
    {
        // NOW()
        $pdo->sqliteCreateFunction('NOW', function () {
            return date('Y-m-d H:i:s');
        }, 0);

        // CURDATE()
        $pdo->sqliteCreateFunction('CURDATE', function () {
            return date('Y-m-d');
        }, 0);

        // TIMESTAMPDIFF(unit, start, end)
        $pdo->sqliteCreateFunction('TIMESTAMPDIFF', function ($unit, $start, $end) {
            if ($start === null || $end === null) return null;
            $s = strtotime($start);
            $e = strtotime($end);
            if ($s === false || $e === false) return null;
            $diff = $e - $s;
            switch (strtoupper($unit)) {
                case 'SECOND': return $diff;
                case 'MINUTE': return intdiv($diff, 60);
                case 'HOUR':   return intdiv($diff, 3600);
                case 'DAY':    return intdiv($diff, 86400);
                default:       return $diff;
            }
        }, 3);

        // DATE_FORMAT(date, mysqlFormat)
        $pdo->sqliteCreateFunction('DATE_FORMAT', function ($date, $fmt) {
            if ($date === null) return null;
            $ts = strtotime($date);
            if ($ts === false) return null;
            $map = [
                '%Y' => 'Y', '%y' => 'y', '%m' => 'm', '%c' => 'n',
                '%d' => 'd', '%e' => 'j', '%H' => 'H', '%h' => 'h',
                '%i' => 'i', '%s' => 's', '%p' => 'A', '%W' => 'l',
                '%M' => 'F', '%b' => 'M', '%a' => 'D',
            ];
            $phpFmt = str_replace(array_keys($map), array_values($map), $fmt);
            return date($phpFmt, $ts);
        }, 2);

        // DATEDIFF(a, b) — days
        $pdo->sqliteCreateFunction('DATEDIFF', function ($a, $b) {
            if ($a === null || $b === null) return null;
            $ta = strtotime($a);
            $tb = strtotime($b);
            if ($ta === false || $tb === false) return null;
            return (int)(($ta - $tb) / 86400);
        }, 2);

        // MONTH(date)
        $pdo->sqliteCreateFunction('MONTH', function ($d) {
            if ($d === null) return null;
            $t = strtotime($d);
            return $t === false ? null : (int)date('n', $t);
        }, 1);

        // YEAR(date)
        $pdo->sqliteCreateFunction('YEAR', function ($d) {
            if ($d === null) return null;
            $t = strtotime($d);
            return $t === false ? null : (int)date('Y', $t);
        }, 1);

        // DAY(date)
        $pdo->sqliteCreateFunction('DAY', function ($d) {
            if ($d === null) return null;
            $t = strtotime($d);
            return $t === false ? null : (int)date('j', $t);
        }, 1);

        // CONCAT(a, b, ...) — variadic
        $pdo->sqliteCreateFunction('CONCAT', function () {
            $args = func_get_args();
            foreach ($args as $a) {
                if ($a === null) return null;
            }
            return implode('', $args);
        }, -1);
    }
}
