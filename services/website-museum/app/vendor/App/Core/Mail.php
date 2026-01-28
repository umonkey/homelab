<?php

class App_Core_Mail
{
    public static function send(array $msg)
    {
        $msg = array_merge(array(
            "from" => null,
            "from_name" => null,
            "to" => null,
            "to_name" => null,
            "reply_to" => null,
            "subject" => "no subject",
            "text" => null,
            "html" => null,
            "cc" => null,
            "bcc" => null,
            ), $msg);

        $headers = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Transfer-Encoding: base64\r\n";

        if ($msg["from_name"])
            $headers .= sprintf("From: \"%s\" <%s>\r\n", self::quote($msg["from_name"]), self::quote($msg["from"]));
        else
            $headers .= sprintf("From: <%s>\r\n", self::quote($msg["from"]));

        if ($msg["cc"])
            $headers .= sprintf("Cc: %s\r\n", self::quote($msg["cc"]));

        if ($msg["bcc"])
            $headers .= sprintf("Bcc: %s\r\n", self::quote($msg["bcc"]));

        if ($msg["reply_to"])
            $headers .= sprintf("Reply-To: %s\r\n", self::quote($msg["reply_to"]));

        if ($msg["html"]) {
            $body = chunk_split(base64_encode($msg["html"]));
            $headers .= "Content-type: text/html; charset=utf-8\r\n";
        } elseif ($msg["text"]) {
            $body = chunk_split(base64_encode($msg["text"]));
            $headers .= "Content-type: text/plain; charset=utf-8\r\n";
        } else {
            throw new RuntimeException("mail text not specified");
        }

        $res = mail($msg["to"], self::quote($msg["subject"]), $body, $headers, "-f {$msg["from"]}");
        if ($res === false) {
            log_error("mail: sending failed.");
            throw new RuntimeException("mail could not be sent");
        }

        log_info("sent mail to {$msg["to"]}");
    }

    public static function quote($text)
    {
        for ($idx = 0; $idx < strlen($text); $idx++) {
            $o = ord($text[$idx]);
            if ($o < 32 or $o >= 128)
                return sprintf("=?UTF-8?B?%s?=", base64_encode(trim($text)));
        }

        return trim($text);
    }
}
