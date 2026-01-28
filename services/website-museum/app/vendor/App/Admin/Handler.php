<?php

class App_Admin_Handler extends App_Core_View
{
    public function prepare()
    {
        $s = $this->sessionRead();

        if (!$s["is_admin"]) {
            $s["is_admin"] = true;
            $s->save();
        }

        if (empty($s["vk"]["token"]))
            throw new Framework_Errors_Unauthorized;
    }

    protected function sendPage($templateName, array $data = array(), $status = "200 OK")
    {
        $data["admin_nav"] = $this->getAdminNavBar();
        return parent::sendPage($templateName, $data, $status);
    }

    protected function getAdminNavBar()
    {
        $res = array();

        $path = $this->request->getPath();

        $config = App_Admin_Common::getConfig();
        foreach ($config["types"] as $k => $v) {
            $item = array(
                "label" => $v["title"],
                "link" => "/admin/{$k}",
                "active" => false,
                );

            if (0 === strpos($path, $item["link"]))
                $item["active"] = true;

            $res[] = $item;
        }

        return $res;
    }

    protected function getFiles($name = null)
    {
        return $this->request->files($name);
    }
}
