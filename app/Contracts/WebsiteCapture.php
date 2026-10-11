<?php

namespace App\Contracts;

/** Capture d'écran d'une page web (site du restaurateur) découpée en images analysables. */
interface WebsiteCapture
{
    /**
     * @return list<string> Images JPEG (contenu binaire), de haut en bas de la page
     */
    public function capture(string $url): array;
}
