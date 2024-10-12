<?php
/**
 * Model - DuckBrain
 *
 * Modelo ORM para objetos que hagan uso de una base de datos.
 * Depende de Libs\Database y hace uso de las constantes
 * DB_TYPE, DB_HOST, DB_NAME, DB_USER y DB_PASS.
 *
 * @author KJ
 * @website https://kj2.me
 * @licence MIT
 */

namespace Libs;

use Libs\Database;
use PDO;
use PDOException;
use Exception;
use ReflectionClass;
use ReflectionProperty;
use AllowDynamicProperties;

#[AllowDynamicProperties]
class Model {

    public           ?int    $id          = null;
    protected        array   $toNull      = [];
    static protected string  $primaryKey  = 'id';
    static protected array   $ignoreSave  = ['id'];
    static protected array   $forceSave   = [];
    static protected string  $table;
    static protected string  $tableSufix  = 's';
    static protected array   $queryVars   = [];
    static protected array   $querySelect = [
        'select'              => ['*'],
        'where'               => '',
        'from'                => '',
        'leftJoin'            => '',
        'rightJoin'           => '',
        'innerJoin'           => '',
        'orderBy'             => '',
        'groupBy'             => '',
        'limit'               => ''
    ];

    /**
     * Sirve para obtener la instancia de la base de datos.
     *
     * @return PDO
     */
    protected static function db(): PDO
    {
        if (DB_TYPE == 'sqlite')
            return Database::getInstance(
             type: DB_TYPE,
             name: DB_NAME
            );
        else
            return Database::getInstance(
                DB_TYPE,
                DB_HOST,
                DB_NAME,
                DB_USER,
                DB_PASS
            );
    }

    /**
     * Ejecuta PDO::beginTransaction para iniciar una transacción.
     * Más info: https://www.php.net/manual/es/pdo.begintransaction.php
     *
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return static::db()->beginTransaction();
    }

    /**
     * Ejecuta PDO::rollBack para deshacher los cambios de una transacción.
     * Más info: https://www.php.net/manual/es/pdo.rollback.php
     *
     * @return bool
     */
    public function rollBack(): bool
    {
        if ( static::db()->inTransaction())
            return static::db()->rollBack();
        else
            return true;
    }

    /**
     * Ejecuta PDO::commit para consignar una transacción.
     * Más info: https://www.php.net/manual/es/pdo.commit.php
     *
     * @return bool
     */
    public function commit(): bool
    {
        if (static::db()->inTransaction())
            return static::db()->commit();
        else
            return true;
    }

    /**
     * Ejecuta una sentencia SQL en la base de datos.
     *
     * @param string $query
     *   Contiene la sentencia SQL que se desea ejecutar.
     *
     * @throws Exception
     *   En caso de que la sentencia SQL falle, devolverá un error en
     *   pantalla y hará rolllback en caso de estar dentro de una
     *   transacción (ver método beginTransacction).
     *
     * @param bool $resetQuery
     *   Indica si el query debe reiniciarse o no (por defecto es true).
     *
     * @return array
     *   Contiene el resultado de la llamada SQL .
     */
    protected static function query(string $query, bool $resetQuery = true): array
    {
        $db = static::db();

        try {
            $prepared = $db->prepare($query);
            $prepared->execute(static::$queryVars);
        } catch (PDOException $e) {
            if ($db->inTransaction())
                $db->rollBack();

            $vars = json_encode(static::$queryVars);

            echo "<pre>";
            throw new Exception(
                "\nError at query to database.\n" .
                "Query: $query\n" .
                "Vars: $vars\n" .
                "Error:\n" . $e->getMessage()
            );
        }

        $result = $prepared->fetchAll();

        if ($resetQuery)
            static::resetQuery();

        return $result;
    }

    /**
     * Reinicia la configuración de la sentencia SQL.
     * @return void
     */
    protected static function resetQuery(): void
    {
        static::$querySelect = [
            'select'              => ['*'],
            'where'               => '',
            'from'                => '',
            'leftJoin'            => '',
            'rightJoin'           => '',
            'innerJoin'           => '',
            'orderBy'             => '',
            'groupBy'             => '',
            'limit'               => ''
        ];
        static::$queryVars  = [];
    }

