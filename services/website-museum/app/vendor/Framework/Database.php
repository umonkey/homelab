<?php

if (!defined("JSON_UNESCAPED_UNICODE"))
    define("JSON_UNESCAPED_UNICODE", 256);

class Framework_Database
{
    protected static $instance = null;

    protected $pdo;

    protected $debug;

    public static function getInstance()
    {
        if (self::$instance === null)
            self::$instance = self::connect();
        return self::$instance;
    }

    public static function transact($func)
    {
        $db = static::getInstance();

        try {
            $db->beginTransaction();
            $res = $func($db);
            $db->commit();
            return $res;
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }

    protected static function connect()
    {
        return new static;
    }

    public function __construct()
    {
        $this->debug = (bool)Framework_Config::get("debug_sql", false);

        $this->pdo = new PDO(
            Framework_Config::get("db_dsn"),
            Framework_Config::get("db_user"),
            Framework_Config::get("db_password"));

        $this->pdo->setAttribute(
            PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->pdo->setAttribute(
            PDO::ATTR_EMULATE_PREPARES, true);

        $commands = Framework_Config::get("sql_commands",
            array("SET NAMES utf8"));
        foreach ($commands as $command)
            $this->pdo->query($command);
    }

    public function beginTransaction()
    {
        if (!$this->pdo->inTransaction())
            $this->pdo->beginTransaction();
    }

    public function commit()
    {
        if ($this->pdo->inTransaction())
            $this->pdo->commit();
    }

    public function rollback()
    {
        if ($this->pdo->inTransaction())
            $this->pdo->rollback();
    }

    public function fetch($query, array $params = array())
    {
        return $this->query($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function fetchk($key, $query, array $params = array())
    {
        $sth = $this->query($query, $params);

        $result = array();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            $result[$row[$key]] = $row;

        return $result;
    }

    public function fetchkv($key, $value, $query, array $params = array())
    {
        $sth = $this->query($query, $params);

        $result = array();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            $result[$row[$key]] = $row[$value];

        return $result;
    }

    public function fetchv($value, $query, array $params = array())
    {
        $sth = $this->query($query, $params);

        $result = array();
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            $result[] = $row[$value];

        return $result;
    }

    public function fetchcell($query, array $params = array())
    {
        $sth = $this->query($query, $params);
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            return array_shift($row);
    }

    public function prepare($query)
    {
        return $this->pdo->prepare($query);
    }

    public function query($query, array $params = array())
    {
        $query_text = null;
        if ($this->debug) {
            if ($params) {
                $tmp = array();

                $p1 = explode("?", $query);
                $p2 = $params;
                while ($p1) {
                    $tmp[] = array_shift($p1);

                    if ($p2) {
                        if (!is_numeric($p = array_shift($p2)))
                            $p = "'{$p}'";
                        $tmp[] = $p;
                    }
                }

                $query_text = implode("", $tmp);
            } else {
                $query_text = $query;
            }
        }

        try {
            $ts = microtime(true);
            $sth = $this->pdo->prepare($query);
            $sth->execute($params);
        } catch (PDOException $e) {
            log_error("SQL: %s; error: %s; code: %s", $query, $e->getMessage(), $e->errorInfo[1]);
            if ($e->errorInfo[1] == 1062)
                throw new Framework_Errors_Duplicate("duplicate key", 500, $e);
            throw $e;
        }

        if ($this->debug)
            log_debug("SQL: %s; duration=%f seconds", $query_text, microtime(true) - $ts);

        return $sth;
    }

    /**
     * Обработка запроса пользовательской функцией.
     *
     * @param string $query SQL query.
     * @param array $params Optional query params.
     * @param callable $func User function to handle records.
     **/
    public function query_cb($query, array $params, $func)
    {
        $sth = $this->query($query, $params);
        while ($row = $sth->fetch(PDO::FETCH_ASSOC))
            $func($row);
    }

    public function getLastInsertId()
    {
        return $this->pdo->lastInsertId();
    }

    public function quote($s)
    {
        return $this->pdo->quote($s);
    }

    protected function log($message)
    {
        if ($this->debug)
            log_debug("SQL: %s", $message);
    }

    public function insert($tableName, array $fields)
    {
        $_fields = [];
        $_marks = [];
        $_params = [];

        foreach ($fields as $k => $v) {
            $_fields[] = "`{$k}`";
            $_params[] = $v;
            $_marks[] = "?";
        }

        $_fields = implode(", ", $_fields);
        $_marks = implode(", ", $_marks);

        $query = "INSERT INTO `{$tableName}` ({$_fields}) VALUES ({$_marks})";
        $sth = $this->query($query, $_params);

        return $this->pdo->lastInsertId();
    }

    public function update($tableName, array $fields, array $where)
    {
        $_set = [];
        $_where = [];
        $_params = [];

        foreach ($fields as $k => $v) {
            $_set[] = "`{$k}` = ?";
            $_params[] = $v;
        }

        foreach ($where as $k => $v) {
            $_where[] = "`{$k}` = ?";
            $_params[] = $v;
        }

        $_set = implode(", ", $_set);
        $_where = implode(" AND ", $_where);

        $query = "UPDATE `{$tableName}` SET {$_set} WHERE {$_where}";
        $sth = $this->query($query, $_params);
        return $sth->rowCount();
    }
}
