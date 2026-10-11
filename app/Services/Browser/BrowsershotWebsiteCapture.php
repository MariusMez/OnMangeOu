<?php

namespace App\Services\Browser;

use App\Contracts\WebsiteCapture;
use App\Exceptions\MenuAnalysisException;
use Illuminate\Support\Str;
use Intervention\Image\Encoders\JpegEncoder;
use Intervention\Image\ImageManager;
use Throwable;

/**
 * Navigue sur le site du restaurateur avec Chromium (Browsershot) : capture
 * pleine page de l'URL donnée, puis de la première page « carte / menu » liée,
 * découpées en tuiles lisibles par le modèle de vision.
 */
class BrowsershotWebsiteCapture implements WebsiteCapture
{
    public const int WIDTH = 1280;

    public const int TILE_HEIGHT = 1800;

    private const int TIMEOUT_SECONDS = 60;

    private const string USER_AGENT = 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/130.0 Safari/537.36 OptiCarte/1.0';

    public function __construct(
        private readonly BrowsershotFactory $factory,
        private readonly ImageManager $images,
    ) {}

    public function capture(string $url): array
    {
        $maxTiles = (int) config('opticarte.max_images');
        $tiles = $this->tiles($this->screenshot($url), $maxTiles);

        $menuUrl = $this->findMenuLink($url);
        if ($menuUrl !== null && count($tiles) < $maxTiles) {
            try {
                $tiles = [...$tiles, ...$this->tiles($this->screenshot($menuUrl), $maxTiles - count($tiles))];
            } catch (Throwable) {
                // La page d'accueil suffit si la page « carte » ne se charge pas.
            }
        }

        if ($tiles === []) {
            throw new MenuAnalysisException('La page web n’a pas pu être capturée.');
        }

        return $tiles;
    }

    private function screenshot(string $url): string
    {
        try {
            return $this->factory->url($url)
                ->userAgent(self::USER_AGENT)
                ->windowSize(self::WIDTH, self::TILE_HEIGHT)
                ->fullPage()
                ->dismissDialogs()
                ->ignoreHttpsErrors()
                ->waitUntilNetworkIdle(strict: false)
                ->timeout(self::TIMEOUT_SECONDS)
                ->setScreenshotType('png')
                ->screenshot();
        } catch (Throwable $exception) {
            throw new MenuAnalysisException(
                sprintf('Impossible de capturer %s : %s', $url, Str::limit($exception->getMessage(), 200)),
                previous: $exception,
            );
        }
    }

    /**
     * Découpe une capture pleine page en tuiles de hauteur fixe.
     *
     * @return list<string>
     */
    private function tiles(string $png, int $max): array
    {
        if ($max <= 0) {
            return [];
        }

        $image = $this->images->decodeBinary($png);
        $height = $image->height();
        $tiles = [];

        for ($offset = 0; $offset < $height && count($tiles) < $max; $offset += self::TILE_HEIGHT) {
            $tileHeight = min(self::TILE_HEIGHT, $height - $offset);
            if ($tileHeight < 200) {
                break;
            }

            $tiles[] = (clone $image)
                ->crop($image->width(), $tileHeight, 0, $offset)
                ->encode(new JpegEncoder(quality: 85))
                ->toString();
        }

        return $tiles;
    }

    /** Première page liée dont le texte ou l'adresse évoque la carte ou le menu, sur le même site. */
    private function findMenuLink(string $url): ?string
    {
        try {
            $json = $this->factory->url($url)
                ->userAgent(self::USER_AGENT)
                ->timeout(self::TIMEOUT_SECONDS)
                ->ignoreHttpsErrors()
                ->evaluate(<<<'JS'
                JSON.stringify(Array.from(document.querySelectorAll('a[href]')).map(a => ({
                    href: a.href,
                    text: (a.textContent || '').trim().slice(0, 80),
                })))
                JS);
        } catch (Throwable) {
            return null;
        }

        $links = json_decode($json, true);
        if (! is_array($links)) {
            return null;
        }

        $host = parse_url($url, PHP_URL_HOST);

        foreach ($links as $link) {
            $href = (string) ($link['href'] ?? '');
            $text = (string) ($link['text'] ?? '');

            if (! Str::startsWith($href, ['http://', 'https://']) || parse_url($href, PHP_URL_HOST) !== $host) {
                continue;
            }
            if (rtrim($href, '/') === rtrim($url, '/') || Str::endsWith(strtolower($href), '.pdf')) {
                continue;
            }
            if (preg_match('/\b(carte|menu|menus|la-carte|nos-plats)\b/iu', $text.' '.$href)) {
                return $href;
            }
        }

        return null;
    }
}
