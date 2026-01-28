<?php

class App_Core_Model extends Framework_Model
{
    protected static function getTableName()
    {
        return static::$tableName;
    }

    protected static function getFields()
    {
        return static::$fieldNames;
    }

    protected static function getDatabase()
    {
        return Framework_Database::getInstance();
    }

    protected function validate()
    {
        $defaults = $this->getDefaults();
        foreach ($defaults as $k => $v)
            if (!isset($this->$k) or $this->$k === null)
                $this->$k = $v;
    }

    protected function getDefaults()
    {
        return array();
    }

    protected static function fetch($query, array $params = array())
    {
        $db = Framework_Database::getInstance();
        $query = str_replace("@TABLE_NAME@", static::$tableName, $query);
        return $db->fetch($query, $params);
    }

    /**
     * Get some recently created documents.
     *
     * Basic implementation.  Sorts objects by key.  No visibility
     * control.  Suitable for admin UI or simple objects.  Probably
     * needs to be subclassed.
     *
     * @param int $count The number of objects to show, all if null.
     * @return array Recent object iterator.
     **/
    public static function findRecent($count = null)
    {
        $where = "1";

        $keyName = static::getKeyFieldName();
        $where .= " ORDER BY {$keyName} DESC";

        if ($count)
            $where .= " LIMIT {$count}";

        return static::where($where);
    }

    public static function findPublished()
    {
        $where = "1";

        if (in_array("published", static::getFields()))
            $where .= " AND `published` = 1";

        $where .= " ORDER BY `id`";

        return self::where($where);
    }

    public function forTemplate()
    {
        $res = $this->toArray();

        if (isset($res["gallery"]))
            $res["gallery_images"] = App_Common::prepareGallery($this["gallery"]);

        if (!empty($res["small_image"]))
            $res["small_image_parsed"] = App_Core_Common::getListImageLink($this->small_image);

        if (empty($res["small_image"]) and !empty($res["gallery_images"])) {
            $img = $res["gallery_images"][0]["small"];
            $res["small_image"] = $img;
            $res["small_image_parsed"] = $img;
        }

        return $res;
    }

    public function forTemplate2()
    {
        return $this->forTemplate();
    }

    public function __call($method, array $args)
    {
        $class = get_class($this);
        throw new RuntimeException("method {$class}::{$method} not implimented");
    }

    public static function whereTemplate($query, array $params = array())
    {
        $res = array();

        $rows = static::where($query, $params);
        foreach ($rows as $row)
            $res[] = $row->forTemplate();

        return $res;
    }
}
