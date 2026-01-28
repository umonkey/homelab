<?php

class Framework_Session implements ArrayAccess
{
    const COOKIE = "session_id";

    protected $id;

    protected $data;

    public function __construct(array $defaults = array())
    {
        $this->data = array_merge($defaults, self::readData());
    }

    public function save()
    {
        self::saveData($this->data);
    }

    // ArrayAccess methods

    public function offsetExists($k)
    {
        return array_key_exists($k, $this->data);
    }

    public function &offsetGet($k)
    {
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

    /**
     * Чтение содержимого сессии.
     *
     * Возвращает пустой массив если сессия не запущена.
     *
     * @param array $defaults Данные по умолчанию.
     * @return array Содержимое сессии.
     **/
    public static function readData(array $defaults = array())
    {
        $data = array();

        if (isset($_COOKIE[self::COOKIE])) {
            $sid = $_COOKIE[self::COOKIE];

            $db = Framework_Database::getInstance();
            $rows = $db->fetch("SELECT `data` FROM `sessions` WHERE `id` = ?", array($sid));
            if ($rows)
                $data = unserialize($rows[0]["data"]);
        }

        return array_merge($defaults, $data);
    }

    /**
     * Сохранение сессионных данных.
     *
     * Создаёт сессию при необходимости, устанавливает куки.
     *
     * @param array $data Содержимое сессии.
     * @return void
     **/
    public static function saveData(array $data)
    {
        if (isset($_COOKIE[self::COOKIE]))
            $sid = $_COOKIE[self::COOKIE];
        else {
            $sid = self::mkid();
            setcookie(self::COOKIE, $sid, time() + 60 * 60 * 24 * 30, "/");
        }

        $db = Framework_Database::getInstance();
        $db->query("REPLACE INTO `sessions` (`id`, `updated`, `data`) VALUES (?, ?, ?)",
            array($sid, strftime("%Y-%m-%d %H:%M:%S"), serialize($data)));
    }

    protected static function mkid()
    {
        return sha1(uniqid("session", true));
    }

    public static function with($func)
    {
        $data = self::read();
        $func($data);
        self::save($data);
    }
}
