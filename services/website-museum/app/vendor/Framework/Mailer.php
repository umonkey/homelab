<?php

class Framework_Mailer
{
    protected $data;

    protected $template_name = null;

    public function __construct(array $data = array())
    {
        $this->data = $data;
    }

    public function deliver(array $recipients = array())
    {
        if (empty($recipients)) {
            $recipients = $this->getRecipients();
            if (!is_array($recipients))
                throw new RuntimeException("recipients must be an array");
        }

        if (empty($recipients))
            throw new RuntimeException("Email recipient not specified.");

        $body = $this->getHtmlBody();
        $subject = $this->getSubject();

        foreach ($recipients as $to)
            Artwall_Mail::queue_html($to, $subject, $body);

        /*
        $body = chunk_split(base64_encode($body));

        if (!($from = $from_addr = Framework_Config::get("mail_from")))
            throw new RuntimeException("Email sender not set (mail_from).");

        if ($from_name = Framework_Config::get("mail_from_name"))
            $from = sprintf("\"%s\" <%s>", $this->quote($from_name), $from);

        $headers = "From: {$from}\r\n"
                 . "Mime-Version: 1.0\r\n"
                 . "Content-Type: text/html; charset=utf-8\r\n"
                 . "Content-Transfer-Encoding: base64\r\n";

        if ($bcc = $this->getBCC())
            $headers .= "Bcc: {$bcc}\r\n";

        $res = mail ($recipients, $this->getSubject(), $body, $headers, "-f {$from_addr}");
        if ($res === false)
            throw new RuntimeException("Could not send mail to {$recipients}.");

        log_info("Sent mail to {$recipients}.");
        */
    }

    protected function getSubject()
    {
        return "(no subject)";
    }

    protected function getHtmlBody()
    {
        if (null === $this->template_name)
            throw new RuntimeException("Mailer template not set.");

        $templatePath = "templates/mailers/" . $this->template_name;

        return Framework_TemplateEngine::renderFile(
            $templatePath,
            $this->data);
    }

    protected function getRecipients()
    {
        return array();
    }

    protected function getBCC()
    {
        return null;
    }

    public function quote($text)
    {
        return sprintf("=?UTF-8?B?%s?=", base64_encode($text));
    }
}
