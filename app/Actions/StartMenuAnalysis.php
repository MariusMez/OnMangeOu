<?php

namespace App\Actions;

use App\Enums\AnalysisStatus;
use App\Jobs\RunMenuAnalysis;
use App\Models\Analysis;
use App\Services\ImageNormalizer;
use App\Services\ImageStore;
use Illuminate\Http\UploadedFile;

class StartMenuAnalysis
{
    public function __construct(
        private readonly ImageNormalizer $normalizer,
        private readonly ImageStore $images,
    ) {}

    /**
     * @param  array{restaurant_name: string, city: string, restaurant_type: string, goal?: string|null, website_url?: string|null}  $data
     * @param  list<UploadedFile>  $files
     */
    public function handle(array $data, array $files): Analysis
    {
        $images = array_map(
            fn (UploadedFile $file) => $this->normalizer->normalize($file->getRealPath()),
            array_values($files),
        );

        $mock = (bool) config('opticarte.mock');

        $analysis = Analysis::create([
            'status' => AnalysisStatus::Pending,
            'restaurant_name' => $data['restaurant_name'],
            'city' => $data['city'],
            'restaurant_type' => $data['restaurant_type'],
            'goal' => $data['goal'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'image_count' => count($images),
            'model' => $mock ? 'mock' : config('opticarte.model'),
            'mock' => $mock,
        ]);

        $this->images->store($analysis, $images);

        RunMenuAnalysis::dispatch($analysis);

        return $analysis;
    }
}
