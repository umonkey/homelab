<?php

/**
 * Renders images to share posts with.
 *
 * TODO: save as a file, upload to S3.
 **/

declare(strict_types=1);

namespace App\Services;

class NodePictureService
{
    /**
     * @var array
     */
    protected $settings;

    public function __construct(array $settings)
    {
        $this->settings = $settings;
    }

    public function render(string $text, string $template = null): string
    {
        if (null === $template) {
            $template = $this->getTemplate();
        }

        if (empty($template) or !file_exists($template)) {
            throw new \RuntimeException('image template not found: ' . $template);
        }

        $font = $this->getFont();

        $data = file_get_contents($template);
        $img = imagecreatefromstring($data);

        $this->drawText($img, $text, $font);

        ob_start();
        imagejpeg($img, null, 70);
        $data = ob_get_clean();

        return $data;
    }

    protected function getTemplate()
    {
        return $this->settings['template'] ?? null;
    }

    protected function getFont()
    {
        $path = $this->settings['font'] ?? null;

        if (empty($path)) {
            throw new \RuntimeException('kdpv font not set');
        }

        if (!is_readable($path)) {
            throw new \RuntimeException('kdpv font is not readable');
        }

        return $path;
    }

    protected function drawText($img, $text, $font)
    {
        $top = 220;
        $left = 80;
        $width = 960 - $left - 20;

        $size = 40;
        $lsize = 60;

        // Some typo.
        $text = str_replace(' в ', ' в ', $text);

        $words = preg_split('@\s+@', $text, -1, PREG_SPLIT_NO_EMPTY);

        while ($words) {
            for ($count = 1; $count <= count($words); $count++) {
                $line = implode(' ', array_slice($words, 0, $count));

                $box = imagettfbbox($size, 0, $font, $line);
                $bwidth = $box[4];
                $bheight = 0 - $box[5];

                if ($bwidth > $width) {
                    $count--;
                    break;
                }
            }

            $line = implode(' ', array_slice($words, 0, $count));

            imagettftext($img, $size, 0, $left, $top, 0xffffff, $font, $line);
            $top += $lsize;

            $words = array_slice($words, $count);
        }
    }
}
