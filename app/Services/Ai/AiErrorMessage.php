<?php

namespace App\Services\Ai;

use App\Exceptions\MenuAnalysisException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Laravel\Ai\Exceptions\InsufficientCreditsException;
use Laravel\Ai\Exceptions\ProviderConnectionException;
use Laravel\Ai\Exceptions\ProviderOverloadedException;
use Laravel\Ai\Exceptions\RateLimitedException;
use Throwable;

final class AiErrorMessage
{
    /** Message lisible pour le restaurateur à partir d'une exception du pipeline. */
    public static function describe(?Throwable $exception): string
    {
        return match (true) {
            $exception === null => 'Erreur inconnue.',
            $exception instanceof MenuAnalysisException => $exception->getMessage(),
            $exception instanceof RateLimitedException => "Limite de débit de l'API atteinte : réessayez dans quelques instants.",
            $exception instanceof ProviderOverloadedException => "Le fournisseur d'IA est surchargé : réessayez dans quelques instants.",
            $exception instanceof InsufficientCreditsException => 'Crédits API insuffisants sur le compte Anthropic.',
            $exception instanceof ProviderConnectionException, $exception instanceof ConnectionException => "Impossible de joindre l'API Anthropic (réseau).",
            $exception instanceof RequestException => self::describeHttpFailure($exception),
            default => $exception->getMessage() !== '' ? $exception->getMessage() : 'Erreur inconnue.',
        };
    }

    private static function describeHttpFailure(RequestException $exception): string
    {
        $status = $exception->response->status();
        $detail = (string) ($exception->response->json('error.message') ?? '');

        return match (true) {
            $status === 401 => 'Clé API Anthropic absente ou invalide. Renseignez ANTHROPIC_API_KEY (ou OPTICARTE_MOCK=true pour le mode démo).',
            $status === 400 => "Requête refusée par l'API : ".($detail ?: $exception->getMessage()),
            default => sprintf('Erreur API (%d) : %s', $status, $detail ?: $exception->getMessage()),
        };
    }
}
