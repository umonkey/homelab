<?php

require_once APP_ROOT . "/vendor/Parsedown.php";

class App_Core_Markdown extends Parsedown
{
    protected $base_url;

    public function __construct($base_url = "")
    {
        $this->base_url = $base_url;
        // $this->BlockTypes["/"][] = "Embed";
    }

    protected function lines(array $lines)
    {
        $newLines = array();

        foreach ($lines as $line) {
            if (preg_match('@^/files/(\d)/original\.(jpg|png|gif)$@i', trim($line), $m)) {
                $line = $this->embedImage($m[1], $m[2]);
            } elseif (preg_match('@^(http|/).*\.(jpg|png|gif)$@', trim($line))) {
                $line = "<figure><img src='{$line}' alt='image'/></figure>";
            } elseif (preg_match('@youtube\.com/watch\?@', $line)) {
                $line = $this->embedYouTube1($line);
            } elseif (preg_match('@youtu\.be/([^?]+)@', $line, $m)) {
                $line = $this->embedYouTube2($m[1]);
            }

            $newLines[] = $line;
        }

        return parent::lines($newLines);
    }

    protected function embedYouTube1($line)
    {
        $url = App_Core_Common::parseURL($line);

        if (!empty($url["args"]["v"]))
            return $this->embedYouTube2($url["args"]["v"]);

        return $line;
    }

    protected function embedYouTube2($vid)
    {
        $html = "<iframe width='560' height='315' src='https://www.youtube.com/embed/{$vid}' frameborder='0' allowfullscreen></iframe>";
        return $html;
    }

    protected function embedImage($id, $ext)
    {
        $variants = array("lg.jpg", "original.{$ext}");
        foreach ($variants as $var) {
            $path = "files/{$id}/{$var}";
            if (is_readable(get_doc_path($path))) {
                return "<figure><img src='/{$path}' alt='image'/></figure>";
            } else {
                log_warning("markdown: file %s not found", $path);
            }
        }

        return "(файл не найден)";
    }

    protected function blockEmbed($Line)
    {
        $text = trim(substr($Line["text"], 1));

        if (preg_match('@(.+\.(jpg|png))$@', $text, $m))
            return $this->embed_image($m[1]);

        if (preg_match('@^(https?://.+)@', $text, $m))
            return $this->embed_link($m[1]);

        debug($Line);
    }

    protected function embed_link($link)
    {
        $parts = explode("/", $link);

        if (preg_match('@https?://youtu\.be/(\S+)@', $link, $m))
            return $this->embed_youtube($m[1]);

        if (preg_match('@youtube\.com/watch.+v=(.+)@', $link, $m))
            return $this->embed_youtube($m[1]);

        $block = array(
            "markup" => "<a href='{$link}'>{$link}</a>",
            );

        return $block;
    }

    protected function embed_youtube($vid)
    {
        $html = "<div class='youtube player_16x9'>";
        $html .= "<div>";
        $html .= "<iframe class='video youtube' src='https://www.youtube.com/embed/{$vid}?&rel=0&showinfo=0' frameborder='0' allowfullscreen='allowfullscreen'></iframe>";
        $html .= "</div>";
        $html .= "</div>";

        $block = array(
            "name" => "wtf",
            "markup" => $html,
            );

        return $block;
    }

    protected function embed_image($link)
    {
        $name = basename($link);

        if (false === strpos($link, "/"))
            $link = $this->base_url . $link;

        $block = array(
            "markup" => "<figure><img src='{$link}' alt='{$name}'/></figure>",
            );

        return $block;
    }

    public static function format($text, $base_url = "")
    {
        $parser = new self($base_url);
        $text = $parser->text($text);

        // Немного примитивной типографики.
        $text = str_replace(" -- ", "&nbsp;— ", $text);
        $text = str_replace(".  ", ".&nbsp; ", $text);

        return $text;
    }
}
