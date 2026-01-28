<?php

class App_Core_Image
{
    protected $img;

    public function __construct($img)
    {
        $this->img = $img;
    }

    protected function replace($img)
    {
        if (!is_resource($img))
            throw new RuntimeException("image must be a resource");

        if ($this->img)
            imagedestroy($this->img);

        $this->img = $img;
        return $this;
    }

    public static function fromFile($path)
    {
        $data = file_get_contents($path);
        return self::fromString($data);
    }

    public static function fromString($str)
    {
        $img = @imageCreateFromString($str);
        if (false === $img)
            throw new RuntimeException("error parsing image");
        return new self($img);
    }

    public static function fromResource($img)
    {
        if (!is_resource($img))
            throw new RuntimeException("image must be a resource");
        $i = new self($img);
        return $i;
    }

    public function __destruct()
    {
        imageDestroy($this->img);
    }

    /**
     * Create a new image of given size and cover it with the current one.
     *
     * @param integer $width Desired image width.
     * @param integer $height Desired image height.
     * @return Files_Image Resulting image.
     **/
    public function cover($width, $height)
    {
        // Start with whole image.
        $sx = 0;
        $sy = 0;
        $sw = imageSX($this->img);
        $sh = imageSY($this->img);

        $sr = $sw / $sh;
        $dr = $width / $height;

        // Source image is taller, cut from top and bottom.
        if ($sr < $dr) {
            $sh = round($sw * $dr);
            $sy = round((imageSY($this->img) - $sh) / 2);
        }

        // Source is wider, cut from left and right.
        else {
            $sw = round($sh * $dr);
            $sx = round((imageSX($this->img) - $sw) / 2);
        }

        log_debug("image: resizing %ux%u to %ux%u",
            imageSX($this->img), imageSY($this->img), $width, $height);

        $dst = imageCreateTrueColor($width, $height);

        $res = imageCopyResampled($dst, $this->img, 0, 0, $sx, $sy, $width, $height, $sw, $sh);
        if ($res === false)
            throw new RuntimeException("could not scale image");

        imageDestroy($this->img);
        $this->img = $dst;
    }

    public function crop($x, $y, $w, $h)
    {
        $new = imagecreatetruecolor($w, $h);

        $res = imagecopyresampled($new, $this->img,
            0, 0, $x, $y,
            $w, $h, $w, $h);
        if (false === $res)
            throw new RuntimeException("error cropping image");

        return self::fromResource($new);
    }

    public function resizeMin($size, $replace = true)
    {
        $sx = $sy = 0;
        $sw = imageSX($this->img);
        $sh = imageSY($this->img);

        // never enlarge images
        $size = min($size, min($sw, $sh));

        $k = $sw / $sh;
        if ($sw > $sh) {
            $dh = $size;
            $dw = round($dh * $k);
        } else {
            $dw = $size;
            $dh = round($dw / $k);
        }

        log_debug("image: resizing %ux%u to %ux%u", $sw, $sh, $dw, $dh);

        $dst = imageCreateTrueColor($dw, $dh);

        $res = imageCopyResampled($dst, $this->img, 0, 0, $sx, $sy, $dw, $dh, $sw, $sh);
        if ($res === false)
            throw new RuntimeException("could not scale image");

        return $replace ? $this->replace($dst) : self::fromResource($dst);
    }

    public function resizeMax($size, $replace = true)
    {
        $sx = $sy = 0;
        $sw = imageSX($this->img);
        $sh = imageSY($this->img);

        // never enlarge images
        $size = min($size, max($sw, $sh));

        $k = $sw / $sh;
        if ($sw < $sh) {
            $dh = $size;
            $dw = round($dh * $k);
        } else {
            $dw = $size;
            $dh = round($dw / $k);
        }

        log_debug("image: resizing %ux%u to %ux%u", $sw, $sh, $dw, $dh);

        $dst = imageCreateTrueColor($dw, $dh);

        $res = imageCopyResampled($dst, $this->img, 0, 0, $sx, $sy, $dw, $dh, $sw, $sh);
        if ($res === false)
            throw new RuntimeException("could not scale image");

        return $replace ? $this->replace($dst) : self::fromResource($dst);
    }

    public function resizeSquare($width)
    {
        $sx = $sy = 0;
        $sw = $_sw = imageSX($this->img);
        $sh = $_sh = imageSY($this->img);

        // never enlarge images
        $width = min($width, min($sw, $sh));

        if ($sw > $sh) {
            $sx = round(($sw - $sh) / 2);
            $sw = $sh;
        } elseif ($sh > $sw) {
            $sy = round(($sh - $sw) / 2);
            $sh = $sw;
        }

        $dx = $dy = 0;
        $dw = $dh = $width;

        log_debug("image: resizing %ux%u to %ux%u", $_sw, $_sh, $dw, $dh);

        $dst = imageCreateTrueColor($dw, $dh);

        $res = imageCopyResampled($dst, $this->img, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh);
        if ($res === false)
            throw new RuntimeException("could not scale image");

        imageDestroy($this->img);
        $this->img = $dst;
    }

    public function sharpen()
    {
        $sharpen = array(
            array(-1.2, -1, -1.2),
            array(-1, 20, -1),
            array(-1.2, -1, -1.2),
            );

        log_debug("sharpening image.");

        $divisor = array_sum(array_map('array_sum', $sharpen));

        imageConvolution($this->img, $sharpen, $divisor, 0);
    }

    public function getJPEG($quality = 95)
    {
        ob_start();
        $data = @imageJPEG($this->img, null, $quality);
        if (false === $data)
            throw new RuntimeException("error saving image as JPEG");
        return ob_get_clean();
    }
}