    /**
     * Construye la sentencia SQL a partir static::$querySelect y una vez
     * construída, llama a resetQuery.
     *
     * @return string
     *   Contiene la sentencia SQL.
     */
    protected static function buildQuery(): string
    {
        $sql = 'SELECT '.join(', ', static::$querySelect['select']);

        if (static::$querySelect['from'] != '')
            $sql .= ' FROM '.static::$querySelect['from'];
        else
            $sql .= ' FROM '.static::table();

        if(static::$querySelect['innerJoin'] != '')
            $sql .= static::$querySelect['innerJoin'];

        if (static::$querySelect['leftJoin'] != '')
            $sql .= static::$querySelect['leftJoin'];

        if(static::$querySelect['rightJoin'] != '')
            $sql .= static::$querySelect['rightJoin'];

        if (static::$querySelect['where'] != '')
            $sql .= ' WHERE '.static::$querySelect['where'];

        if (static::$querySelect['groupBy'] != '')
            $sql .= ' GROUP BY '.static::$querySelect['groupBy'];

        if (static::$querySelect['orderBy'] != '')
            $sql .= ' ORDER BY '.static::$querySelect['orderBy'];

        if (static::$querySelect['limit'] != '')
            $sql .= ' LIMIT '.static::$querySelect['limit'];

        return $sql;
    }


    /**
     * Configura $queryVars para vincular un valor a un
     * parámetro de sustitución y devuelve este último.
     *
     * @param string $value
     *   Valor a vincular.
     *
     * @return string
     *   Parámetro de sustitución.
     */
    private static function bindValue(string $value): string
    {
        $index = ':v_'.count(static::$queryVars);
        static::$queryVars[$index] = $value;
        return $index;
    }

    /**
     * Crea una instancia del objeto actual a partir de un arreglo.
     *
     * @param mixed $elem
     *   Puede recibir un arreglo o un objeto que contiene los valores
     *   que tendrán sus atributos.
     *
     * @return static
     *   Retorna un objeto de la clase actual.
     */
    protected static function getInstance(array $elem = []): static
    {
        $class         = get_called_class();
        $instance      = new $class;
        $reflection    = new ReflectionClass($instance);
        $properties    = $reflection->getProperties();
        $propertyNames = array_column($properties, 'name');

        foreach ($elem as $key => $value) {
            $index = array_search($key, $propertyNames);
            if (is_numeric($index) && enum_exists($properties[$index]->getType()->getName()))
                $instance->$key = $properties[$index]->getType()->getName()::tryfrom($value);
            else
                $instance->$key = $value;
        }

        return $instance;
    }

    /**
     * Devuelve los atributos a guardar de la case actual.
     * Los atributos serán aquellos que seran public y
     * no esten excluidos en static::$ignoresave y aquellos
     * que sean private o protected pero estén en static::$forceSave.
     *
     * @return array
     *   Contiene los atributos indexados del objeto actual.
     */
    protected function getVars(): array
    {
        $reflection = new ReflectionClass($this);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
        $result     = [];

        foreach($properties as $property)
            $result[$property->name] = isset($this->{$property->name})
                          ? $this->{$property->name} : null;

        foreach (static::$ignoreSave as $del)
            unset($result[$del]);

        foreach (static::$forceSave as $value)
            $result[$value] = isset($this->$value)
                            ? $this->$value: null;

        foreach ($result as $i => $property) {
            if (gettype($property) == 'boolean')
                $result[$i] = $property ? '1' : '0';

            if ($property instanceof \UnitEnum)
                $result[$i] = $property->value;
        }

        return $result;
    }

    /**
     * Devuelve el nombre de la clase actual aunque sea una clase extendida.
     *
     * @return string
     *   Devuelve el nombre de la clase actual.
     */
    public static function className(): string
    {
        return strtolower(
            preg_replace(
                '/(?<!^)[A-Z]/', '_$0',
                substr(
                    strrchr(get_called_class(), '\\'), 1
                )
            )
        );
    }

