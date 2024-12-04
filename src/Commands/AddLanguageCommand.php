<?php

namespace Mateffy\AiTranslations\Commands;

use Illuminate\Console\Command;

class AddLanguageCommand extends Command
{
    protected $signature = 'translate:add {language} {--base-language= : The language to translate from}';

    protected $description = 'Add a new language to the application';

    public function handle(): void
    {
        $from = $this->option('base-language') ?? config('app.locale');

        $this->call('translate', [
            '--base-language' => $from,
            '--language' => $this->argument('language'),
        ]);
    }
}
