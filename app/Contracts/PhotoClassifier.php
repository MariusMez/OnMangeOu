<?php

namespace App\Contracts;

/** Tri des photos d'un restaurant : lesquelles montrent une carte dont les prix sont lisibles ? */
interface PhotoClassifier
{
    /**
     * @param  list<string>  $images  Photos JPEG (contenu binaire), dans l'ordre
     * @return list<array{kind: string, has_prices: bool}> Un verdict par photo, dans le même ordre (kind : valeur de PhotoKind)
     */
    public function classify(array $images): array;
}
