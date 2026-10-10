<?php

namespace App\Services\Claude;

use Anthropic\Core\Exceptions\APIConnectionException;
use Anthropic\Core\Exceptions\APIStatusException;
use Anthropic\Core\Exceptions\AuthenticationException;
use Anthropic\Core\Exceptions\BadRequestException;
use Anthropic\Core\Exceptions\RateLimitException;
use App\Exceptions\MenuAnalysisException;
use Throwable;

final class ClaudeErrorMessage
{
    /** Message lisible pour le restaurateur à partir d'une exception du pipeline. */
    public static function describe(?Throwable $exception): string
    {
        return match (true) {
            $exception === null => 'Erreur inconnue.',
            $exception instanceof MenuAnalysisException => $exception->getMessage(),
            $exception instanceof AuthenticationException => 'Clé API Anthropic absente ou invalide. Renseignez ANTHROPIC_API_KEY (ou OPTICARTE_MOCK=true pour le mode démo).',
            $exception instanceof RateLimitException => "Limite de débit de l'API atteinte : réessayez dans quelques instants.",
            $exception instanceof BadRequestException => "Requête refusée par l'API : ".$exception->getMessage(),
            $exception instanceof APIConnectionException => "Impossible de joindre l'API Anthropic (réseau).",
            $exception instanceof APIStatusException => 'Erreur API : '.$exception->getMessage(),
            default => $exception->getMessage() !== '' ? $exception->getMessage() : 'Erreur inconnue.',
        };
    }
}