    /**
     * Construye (a partir del nombre de la clase y el sufijo en static::$tableSufix)
     * y/o develve el nombre de la tabla de la BD en la que se alojará o
     * se aloja el objeto actual.
     *
     * @return string
     */
    protected static function table(): string
    {
        if (isset(static::$table))
            return static::$table;
        return static::className().static::$tableSufix;
    }

    /**
     * Actualiza los valores en la BD con los valores del objeto actual.
     * @return void
     */
    protected function update(): void
    {
        $atts = $this->getVars();

        foreach ($atts as $key => $value) {
            if (isset($value)) {
                if (in_array($key, $this->toNull))
                    $set[]="$key=NULL";
                else {
                    $set[]="$key=:$key";
                    static::$queryVars[':'.$key] = $value;
                }
            } else {
                if (in_array($key, $this->toNull))
                    $set[]="$key=NULL";
            }
        }

        $table = static::table();
        $pk = static::$primaryKey;
        $pkv = $this->$pk;
        $sql = "UPDATE $table SET ".join(', ', $set)." WHERE $pk='$pkv'";
        static::query($sql);
    }

    /**
     * Inserta una nueva fila en la base de datos a partir del
     * objeto actual.
     * @return void
     */
    protected function add(): void
    {
        $db = static::db();
        $atts = $this->getVars();

        foreach ($atts as $key => $value) {
            if (isset($value)) {
                $into[] = "`$key`";
                $values[] = ":$key";
                static::$queryVars[":$key"] = $value;
            }
        }

        $table = static::table();
        $sql = "INSERT INTO $table (".join(', ', $into).") VALUES (".join(', ', $values).")";
        static::query($sql);

        $pk = static::$primaryKey;
        $this->$pk = $db->lastInsertId();
    }

    /**
     * Revisa si el objeto a guardar es nuevo o no y según el resultado
     * llama a update para actualizar o add para insertar una nueva fila.
     * @return void
     */
    public function save(): void
    {
        $pk = static::$primaryKey;
        if (isset($this->$pk))
            $this->update();
        else
            $this->add();
    }

    /**
     * Elimina el objeto actual de la base de datos.
     * @return void
     */
    public function delete(): void {
        $table = static::table();
        $pk = static::$primaryKey;
        $sql = "DELETE FROM $table WHERE $pk=:$pk";

        static::$queryVars[":$pk"] = $this->$pk;
        static::query($sql);
    }

    /**
     * Define SELECT en la sentencia SQL.
     *
     * @param array $columns
     *   Columnas que se selecionarán en la consulta SQL.
     *
     * @return static
     */
    public static function select(array $columns): static
    {
        static::$querySelect['select'] = $columns;

        return new static();
    }

    /**
     * Define FROM en la sentencia SQL.
     *
     * @param array $tables
     *   Tablas que se selecionarán en la consulta SQL.
     *
     * @return static
     */
    public static function from(array $tables): static
    {
        static::$querySelect['from'] = join(', ', $tables);

        return new static();
    }

    /**
     * Define el WHERE en la sentencia SQL.
     *
     * @param string $column
     *   La columna a comparar.
     *
     * @param string $operatorOrValue
     *   El operador o el valor a comparar como igual en caso de que $value no se defina.
     *
     * @param string $value
     *   (opcional) El valor a comparar en la columna.
     *
     * @param bool $no_filter
     *   (opcional) Se usa cuando $value es una columna o un valor que no requiere filtros
     *   contra ataques SQLI (por defeco es false).
     *
     * @return static
     */
    public static function where(
        string $column,
        string $operatorOrValue,
        string $value   = null,
        bool $no_filter = false
    ): static
    {
        return static::and(
            $column,
            $operatorOrValue,
            $value,
            $no_filter
        );
    }

