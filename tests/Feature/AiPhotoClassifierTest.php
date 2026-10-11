<?php

namespace Tests\Feature;

use App\Ai\Agents\MenuPhotoClassifier;
use App\Services\Ai\AiPhotoClassifier;
use Tests\TestCase;

class AiPhotoClassifierTest extends TestCase
{
    public function test_each_photo_gets_a_verdict_in_the_order_it_was_sent(): void
    {
        MenuPhotoClassifier::fake([['photos' => [
            ['index' => 2, 'kind' => 'menu', 'has_prices' => true],
            ['index' => 1, 'kind' => 'selfie', 'has_prices' => false],
            ['index' => 9, 'kind' => 'menu', 'has_prices' => true],
        ]]]);

        $verdicts = app(AiPhotoClassifier::class)->classify(['photo-1', 'photo-2', 'photo-3']);

        $this->assertSame([
            ['kind' => 'other', 'has_prices' => false],
            ['kind' => 'menu', 'has_prices' => true],
            ['kind' => 'other', 'has_prices' => false],
        ], $verdicts);
        MenuPhotoClassifier::assertPrompted(fn ($prompt) => count($prompt->attachments) === 3 && $prompt->contains('3 photo(s)'));
    }

    public function test_no_photo_means_no_call_to_the_model(): void
    {
        MenuPhotoClassifier::fake([]);

        $this->assertSame([], app(AiPhotoClassifier::class)->classify([]));
        MenuPhotoClassifier::assertNeverPrompted();
    }
}
