<?php
declare(strict_types=1);

namespace FMGlobal\Repositories;

final class Database
{
    private static ?\mysqli $connection = null;

    public static function connection(): \mysqli
    {
        if (self::$connection === null) {
            $config = require FM_ROOT . '/config/database.php';
            self::$connection = new \mysqli($config['host'], $config['user'], $config['password'], $config['database']);
            self::$connection->set_charset($config['charset']);
        }
        return self::$connection;
    }
}
