<?php

class App_Mail extends App_Core_View
{
    public function onGet()
    {
        $path = array();
        $path[] = array(
            "link" => "/",
            "label" => "Себежский музей",
            );
        $path[] = array(
            "link" => "/mail",
            "label" => "Связь",
            );

        return $this->sendPage("mail.twig", array(
            "page_title" => "Связь с музеем",
            "mail_sent" => @$_GET["status"] == "sent",
            ));
    }

    public function onPost()
    {
        $form = $this->getForm(array(
            "name" => null,
            "from" => null,
            "message" => null,
            "pdata" => null,
            "captcha" => null,
            ));

        $s = new Framework_Session;
        if (empty($s["captcha"]) or $s["captcha"] != $form["captcha"])
            return $this->sendJSON(array(
                "message" => "Не те цифры.",
                ));

        $text = "Отправитель: {$form["name"]}\n\n";
        if ($form["from"])
            $text .= "Обратный адрес: {$form["from"]}\n\n";
        $text .= $form["message"];

        $html = sprintf("<p>Отправитель: %s</p>", htmlspecialchars($form["name"]));
        if (!empty($form["from"]))
            $html .= sprintf("<p>Обратный адрес: %s</p>", htmlspecialchars($form["from"]));
        $html .= sprintf("<pre>%s</pre>", htmlspecialchars($form["message"]));

        App_Core_Mail::send(array(
            "from" => $this->config("email_from", "webmaster@seb-museum.ru"),
            "from_name" => $form["name"],
            "reply_to" => $form["from"],
            "to" => $this->config("email_to", "staff@seb-museum.ru"),
            "cc" => $form["from"],
            "bcc" => $this->config("email_bcc"),
            "subject" => $this->config("email_subject", "Сообщение с сайта музея"),
            "text" => $text,
            "html" => $html,
            ));

        return $this->sendJSON(array(
            "redirect" => "/mail?status=sent",
            ));
    }
}
