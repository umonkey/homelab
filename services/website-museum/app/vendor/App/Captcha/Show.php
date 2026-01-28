<?php
/**
 * Вывод капчи в браузер.
 **/

class App_Captcha_Show extends App_Core_View
{
    const IMG_BLANK = __DIR__ . "/blank.png";

    const FONT = __DIR__ . "/maturasc.ttf";

    const SIZE = 26;

    public function onGet()
    {
        $code = rand(1111, 9999);

        $s = new Framework_Session;
        $s["captcha"] = $code;
        $s->save();

        $image = $this->getImage($code);

        return new Framework_Response($image, "200 OK", array(
            "Content-Type" => "image/png",
            ));
    }

    protected function getImage($code)
    {
        if (!function_exists("imagettftext"))
            return $this->unavailable("no ttf support in gd");

        $color_r = rand(50, 170);
        $color_g = rand(50, 170);
        $color_b = rand(170, 250);

        $size = getimagesize(self::IMG_BLANK);
        $width = $size[0];
        $height = $size[1];

        $rotation = rand(-5,10);
        $pad_x = 10;
        $pad_y = 35;

        $img = ImageCreateFromString(file_get_contents(self::IMG_BLANK));

        $fg = ImageColorAllocate($img, $color_r, $color_g, $color_b);

        $res = ImageTTFText($img, self::SIZE, $rotation, $pad_x, $pad_y, $fg, self::FONT, (string)$code);
        if ($res === false)
            throw new RuntimeException("error rendering text");

        $dots = $width * $height / 2;
        for ($i = 0; $i < $dots; $i++) {
            $dc = ImageColorAllocate($img, $color_r, $color_g, $color_b);
            ImageSetPixel($img, rand(0,$width), rand(0,$height), $dc);
        }

        ob_start();
        imagepng($img);
        return ob_get_clean();
    }
}
