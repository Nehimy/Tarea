<?php
/**
 * Database - DuckBrain
 *
 * Clase diseñada para crear y devolver una única instancia PDO (database).
 *
 * @author KJ
 * @website https://kj2.me
 * @licence MIT
 */

namespace Libs;

use PDO;
use PDOException;
use Exception;

class Database extends PDO {
    static private array $databases = [];

    private function __construct() {}

    /**
     * Devuelve una instancia homogénea (singlenton) de la base de datos (PDO).
     *
     * @return PDO
     */
    static public function getInstance(
        string $type = 'mysql',
        string $host = 'localhost',
        string $name = '',
        string $user = '',
        string $pass = '',
    ): PDO
    {
        $key = $type.'/'.$host.'/'.$name.'/'.$user;
        if (empty(static::$databases[$key])) {

            if ($type == 'sqlite') {
                $dsn = $type .':'. $name;
            } else
                $dsn = $type.':dbname='.$name.';host='.$host;

            try {
                static::$databases[$key] = new PDO($dsn, $user, $pass);
            } catch (PDOException $e) {
                throw new Exception(
                    'Error at connect to database: ' . $e->getMessage()
                );
            }

            static::$databases[$key]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            static::$databases[$key]->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return static::$databases[$key];
    }
}
?>
