<?php

namespace App\Services\Ai;

use App\Ai\Agents\MenuOptimizer;
use App\Ai\Agents\MenuReader;
use App\Contracts\MenuAnalyst;
use App\Exceptions\MenuAnalysisException;
use App\Models\Analysis;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Files\Image;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;

/** Lecture et optimisation de cartes via les agents Laravel AI (fournisseur Anthropic). */
class AiMenuAnalyst implements MenuAnalyst
{
    /** Nombre de caractères de fin de réponse journalisés quand elle est inexploitable. */
    private const int LOGGED_TEXT_TAIL_LENGTH = 500;

    public function __construct(
        private readonly MenuReader $reader,
        private readonly MenuOptimizer $optimizer,
    ) {}

    public function extract(array $images): array
    {
        if ($images === []) {
            throw new MenuAnalysisException('Aucune photo exploitable pour cette analyse.');
        }

        $attachments = array_map(
            fn (string $binary) => Image::fromBase64(base64_encode($binary), 'image/jpeg'),
            $images,
        );

        $response = $this->reader->prompt(
            sprintf('Voici %d photo(s) de la carte. Transcris-la intégralement au format demandé.', count($images)),
            attachments: $attachments,
        );

        $menu = $this->structured($response, 'la lecture de la carte');

        if (! is_array($menu['sections'] ?? null)) {
            throw new MenuAnalysisException('Réponse du modèle inexploitable lors de la lecture de la carte.');
        }

        return $menu;
    }

    public function optimize(array $menu, array $omnes, Analysis $analysis): array
    {
        $context = array_filter([
            'Restaurant : '.$analysis->restaurant_name,
            'Ville : '.$analysis->city,
            'Type déclaré : '.$analysis->restaurant_type,
            $analysis->goal ? 'Objectif du restaurateur : '.$analysis->goal : null,
        ]);

        $prompt = implode("\n\n", [
            "## Contexte\n".implode("\n", $context),
            "## Carte extraite (JSON)\n".self::json($menu),
            "## Analyse Omnes (JSON)\n".self::json($omnes),
            'Rédige le diagnostic et la carte optimisée au format demandé.',
        ]);

        $optimization = $this->structured($this->optimizer->prompt($prompt), "l'optimisation");

        if (! is_array($optimization['optimized_menu'] ?? null)) {
            throw new MenuAnalysisException("Réponse du modèle inexploitable lors de l'optimisation.");
        }

        return $optimization;
    }

    /** @return array<string, mixed> */
    private function structured(AgentResponse $response, string $step): array
    {
        if ($response instanceof StructuredAgentResponse && $response->toArray() !== []) {
            return $response->toArray();
        }

        $decoded = json_decode($response->text, true);

        if (is_array($decoded) && $decoded !== []) {
            return $decoded;
        }

        Log::warning('Unusable model response', [
            'step' => $step,
            'model' => $response->meta->model,
            'finish_reason' => $response->steps->last()?->finishReason->value,
            'input_tokens' => $response->usage->inputTokens,
            'output_tokens' => $response->usage->outputTokens,
            'text_length' => mb_strlen($response->text),
            'text_tail' => mb_substr($response->text, -self::LOGGED_TEXT_TAIL_LENGTH),
        ]);

        throw new MenuAnalysisException(sprintf('Réponse du modèle inexploitable pendant %s.', $step));
    }

    /** @param  array<string, mixed>  $data */
    private static function json(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR);
    }
}
