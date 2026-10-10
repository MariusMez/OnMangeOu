<?php

namespace App\Services\Browser;

use App\Contracts\WebsiteCapture;

/** Mode démo : image de substitution, aucune navigation réelle. */
class FakeWebsiteCapture implements WebsiteCapture
{
    public function capture(string $url): array
    {
        $image = imagecreatetruecolor(1280, 900);
        imagefill($image, 0, 0, 0xFAF7F2);
        $ink = imagecolorallocate($image, 31, 26, 23);
        imagestring($image, 5, 40, 40, 'Capture simulee (mode demo)', $ink);
        imagestring($image, 3, 40, 70, substr($url, 0, 150), $ink);

        ob_start();
        imagejpeg($image, null, 85);

        return [(string) ob_get_clean()];
    }
}
