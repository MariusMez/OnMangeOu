<?php

namespace App\Ai\Agents;

use App\Ai\Schemas\MenuSchemas;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\JsonSchema\Types\Type;
use Laravel\Ai\Attributes\MaxTokens;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Attributes\Timeout;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

/** Trie les photos publiées sur la fiche d'un restaurant : carte avec prix lisibles, plat, salle, façade… */
#[Provider(Lab::Anthropic)]
#[MaxTokens(4000)]
#[Timeout(180)]
class MenuPhotoClassifier implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        Tu tries les photos publiées sur la fiche d'un restaurant (clients et propriétaire).
        Pour chaque photo, dans l'ordre d'envoi, donne :
        - "index" : son numéro, à partir de 1 ;
        - "kind" : menu (carte, ardoise, menu du jour, tableau des prix), food (plat ou boisson), interior (salle, cuisine), exterior (façade, terrasse, enseigne) ou other ;
        - "has_prices" : true seulement si des noms de plats ET leurs prix sont lisibles sur la photo.
        Une carte floue, coupée au point de ne lire aucun prix, ou vue de trop loin a has_prices à false.
        PROMPT;
    }

    /** Modèle dédié au tri, réglable séparément de la lecture des cartes (OPTICARTE_CLASSIFIER_MODEL). */
    public function model(): string
    {
        return (string) config('opticarte.collect.classifier_model');
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return MenuSchemas::photoClassification($schema);
    }

    /** @return array<string, mixed> */
    public function providerOptions(Lab|string $provider): array
    {
        $options = ['output_config' => ['effort' => 'low']];

        return str_contains($this->model(), 'haiku') ? $options : [...$options, 'fallbacks' => 'default'];
    }
}
