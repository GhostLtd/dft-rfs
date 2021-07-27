<?php

namespace App\Twig;

use Exception;
use Symfony\Component\Asset\Packages;
use Symfony\WebpackEncoreBundle\Asset\EntrypointLookupCollectionInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EntryFilesTwigExtension extends AbstractExtension
{
    protected EntrypointLookupCollectionInterface $entrypointLookupCollection;
    protected Packages $packages;
    protected string $webRootDir;

    public function __construct(EntrypointLookupCollectionInterface $entrypointLookupCollection, Packages $packages, string $webRootDir)
    {
        $this->entrypointLookupCollection = $entrypointLookupCollection;
        $this->packages = $packages;
        $this->webRootDir = $webRootDir;
    }

    public function getFunctions()
    {
        return [
            // Names in keeping with Symfony\WebpackEncoreBundle\Twig\EntryFilesTwigExtension
            new TwigFunction('encore_entry_inline_javascript', [$this, 'inlineJavascript'], ['is_safe' => ['html']]),
            new TwigFunction('encore_entry_inline_styles', [$this, 'inlineStyles'], ['is_safe' => ['html']]),
        ];
    }

    public function inlineJavascript(string $entryName, string $packageName = null, string $entrypointName = '_default'): string
    {
        $paths = $this->entrypointLookupCollection->getEntrypointLookup($entrypointName)->getJavaScriptFiles($entryName);
        return '<script>'.$this->getFilesContent($paths, $packageName).'</script>';
    }

    public function inlineStyles(string $entryName, string $packageName = null, string $entrypointName = '_default'): string
    {
        $paths = $this->entrypointLookupCollection->getEntrypointLookup($entrypointName)->getCssFiles($entryName);
        return '<style type="text/css">'.$this->getFilesContent($paths, $packageName).'</style>';
    }

    private function getFilesContent(array $paths, string $packageName = null) : string {
        $content = '';

        $fileSystemPaths = $this->getFilesystemPaths($this->getAssetsPaths($paths, $packageName));

        foreach($fileSystemPaths as $path) {
            $content .= file_get_contents($path)."\n";
        }

        return $content;
    }

    private function getFilesystemPaths(array $assetPaths) : array
    {
        $webRootDirWithoutTrailingSlash = DIRECTORY_SEPARATOR . trim($this->webRootDir, DIRECTORY_SEPARATOR);

        return array_map(function($assetPath) use ($webRootDirWithoutTrailingSlash) {
            return $webRootDirWithoutTrailingSlash . $assetPath;
        }, $assetPaths);
    }

    private function getAssetsPaths(array $paths, string $packageName = null) : array
    {
        return array_map(function($path) use ($packageName) {
            return $this->getAssetPath($path, $packageName);
        }, $paths);
    }

    private function getAssetPath(string $assetPath, string $packageName = null): string
    {
        if (null === $this->packages) {
            throw new Exception('To render the script or link tags, run "composer require symfony/asset".');
        }

        return $this->packages->getUrl(
            $assetPath,
            $packageName
        );
    }
}