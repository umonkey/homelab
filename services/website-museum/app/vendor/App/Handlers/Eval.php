<?php
/**
 * Форма оценки качества услуг.
 **/

class App_Handlers_Eval extends App_Core_View
{
    public function onGet()
    {
        $thanks = @$_GET["thanks"] == "yes";

        return $this->sendPage("eval.twig", [
            "thanks" => $thanks,
        ]);
    }

    public function onPost()
    {
        $ip = $_SERVER["REMOTE_ADDR"];
        $agent = $_SERVER["HTTP_USER_AGENT"];
        $answers = $_POST["answer"];
        $key = "eval_" . uniqid();

        $more = serialize([
            "answers" => $answers,
            "ip" => $ip,
            "agent" => $agent,
        ]);

        if (!($lb = $this->db->fetchcell("SELECT MAX(`rb`) FROM `nodes`")))
            $lb = 0;

        $now = strftime("%Y-%m-%d %H:%M:%S");

        $this->db->insert("nodes", [
            "lb" => $lb + 1,
            "rb" => $lb + 2,
            "type" => "eval",
            "created" => $now,
            "updated" => $now,
            "key" => $key,
            "published" => 0,
            "more" => $more,
        ]);

        $this->sendMail($answers);

        return $this->redirect("/eval?thanks=yes");
    }

    protected function sendMail(array $answers)
    {
        $text = "";

        for ($idx = 1; $idx < 17; $idx++) {
            $value = empty($answers[$idx]) ? null : $answers[$idx];
            if ($value == "yes")
                $value = "да";
            elseif ($value == "no")
                $value = "нет";

            if ($idx == 16) {
                if ($value == "f") {
                    $value = "женщина";
                } elseif ($value == "m") {
                    $value = "мужчина";
                } else {
                    $value = "пол не указан";
                }
            }

            if (empty($value))
                $value = "-";

            $text .= "Вопрос №{$idx}: {$value}\n\n";
        }

        App_Core_Mail::send([
            "from" => $this->config("email_from", "webmaster@seb-museum.ru"),
            "from_name" => $form["name"],
            "reply_to" => $form["from"],
            "to" => $this->config("email_to", "staff@seb-museum.ru"),
            "cc" => "hex+museum@umonkey.net",
            "bcc" => $this->config("email_bcc"),
            "subject" => "Анкета по качеству услуг",
            "text" => $text,
            "html" => null,
        ]);
    }
}
