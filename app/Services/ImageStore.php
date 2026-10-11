<?php

namespace App\Services;

use App\Models\Analysis;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

/** Photos normalisées d'une analyse, rangées dans analyses/{id}/{n}.jpg. */
class ImageStore
{
    /** @param  list<string>  $images */
    public function store(Analysis $analysis, array $images): void
    {
        foreach ($images as $index => $binary) {
            $this->disk()->put($this->path($analysis, $index + 1), $binary);
        }
    }

    /** @return list<string> */
    public function all(Analysis $analysis): array
    {
        $images = [];
        for ($index = 1; $index <= $analysis->image_count; $index++) {
            $path = $this->path($analysis, $index);
            if ($this->disk()->exists($path)) {
                $images[] = $this->disk()->get($path);
            }
        }

        return $images;
    }

    public function get(Analysis $analysis, int $index): ?string
    {
        $path = $this->path($analysis, $index);

        return $this->disk()->exists($path) ? $this->disk()->get($path) : null;
    }

    private function path(Analysis $analysis, int $index): string
    {
        return sprintf('analyses/%s/%d.jpg', $analysis->id, $index);
    }

    private function disk(): Filesystem
    {
        return Storage::disk(config('opticarte.disk'));
    }
}
