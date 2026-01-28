<?php
/**
 * Вывод журнала операций.
 **/

class App_Handlers_Log extends App_Core_View
{
    public function onGet()
    {
        $args = func_get_args();

        $user = $this->requireAdmin();

        $entries = $this->db->fetch("SELECT * FROM `log` ORDER BY `timestamp` DESC LIMIT 100");

        $entries = array_map(function ($row) {
            $row = $this->unpack($row);

            $row["message"] = preg_replace('@(exhibition|article) with id=(\d+)@', '<a href="/\1s/\2" target="_blank">\0</a>', $row["message"]);

            return $row;
        }, $entries);

        return $this->sendPage("log.twig", [
            "entries" => $entries,
            "breadcrumbs" => App_Common::breadcrumbs(array(
                "/admin" => "Управление",
                "/log" => "Журнал",
                )),
        ]);
    }
}
