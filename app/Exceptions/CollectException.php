<?php

namespace App\Exceptions;

use RuntimeException;

/** Erreur de la collecte de restaurants (zone invalide, service indisponible…), avec un message lisible. */
class CollectException extends RuntimeException {}