    /**
     * Define AND en la sentencia SQL (se puede anidar).
     *
     * @param string $column
     *   La columna a comparar.
     *
     * @param string $operatorOrValue
     *   El operador o el valor a comparar como igual en caso de que $value no se defina.
     *
     * @param string $value
     *   (opcional) El valor el valor a comparar en la columna.
     *
     * @param bool $no_filter
     *   (opcional) Se usa cuando $value es una columna o un valor que no requiere filtros
     *   contra ataques SQLI (por defecto es false).
     *
     * @return static
     */
    public static function and(
        string $column,
        string $operatorOrValue,
        string $value   = null,
        bool $no_filter = false
    ): static
    {
        if (is_null($value)) {
            $value = $operatorOrValue;
            $operatorOrValue = '=';
        }

        if (!$no_filter)
            $value = static::bindValue($value);

        if (static::$querySelect['where'] == '')
            static::$querySelect['where'] = "$column $operatorOrValue $value";
        else
            static::$querySelect['where'] .= " AND $column $operatorOrValue $value";

        return new static();
    }

    /**
     * Define OR en la sentencia SQL (se puede anidar).
     *
     * @param string $column
     *   La columna a comparar.
     *
     * @param string $operatorOrValue
     *   El operador o el valor a comparar como igual en caso de que $value no se defina.
     *
     * @param string $value
     *   (opcional) El valor el valor a comparar en la columna.
     *
     * @param bool $no_filter
     *   (opcional) Se usa cuando $value es una columna o un valor que no requiere filtros
     *   contra ataques SQLI (por defecto es false).
     *
     * @return static
     */
    public static function or(
        string $column,
        string $operatorOrValue,
        string $value   = null,
        bool $no_filter = false
    ): static
    {
        if (is_null($value)) {
            $value = $operatorOrValue;
            $operatorOrValue = '=';
        }

        if (!$no_filter)
            $value = static::bindValue($value);

        if (static::$querySelect['where'] == '')
            static::$querySelect['where'] = "$column $operatorOrValue $value";
        else
            static::$querySelect['where'] .= " OR $column $operatorOrValue $value";

        return new static();
    }

    /**
     * Define WHERE usando IN en la sentencia SQL.
     *
     * @param string $column
     *   La columna a comparar.
     *
     * @param array $arr
     *   Arreglo con todos los valores a comparar con la columna.
     *
     * @param bool $in
     *   Define si se tienen que comprobar negativa o positivamente.
     *
     * @return static
     */
    public static function where_in(
        string $column,
        array $arr,
        bool $in = true
    ): static
    {
        $arrIn = [];
        foreach($arr as $value) {
            $arrIn[] = static::bindValue($value);
        }

        if ($in)
            $where_in = "$column IN (".join(', ', $arrIn).")";
        else
            $where_in = "$column NOT IN (".join(', ', $arrIn).")";

        if (static::$querySelect['where'] == '')
            static::$querySelect['where'] = $where_in;
        else
            static::$querySelect['where'] .= " AND $where_in";

        return new static();
    }

    /**
     * Define LEFT JOIN en la sentencia SQL.
     *
     * @param string $table
     *   Tabla que se va a juntar a la del objeto actual.
     *
     * @param string $columnA
     *   Columna a comparar para hacer el join.
     *
     * @param string $operatorOrColumnB
     *   Operador o columna a comparar como igual para hacer el join en caso de que $columnB no se defina.
     *
     * @param string $columnB
     *   (opcional) Columna a comparar para hacer el join.
     *
     * @return static
     */
    public static function leftJoin(
        string $table,
        string $columnA,
        string $operatorOrColumnB,
        string $columnB = null
    ): static
    {
        if (is_null($columnB)) {
            $columnB = $operatorOrColumnB;
            $operatorOrColumnB = '=';
        }

        static::$querySelect['leftJoin'] .= ' LEFT JOIN ' . $table . ' ON ' . "$columnA$operatorOrColumnB$columnB";

        return new static();
    }

