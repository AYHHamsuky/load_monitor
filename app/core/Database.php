<?php

class Database
{
    private static ?PDO $conn = null;

    public static function connect(): PDO
    {
        if (self::$conn === null) {
            $driver = getenv('DB_DRIVER') ?: 'sqlite';

            if ($driver === 'mysql') {
                $host = getenv('DB_HOST') ?: 'localhost';
                $port = getenv('DB_PORT') ?: '3306';
                $name = getenv('DB_NAME') ?: 'load_monitor';
                $user = getenv('DB_USER') ?: 'root';
                $pass = getenv('DB_PASS') ?: '';

                self::$conn = new PDO(
                    "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4",
                    $user,
                    $pass,
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
                    ]
                );
            } else {
                // SQLite — local development fallback
                $dbPath = __DIR__ . '/../../database/load_monitor.sqlite';
                $dir    = dirname($dbPath);
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

                self::$conn->exec('PRAGMA journal_mode=WAL');
                self::$conn->exec('PRAGMA foreign_keys=ON');
                self::registerMySQLCompat(self::$conn);
            }
        }

        return self::$conn;
    }

    /**
     * Register MySQL-compatible SQL functions for SQLite (local dev only).
     * Not called when using MySQL — these are all native MySQL functions.
     */
    private static function registerMySQLCompat(PDO $pdo): void
    {
        $pdo->sqliteCreateFunction('NOW', fn() => date('Y-m-d H:i:s'), 0);

        $pdo->sqliteCreateFunction('CURDATE', fn() => date('Y-m-d'), 0);

        $pdo->sqliteCreateFunction('TIMESTAMPDIFF', function ($unit, $start, $end) {
            if ($start === null || $end === null) return null;
            $s    = strtotime($start);
            $e    = strtotime($end);
            if ($s === false || $e === false) return null;
            $diff = $e - $s;
            return match (strtoupper($unit)) {
                'SECOND' => $diff,
                'MINUTE' => intdiv($diff, 60),
                'HOUR'   => intdiv($diff, 3600),
                'DAY'    => intdiv($diff, 86400),
                default  => $diff,
            };
        }, 3);

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
            return date(str_replace(array_keys($map), array_values($map), $fmt), $ts);
        }, 2);

        $pdo->sqliteCreateFunction('DATEDIFF', function ($a, $b) {
            if ($a === null || $b === null) return null;
            $ta = strtotime($a);
            $tb = strtotime($b);
            if ($ta === false || $tb === false) return null;
            return (int)(($ta - $tb) / 86400);
        }, 2);

        $pdo->sqliteCreateFunction('MONTH', function ($d) {
            $t = $d !== null ? strtotime($d) : false;
            return $t === false ? null : (int)date('n', $t);
        }, 1);

        $pdo->sqliteCreateFunction('YEAR', function ($d) {
            $t = $d !== null ? strtotime($d) : false;
            return $t === false ? null : (int)date('Y', $t);
        }, 1);

        $pdo->sqliteCreateFunction('DAY', function ($d) {
            $t = $d !== null ? strtotime($d) : false;
            return $t === false ? null : (int)date('j', $t);
        }, 1);

        $pdo->sqliteCreateFunction('CONCAT', function () {
            $args = func_get_args();
            foreach ($args as $a) {
                if ($a === null) return null;
            }
            return implode('', $args);
        }, -1);
    }
}
