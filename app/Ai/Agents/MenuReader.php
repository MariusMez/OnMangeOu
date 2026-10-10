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

/** Lit une carte de restaurant à partir de photos et la transcrit en JSON structuré. */
#[Provider(Lab::Anthropic)]
#[MaxTokens(16000)]
#[Timeout(600)]
class MenuReader implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    public function instructions(): string
    {
        return <<<'PROMPT'
        Tu es un expert en lecture de cartes de restaurant.
        On te donne une ou plusieurs photos d'une même carte (pages, recto/verso, ardoise, captures d'écran d'un site web).
        Transcris fidèlement TOUS les plats, dans l'ordre d'apparition, sans rien inventer.

        Règles :
        - Conserve l'orthographe originale dans "name" et "description" (les fautes sont relevées à part dans "spelling_issues").
        - "price" est un nombre décimal (14,50 € → 14.5). Pour un prix double (ex: "12 / 18") prends le premier et garde le texte complet dans "price_text". Si illisible : null.
        - "category" : entree, plat, dessert, boisson, menu (formules / menus complets), autre (accompagnements, suppléments, enfants…).
        - "is_highlighted" : true seulement si la carte met visiblement le plat en avant (encadré, étoile, "suggestion", "plat du jour", "spécialité").
        - Si une rubrique n'a pas de titre, nomme-la d'après son contenu (ex: "Plats").
        - Déduis "cuisine_type" du contenu (bistrot, pizzeria, brasserie, gastronomique, asiatique…).
        - Sur une capture de site web, ignore les menus de navigation, bandeaux et pieds de page : seuls les plats comptent.
        - Signale dans "reading_notes" les zones illisibles ou les doutes, sinon null.
        PROMPT;
    }

    /** @return array<string, Type> */
    public function schema(JsonSchema $schema): array
    {
        return MenuSchemas::extractedMenu($schema);
    }

    /** @return array<string, mixed> */
    public function providerOptions(Lab|string $provider): array
    {
        return [
            'fallbacks' => 'default',
            'output_config' => ['effort' => 'medium'],
        ];
    }
}
