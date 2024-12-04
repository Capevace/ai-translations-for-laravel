<?php

namespace Mateffy\AiTranslations;
use Spatie\LaravelPackageTools\Package;

class AiTranslationServiceProvider extends \Spatie\LaravelPackageTools\PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package
            ->name('ai-translations')
            ->hasConfigFile();
    }
}
