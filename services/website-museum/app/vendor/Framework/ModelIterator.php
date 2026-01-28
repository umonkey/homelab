<?php

class Framework_ModelIterator implements Iterator
{
    protected $query;
    protected $params;
    protected $sth;
    protected $func;
    protected $count;
    protected $key;
    protected $value;

    public function __construct($query, $params, $class = null)
    {
        $this->query = $query;
        $this->params = $params;
        $this->class = $class;

        $this->sth = null;
        $this->count = 0;
        $this->totalCount = null;
    }

    public function __toString()
    {
        return sprintf("<ModelIterator class=%s query=%s>", $this->class, $this->query);
    }

    public function __debugInfo()
    {
        return array(
            "class" => $this->class,
            "query" => $this->query,
            "params" => $this->params,
            );
    }

    public function rewind()
    {
        $this->count = 0;
        $this->sth = Framework_Database::getInstance()->query($this->query, $this->params);
        $this->fetch();
    }

    public function current()
    {
        return $this->value;
    }

    public function first()
    {
        foreach ($this as $item)
            return $item;
    }

    public function key()
    {
        return $this->idx;
    }

    public function next()
    {
        $this->fetch();
    }

    public function valid()
    {
        return $this->value !== false;
    }

    protected function fetch()
    {
        if (!$this->sth)
            throw new RuntimeException("fetch while not in select");

        $this->value = $this->sth->fetch(PDO::FETCH_ASSOC);
        $this->count++;

        if ($this->value !== false and $this->class) {
            $class = $this->class;
            $this->value = $class::fromRow($this->value);
            $this->key = $this->value->getKeyValue();
        }
    }

    public function all()
    {
        $res = array();
        foreach ($this as $row)
            $res[] = $row;
        return $res;
    }

    /**
     * Return the total amount of selected records.
     *
     * Caches the value.
     *
     * @return int The number of records.
     **/
    public function getCount()
    {
        if ($this->totalCount === null) {
            if (preg_match('@(FROM.+)$@', $this->query, $m)) {
                $query = "SELECT COUNT(1) " . $m[1];
                $sth = Framework_Database::getInstance()->query($query, $this->params);
                if ($row = $sth->fetch())
                    $this->totalCount = (int)$row[0];
            }
        }

        return $this->totalCount;
    }
}
