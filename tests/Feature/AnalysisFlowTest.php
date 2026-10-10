<?php

namespace Tests\Feature;

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AnalysisFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['opticarte.mock' => true]);
        Storage::fake('local');
    }

    public function test_a_menu_photo_is_analysed_end_to_end_in_demo_mode(): void
    {
        $response = $this->post(route('analyses.store'), $this->validInput());
        $response->assertSessionHasNoErrors();

        $analysis = Analysis::sole();
        $response->assertRedirect(route('analyses.show', $analysis));

        $this->assertSame(AnalysisStatus::Done, $analysis->status);
        $this->assertSame('mock', $analysis->model);
        $this->assertSame(1, $analysis->image_count);
        $this->assertSame(17, $analysis->dishes()->count());
        $this->assertSame(14, $analysis->omnes['score']);
        Storage::disk('local')->assertExists("analyses/{$analysis->id}/1.jpg");

        $this->get(route('analyses.show', $analysis))
            ->assertOk()
            ->assertSee('Terminée')
            ->assertSee('Principes d’Omnes')
            ->assertSee('Risotto crémeux aux champignons des bois')
            ->assertSee(route('analyses.pdf', $analysis));
    }

    public function test_the_optimised_menu_is_exported_as_a_pdf(): void
    {
        $analysis = $this->analyse();

        $response = $this->get(route('analyses.pdf', $analysis));

        $response->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_pdf_is_unavailable_until_the_analysis_is_done(): void
    {
        $analysis = Analysis::create([
            'status' => AnalysisStatus::Pending,
            'restaurant_name' => 'Chez Lucien',
            'city' => 'Lyon',
            'restaurant_type' => 'Bistrot / brasserie',
        ]);

        $this->get(route('analyses.pdf', $analysis))->assertNotFound();
    }

    public function test_uploaded_photos_are_served_back(): void
    {
        $analysis = $this->analyse();

        $this->get(route('analyses.image', [$analysis, 1]))
            ->assertOk()
            ->assertHeader('content-type', 'image/jpeg');
        $this->get(route('analyses.image', [$analysis, 2]))->assertNotFound();
    }

    public function test_the_history_and_the_observatory_list_the_analysis(): void
    {
        $analysis = $this->analyse();

        $this->get(route('analyses.index'))
            ->assertOk()
            ->assertSee($analysis->restaurant_name)
            ->assertSee('14 / 100');

        $this->get(route('stats.index', ['city' => 'Lyon']))
            ->assertOk()
            ->assertSee('17 plats, 1 restaurants, 1 analyses terminées')
            ->assertSee('Bistrot / brasserie');

        $this->get(route('stats.index', ['city' => 'Paris']))
            ->assertOk()
            ->assertSee('Aucune donnée pour ces filtres.');
    }

    public function test_at_least_one_photo_is_required(): void
    {
        $this->from(route('home'))
            ->post(route('analyses.store'), ['restaurant_name' => 'Chez Lucien', 'city' => 'Lyon', 'restaurant_type' => 'Bistrot / brasserie'])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['images']);

        $this->assertDatabaseCount('analyses', 0);
    }

    public function test_only_images_are_accepted(): void
    {
        $input = $this->validInput();
        $input['images'] = [UploadedFile::fake()->create('carte.pdf', 100, 'application/pdf')];

        $this->from(route('home'))
            ->post(route('analyses.store'), $input)
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors(['images.0']);
    }

    private function analyse(): Analysis
    {
        $this->post(route('analyses.store'), $this->validInput());

        return Analysis::sole();
    }

    /** @return array<string, mixed> */
    private function validInput(): array
    {
        return [
            'restaurant_name' => 'Chez Lucien',
            'city' => 'Lyon',
            'restaurant_type' => 'Bistrot / brasserie',
            'goal' => 'Augmenter le ticket moyen',
            'images' => [UploadedFile::fake()->image('carte.jpg', 800, 1200)],
        ];
    }
}
