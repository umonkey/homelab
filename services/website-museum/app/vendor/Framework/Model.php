<?php

class Framework_Model implements ArrayAccess
{
    protected $data = array();

    /**
     * Fetch models using a WHERE directive.
     *
     * Returns an iterator to access records.  Memory efficient.
     *
     * @param string $where WHERE clause, can also contain ORDER, etc.
     * @param array $params Query parameters, optional.
     * @return Framework_ModelIterator Result set.
     **/
    public static function where($where, array $params = array())
    {
        $table = static::getTableName();
        $query = "SELECT * FROM `{$table}`";
        if ($where)
            $query .= " WHERE " . $where;

        return new Framework_ModelIterator($query, $params, get_called_class());
    }

    /**
     * Fetch models using an SQL query.
     *
     * Returns an iterator to access records.  Memory efficient.
     *
     * @param string $query Query text (SQL).
     * @param array $params Query parameters, optional.
     * @return array Result set.
     **/
    public static function findBySQL($query, array $params = array())
    {
        $rows = static::query($query, $params);
        return static::fromRows($rows);
    }

    public static function query($query, array $params = array())
    {
        $query = str_replace(
            "@TABLE_NAME@", static::getTableName(),
            $query);

        $sth = static::getDatabase()->query($query, $params);

        if (strpos(strtolower($query), "select") === 0)
            return $sth->fetchAll(PDO::FETCH_ASSOC);

        return $sth;
    }

    public static function findAll()
    {
        return static::findBySQL("SELECT * FROM @TABLE_NAME@");
    }

    public static function getById($id, $quiet = false)
    {
        $tableName = static::getTableName();

        $keyFieldName = static::getKeyFieldName();

        $query = "SELECT * FROM {$tableName} WHERE `{$keyFieldName}` = ?";
        $rows = static::getDatabase()->fetch($query, array($id));

        if (empty($rows)) {
            if ($quiet)
                return null;
            throw new Framework_Errors_ModelNotFound("No record with id {$id} in table {$tableName}.", 404);
        }

        return static::fromRow($rows[0]);
    }

    protected static function getDatabase()
    {
        return Framework_Database::getInstance();
    }

    protected static function getTableName()
    {
        throw new RuntimeException("Table name not set.");
    }

    protected static function getFields()
    {
        throw new RuntimeException("No fields defined for this model.");
    }

    protected static function getKeyFieldName()
    {
        return "id";
    }

    public function getKeyValue()
    {
        if ($key = static::getKeyFieldName())
            return $this->$key;
    }

    protected static function fromRows(array $rows, $func = null)
    {
        $keyFieldName = static::getKeyFieldName();

        $result = array();
        foreach ($rows as $row) {
            $obj = static::fromRow($row);
            if ($func) {
                $func($obj);
            } elseif (!empty($row[$keyFieldName])) {
                $result[$row[$keyFieldName]] = $obj;
            } else {
                $result[] = $obj;
            }
        }

        return $func ? null : $result;
    }

    public static function fromRow(array $row)
    {
        $record = new static;

        foreach (static::getFields() as $field) {
            $value = array_key_exists($field, $row)
                ? $row[$field] : null;
            $record->$field = $value;
        }

        $record->onLoad();

        return $record;
    }

    public static function fromArray(array $array)
    {
        return static::fromRow($array);
    }

    public function toArray()
    {
        $result = array();
        foreach ($this->getFields() as $field)
            $result[$field] = $this->$field;
        return $result;
    }

    public function save()
    {
        $this->validate();

        $keyFieldName = $this->getKeyFieldName();

        if (!empty($this->$keyFieldName))
            return $this->save_existing();
        else {
            if (isset($vlaues[$keyFieldName]))
                unset($values[$keyFieldName]);
            return $this->save_new();
        }
    }

    public function save_new()
    {
        $values = $this->toArray();

        $keys = array_keys($values);
        $keys_sql = "`" . implode("`, `", $keys) . "`";

        $marks = array_fill(0, count($values), "?");
        $marks_sql = implode(", ", $marks);

        $tableName = $this->getTableName();

        $db = $this->getDatabase();

        $query = "INSERT INTO {$tableName} ({$keys_sql}) VALUES ({$marks_sql})";
        // error_log($query);

        $db->query($query, array_values($values));

        $keyFieldName = $this->getKeyFieldName();
        if ($keyFieldName and !$this->$keyFieldName) {
            $id = $db->getLastInsertId();
            $this->$keyFieldName = $id;
        }
    }

    public function save_existing()
    {
        $values = $this->toArray();

        $keyFieldName = $this->getKeyFieldName();
        $id = $values[$keyFieldName];
        unset($values[$keyFieldName]);

        $updates = array();
        foreach (array_keys($values) as $fieldName)
            $updates[] = "`{$fieldName}` = ?";

        $params = array_values($values);

        $params[] = $id;

        $tableName = $this->getTableName();
        $query = "UPDATE {$tableName} SET " . implode(", ", $updates) . " WHERE `{$keyFieldName}` = ?";
        $this->getDatabase()->query($query, $params);
    }

    protected function validate()
    {
    }

    protected function validate_nonempty(array $fields)
    {
        foreach ($fields as $field => $message)
            if (empty($this->$field))
                throw new Framework_Errors_FormError($message);
    }

    public function delete()
    {
        $tableName = static::getTableName();
        $keyFieldName = static::getKeyFieldName();

        if (!isset($this->$keyFieldName))
            throw new RuntimeException("This record does not even exist, can't delete.");

        $query = "DELETE FROM {$tableName} WHERE `{$keyFieldName}` = ?";
        $params = array($this->$keyFieldName);

        static::getDatabase()->query($query, $params);

        $this->$keyFieldName = null;
    }

    /**
     * Update the record with form data.
     *
     * Only handles form values wich have corresponding model fields,
     * the rest is silently ignored.  Fields that aren't in the form
     * aren't updated.
     *
     * Override this method to provide complex logic, like storing
     * only salt-hashed passwords while the user enters them plain.
     *
     * @param array $form Form values.
     **/
    public function update(array $form, $save=false)
    {
        $fields = $this->getFields();

        $changes = false;

        foreach ($form as $k => $v) {
            if (in_array($k, $fields)) {
                if (strval($v) != strval($this->$k)) {
                    $this->$k = $v;
                    $changes = true;
                }
            }
        }

        if ($save)
            $this->save();

        return $changes;
    }

    public function __get($key)
    {
        if (array_key_exists($key, $this->data))
            return $this->data[$key];
        return null;
    }

    public function __set($key, $value)
    {
        $this->data[$key] = $value;
    }

    public function __unset($key)
    {
        if (array_key_exists($key, $this->data))
            unset($this->data[$key]);
    }

    public function __isset($key)
    {
        return array_key_exists($key, $this->data);
    }

    // ArrayAccess methods

    public function offsetExists($k)
    {
        return array_key_exists($k, $this->data);
    }

    public function offsetGet($k)
    {
        if ($this->offsetExists($k))
            return $this->data[$k];
    }

    public function offsetSet($k, $v)
    {
        $this->data[$k] = $v;
    }

    public function offsetUnset($k)
    {
        unset($this->data[$k]);
    }

    protected function onLoad()
    {
    }

    public function __debugInfo()
    {
        return $this->data;
    }

    public function __call($methodName, array $args)
    {
        throw new RuntimeException(sprintf("method %s::%s not implimented", get_class($this), $methodName));
    }
}