    /**
     * Define RIGHT JOIN en la sentencia SQL.
     *
     * @param string $table
     *   Tabla que se va a juntar a la del objeto actual.
     *
     * @param string $columnA
     *   Columna a comparar para hacer el join.
     *
     * @param string $operatorOrColumnB
     *   Operador o columna a comparar como igual para hacer el join en caso de que $columnB no se defina.
     *
     * @param string $columnB
     *   (opcional) Columna a comparar para hacer el join.
     *
     * @return static
     */
    public static function rightJoin(
        string $table,
        string $columnA,
        string $operatorOrColumnB,
        string $columnB = null
    ): static
    {
        if (is_null($columnB)) {
            $columnB = $operatorOrColumnB;
            $operatorOrColumnB = '=';
        }

        static::$querySelect['rightJoin'] .= ' RIGHT JOIN ' . $table . ' ON ' . "$columnA$operatorOrColumnB$columnB";

        return new static();
    }

    /**
     * Define INNER JOIN en la sentencia SQL.
     *
     * @param string $table
     *   Tabla que se va a juntar a la del objeto actual.
     *
     * @param string $columnA
     *   Columna a comparar para hacer el join.
     *
     * @param string $operatorOrColumnB
     *   Operador o columna a comparar como igual para hacer el join en caso de que $columnB no se defina.
     *
     * @param string $columnB
     *   (opcional) Columna a comparar para hacer el join.
     *
     * @return static
     */
    public static function innerJoin(
        string $table,
        string $columnA,
        string $operatorOrColumnB,
        string $columnB = null
    ): static
    {
        if (is_null($columnB)) {
            $columnB = $operatorOrColumnB;
            $operatorOrColumnB = '=';
        }

        static::$querySelect['innerJoin'] .= ' INNER JOIN ' . $table . ' ON ' . "$columnA$operatorOrColumnB$columnB";

        return new static();
    }

    /**
     * Define GROUP BY en la sentencia SQL.
     *
     * @param array $arr
     *   Columnas por las que se agrupará.
     *
     * @return static
     */
    public static function groupBy(array $arr): static
    {
        static::$querySelect['groupBy'] = join(', ', $arr);
        return new static();
    }

    /**
     * Define LIMIT en la sentencia SQL.
     *
     * @param int $offsetOrQuantity
     *   Define el las filas a ignorar o la cantidad a tomar en
     *   caso de que $quantity no esté definido.
     * @param int $quantity
     *   Define la cantidad máxima de filas a tomar.
     *
     * @return static
     */
    public static function limit(int $offsetOrQuantity, ?int $quantity = null): static
    {
        if (is_null($quantity))
            static::$querySelect['limit'] = $offsetOrQuantity;
        else
            static::$querySelect['limit'] = $offsetOrQuantity.', '.$quantity;

        return new static();
    }

    /**
     * Define ORDER BY en la sentencia SQL.
     *
     * @param string $value
     *   Columna por la que se ordenará.
     *
     * @param string $order
     *   (opcional) Define si el orden será de manera ascendente (ASC),
     *   descendente (DESC) o aleatorio (RAND).
     *
     * @return static
     */
    public static function orderBy(string $value, string $order = 'ASC'): static
    {
        if ($value == "RAND") {
            static::$querySelect['orderBy'] = 'RAND()';
            return new static();
        }

        if (!(strtoupper($order) == 'ASC' || strtoupper($order) == 'DESC'))
            $order = 'ASC';

        static::$querySelect['orderBy'] = $value.' '.$order;

        return new static();
    }

    /**
     * Retorna la cantidad de filas que hay en un query.
     *
     * @param bool $resetQuery
     *   (opcional) Indica si el query debe reiniciarse o no (por defecto es true).
     *
     * @param bool $useLimit
     *   (opcional) Permite usar limit para estabecer un máximo inical y final para contar.
     *   Requiere que se haya definido antes el límite (por defecto en false).
     *
     * @return int
     */
    public static function count(bool $resetQuery = true, bool $useLimit = false): int
    {
        if (!$resetQuery)
            $backup = [
                'select'              => static::$querySelect['select'],
                'limit'               => static::$querySelect['limit'],
                'orderBy'             => static::$querySelect['orderBy']
            ];

        if ($useLimit && static::$querySelect['limit'] != '') {
            static::$querySelect['select']  = ['1'];
            static::$querySelect['orderBy'] = '';

            $sql         = 'SELECT COUNT(1) AS quantity FROM ('.static::buildQuery().') AS counted';
            $queryResult = static::query($sql, $resetQuery);
            $result      = $queryResult[0]['quantity'];
        } else {
            static::$querySelect['select']  = ["COUNT(".static::table().".".static::$primaryKey.") as quantity"];
            static::$querySelect['limit']   = '1';
            static::$querySelect['orderBy'] = '';

            $sql = static::buildQuery();
            $queryResult = static::query($sql, $resetQuery);
            $result      = $queryResult[0]['quantity'];
        }

        if (!$resetQuery) {
            static::$querySelect['select']  = $backup['select'];
            static::$querySelect['limit']   = $backup['limit'];
            static::$querySelect['orderBy'] = $backup['orderBy'];
        }

        return $result;
    }

