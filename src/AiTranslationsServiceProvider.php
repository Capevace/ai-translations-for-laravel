<?php

namespace Mateffy\AiTranslations;

use Mateffy\AiTranslations\Commands\AddLanguageCommand;
use Mateffy\AiTranslations\Commands\CheckCommand;
use Mateffy\AiTranslations\Commands\TranslateCommand;
use Mateffy\AiTranslations\Commands\ValidateCommand;
use Spatie\LaravelPackageTools\Package;

class AiTranslationsServiceProvider extends \Spatie\LaravelPackageTools\PackageServiceProvider
{

    public function configurePackage(Package $package): void
    {
        $package
            ->name('ai-translations')
            ->hasConfigFile()
            ->hasCommands([
                AddLanguageCommand::class,
                CheckCommand::class,
                TranslateCommand::class,
                ValidateCommand::class
            ]);
    }
}
