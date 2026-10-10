<?php

namespace Tests\Unit;

use App\Ai\Schemas\MenuSchemas;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;
use Laravel\Ai\ObjectSchema;
use PHPUnit\Framework\TestCase;

class MenuSchemasTest extends TestCase
{
    public function test_the_extracted_menu_schema_requires_every_property_and_marks_optional_ones_nullable(): void
    {
        $schema = (new ObjectSchema(MenuSchemas::extractedMenu(new JsonSchemaTypeFactory)))->toSchema();

        $this->assertSame('object', $schema['type']);
        $this->assertFalse($schema['additionalProperties']);
        $this->assertEqualsCanonicalizing(
            ['restaurant_name', 'cuisine_type', 'currency', 'language', 'sections', 'spelling_issues', 'reading_notes'],
            $schema['required'],
        );

        $item = $schema['properties']['sections']['items']['properties']['items']['items'];
        $this->assertEqualsCanonicalizing(['name', 'description', 'price', 'price_text', 'category', 'tags', 'is_highlighted'], $item['required']);
        $this->assertContains('null', (array) $item['properties']['price']['type']);
        $this->assertSame(['entree', 'plat', 'dessert', 'boisson', 'menu', 'autre'], $item['properties']['category']['enum']);
    }

    public function test_the_optimisation_schema_describes_the_menu_engineering_matrix(): void
    {
        $schema = (new ObjectSchema(MenuSchemas::optimization(new JsonSchemaTypeFactory)))->toSchema();

        $entry = $schema['properties']['menu_engineering']['items'];
        $this->assertSame(['etoile', 'vache_a_lait', 'enigme', 'poids_mort'], $entry['properties']['classification']['enum']);
        $this->assertSame(['haute', 'moyenne', 'basse'], $schema['properties']['layout_recommendations']['items']['properties']['priority']['enum']);
        $this->assertArrayHasKey('optimized_menu', $schema['properties']);
    }
}
