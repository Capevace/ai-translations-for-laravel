<?php

namespace Mateffy\AiTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Mateffy\AiTranslations\TranslationFile;
use Mateffy\AiTranslations\Translator;
use function Laravel\Prompts\confirm;

class TranslateCommand extends Command
{
    protected $signature = 'translate {--dry-run} {--name= : The file to translate} {--language= : The language to translate to} {--base-language= : The language to translate from} {--after=}';

    protected $description = 'Command description';

    public function __construct(protected Translator $translator)
    {
        parent::__construct();
    }

    public function handle(): void
	{
		$from = $this->option('base-language') ?? config('app.locale');

		if ($language = $this->option('language')) {
			$languages = collect([$language]);
		} else {
			$languages = $this->translator->getLanguages()
                ->filter(fn ($file) => $file !== $from)
                ->values();
		}

		if ($name = $this->option('name')) {
			$names = [$name];
		} else {
			$filesInLangDir = File::files(lang_path($from));

			$names = collect($filesInLangDir)
				->map(fn ($file) => pathinfo($file, PATHINFO_FILENAME))
				->unique()
                ->values()
				->toArray();
		}

        $model = $this->translator->getModel();

        $this->line("AI Translator");
        $this->comment("Model: {$model->getOrganization()->id}/{$model->getModelName()}");
        $this->comment("Source: {$from}");
        $this->comment("Languages: " . $languages->join(', '));
        $this->comment("Domains: " . implode(', ', $names));
        $this->newLine();

        foreach ($names as $domain) {
            $fromFile = TranslationFile::load($from, $domain);

		    foreach ($languages as $language) {
                if ($language === $from) {
                    continue;
                }

                $toFile = TranslationFile::load($language, $domain);

				$this->info("Translating from {$from} to {$language}: {$domain}");

                $missingKeys = $fromFile->compare($toFile);

                if (count($missingKeys) === 0) {
                    if (!confirm("No missing keys found. Translate the full file again?", default: false)) {
                        continue;
                    }
                }

				$translations = $this->translator->translate(from: $fromFile, to: $toFile, missingKeys: $missingKeys);

                $this->info("Generated translations for {$domain} from {$from} to {$language}:");
                foreach ($translations as $key => $value) {
                    $this->line("{$key}: {$value}");
                }

                $this->newLine();

                if (!$this->option('dry-run')) {
                    $this->info("Writing translations for {$domain} from {$from} to {$language}");

                    $toFile->apply($translations)->write();
                }
                $this->newLine();
			}
		}
	}
}

