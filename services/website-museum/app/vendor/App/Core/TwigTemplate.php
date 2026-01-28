<?php

class App_Core_TwigTemplate extends Framework_TwigTemplate
{
    protected function prepare()
    {
        parent::prepare();

        $this->environment->addFilter(new Twig_SimpleFilter("sklo", function ($number, $one, $two, $many) {
            if (substr($number, -2) == "11")
                return $many;

            if (($x = substr($number, -1)) == "1")
                return $one;
            elseif ($x == "2" or $x == "3" or $x == "4")
                return $two;
            return $many;
        }));

        $this->environment->addFilter(new Twig_SimpleFilter("cost", function ($cost) {
            if (!floatval($cost))
                return "";
            return sprintf("%.2f", $cost);
        }));

        $this->environment->addFilter(new Twig_SimpleFilter("price", function ($string) {
            if (empty($string))
                return "";

            $n = number_format($string, 0);
            return $n . " р.";
        }));

        $this->environment->addFilter(new Twig_SimpleFilter("date_ddmmyy", function ($string) {
            if (!preg_match('@^(\d{4})-(\d{2})-(\d{2})@', $string, $m))
                return $string;
            return $m[3] . ".". $m[2] . "." . substr($m[1], 2);
        }));

        $this->environment->addFilter(new Twig_SimpleFilter("date_ddmm", function ($string) {
            if (!preg_match('@^(\d{4})-(\d{2})-(\d{2})@', $string, $m))
                return $string;
            return $m[3] . ".". $m[2];
        }));

        $this->environment->addFilter(new Twig_SimpleFilter("strftime", function ($timestamp, $format) {
            return strftime($format, $timestamp);
        }));

        $this->environment->addFilter(new Twig_SimpleFilter("phone_number", function ($string) {
            if (empty($string))
                return "";

            $parts = explode(" ", $string);
            $idx = count($parts) - 1;
            $parts[$idx] = sprintf("<span class='strong'>%s</span>", $parts[$idx]);
            return implode(" ", $parts);
        }));
    }
}