    /**
     * Obtiene una instancia según su primary key (generalmente id).
     * Si no encuentra una instancia, devuelve nulo.
     *
     * @param mixed $id
     *
     * @return static|null
     */
    public static function getById(mixed $id): ?static
    {
        return static::where(static::$primaryKey, $id)->getFirst();
    }

    /**
     * Realiza una búsqueda en la tabla de la instancia actual.
     *
     * @param string $search
     *   Contenido a buscar.
     *
     * @param array $in
     *   (opcional) Columnas en las que se va a buscar (null para buscar en todas).
     *
     * @return static
     */
    public static function search(string $search, array $in = null): static
    {
        if ($in == null) {
            $className = get_called_class();
            $in = array_keys((new $className())->getVars());
        }

        $search = static::bindValue($search);
        $where  = [];

        if (DB_TYPE == 'sqlite')
            foreach($in as $row)
                $where[] = "$row LIKE '%' || $search || '%'";
        else
            foreach($in as $row)
                $where[] = "$row LIKE CONCAT('%', $search, '%')";


        if (static::$querySelect['where']=='')
            static::$querySelect['where'] = join(' OR ', $where);
        else
            static::$querySelect['where'] = static::$querySelect['where'] .' AND ('.join(' OR ', $where).')';

        return new static();
    }

    /**
     * Obtener los resultados de la consulta SQL.
     *
     * @param bool $resetQuery
     *   (opcional) Indica si el query debe reiniciarse o no (por defecto es true).
     *
     * @return array
     *   Arreglo con instancias del la clase actual resultantes del query.
     */
    public static function get(bool $resetQuery = true): array
    {
        $sql = static::buildQuery();
        $result = static::query($sql, $resetQuery);

        $instances = [];

        foreach ($result as $row) {
            $instances[] = static::getInstance($row);
        }

        return $instances;
    }

    /**
     * El primer elemento de la consulta SQL.
     *
     * @param bool $resetQuery
     *   (opcional) Indica si el query debe reiniciarse o no (por defecto es true).
     *
     * @return static|null
     *   Puede retornar una instancia de la clase actual o null.
     */
    public static function getFirst(bool $resetQuery = true): ?static
    {
        static::limit(1);
        $instances = static::get($resetQuery);
        return empty($instances) ? null : $instances[0];
    }

    /**
     * Obtener todos los elementos del la tabla de la instancia actual.
     *
     * @return array
     *   Contiene un arreglo de instancias de la clase actual.
     */
    public static function all(): array
    {
        $sql = 'SELECT * FROM '.static::table();
        $result = static::query($sql);

        $instances = [];

        foreach ($result as $row)
            $instances[] = static::getInstance($row);

        return $instances;
    }

    /**
     * Permite definir como nulo el valor de un atributo.
     * Sólo funciona para actualizar un elemento de la BD, no para insertar.
     *
     * @param string|array $atts
     *   Atributo o arreglo de atributos que se definirán como nulos.
     *
     * @return void
     */
    public function setNull(string|array $atts): void
    {
        if (is_array($atts)) {
            foreach ($atts as $att)
                if (!in_array($att, $this->toNull))
                    $this->toNull[] = $att;
            return;
        }

        if (!in_array($atts, $this->toNull))
            $this->toNull[] = $atts;
    }
}
?>
