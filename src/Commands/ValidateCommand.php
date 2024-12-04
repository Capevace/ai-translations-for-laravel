<?php

namespace Mateffy\AiTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Mateffy\AiTranslations\TranslationChat;
use Mateffy\AiTranslations\TranslationFile;
use Mateffy\AiTranslations\Translator;
use Mateffy\Magic;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\text;

class ValidateCommand extends Command
{
    protected $signature = 'translate:validate {--name=} {--language=} {--base-language=}';

    protected $description = 'Check for any missing translations';

    public function __construct(protected Translator $translator)
    {
        parent::__construct();
    }

    public function handle(): void
	{
		$from = $this->option('base-language') ?? config('app.locale');

        if ($language = $this->option('language')) {
            $languages = [$language];
        } else {
            $languages = $this->translator->getLanguages();
        }

        if ($name = $this->option('name')) {
            $domains = [$name];
        } else {
            $domains = collect(File::files(lang_path($from)))
                ->filter(fn ($file) => $file->getExtension() === 'php')
                ->map(fn ($file) => str($file)
                    ->basename('.php')
                )
                ->filter()
                ->values();
        }

        $this->line("Checking translations...");
        $this->comment("Source: {$from}");
        $this->comment("Languages: " . $languages->join(', '));
        $this->comment("Domains: " . $domains->join(', '));
        $this->newLine();

        foreach ($domains as $domain) {
            $this->line("Checking {$domain}...");
            $this->newLine();

            $source = TranslationFile::load($from, $domain);

            foreach ($languages as $language) {
                $target = TranslationFile::load($language, $domain);

                $this->line("Checking {$domain} in {$language}...");
                $this->newLine();

                $missingKeys = $source->compare($target);

                if (count($missingKeys) > 0) {
                    $this->alert("Missing translations for `{$domain}` in `{$language}`:");
                    $this->table(['Key', 'Value'], collect($missingKeys)
                        ->map(fn ($key) => [$key, $source->get($key)])
                    );
                    $this->newLine();
                } else {
                    $this->info("No missing translations for {$domain} in {$language}");
                }

                $this->newLine();
            }
        }
	}
}

