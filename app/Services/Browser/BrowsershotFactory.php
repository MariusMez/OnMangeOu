<?php

namespace App\Services\Browser;

use Spatie\Browsershot\Browsershot;

/**
 * Applique à Browsershot la configuration partagée avec laravel-pdf
 * (config/laravel-pdf.php, section browsershot) : chemin de Chrome, Node,
 * node_modules, sandbox.
 */
class BrowsershotFactory
{
    public function url(string $url): Browsershot
    {
        return $this->configure(Browsershot::url($url));
    }

    public function configure(Browsershot $browsershot): Browsershot
    {
        $config = (array) config('laravel-pdf.browsershot', []);

        if ($chromePath = ($config['chrome_path'] ?? null)) {
            $browsershot->setChromePath($chromePath);
        }

        if ($nodeBinary = ($config['node_binary'] ?? null)) {
            $browsershot->setNodeBinary($nodeBinary);
        }

        if ($npmBinary = ($config['npm_binary'] ?? null)) {
            $browsershot->setNpmBinary($npmBinary);
        }

        if ($includePath = ($config['include_path'] ?? null)) {
            $browsershot->setIncludePath($includePath);
        }

        $browsershot->setNodeModulePath($config['node_modules_path'] ?: base_path('node_modules'));

        if ($binPath = ($config['bin_path'] ?? null)) {
            $browsershot->setBinPath($binPath);
        }

        if ($config['no_sandbox'] ?? false) {
            $browsershot->noSandbox();
        }

        return $browsershot;
    }
}
