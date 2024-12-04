<?php

namespace Mateffy\AiTranslations\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Laravel\Prompts\Concerns\Colors;
use Mateffy\AiTranslations\TranslationChat;
use Mateffy\AiTranslations\TranslationFile;
use Mateffy\AiTranslations\Translator;
use Mateffy\Magic;
use function Laravel\Prompts\error;
use function Laravel\Prompts\note;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class ValidateCommand extends Command
{
    use Colors;

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
            $languages = collect([$language]);
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

        $verbose = $this->option('verbose');
        $missing = [];

        foreach ($domains as $domain) {
            if ($verbose) {
                $this->line("Checking {$domain}...");
                $this->newLine();
            }

            $source = TranslationFile::load($from, $domain);

            foreach ($languages as $language) {
                $target = TranslationFile::load($language, $domain);

                if ($verbose) {
                    $this->line("Checking {$domain} in {$language}...");
                    $this->newLine();
                }

                $missingKeys = $source->compare($target);

                if (count($missingKeys) > 0) {
                    if ($verbose) {
                        error("Missing translations for `{$domain}` in `{$language}`:");
                        table(['Key', 'Value'], collect($missingKeys)
                            ->map(fn ($key) => [Str::limit($key, 100), Str::limit($source->get($key), 70)])
                        );
                        $this->newLine();
                    }

                    $missing["{$language}/{$domain}"] = count($target->translations) === 0
                        ? $this->red('–')
                        : $this->red(count($missingKeys));
                } else {
                    if ($verbose) {
                        $this->info("No missing translations for {$domain} in {$language}");
                    }

                    $missing["{$language}/{$domain}"] = $this->green('✔');
                }

                if ($verbose) {
                    $this->newLine();
                }
            }
        }

        if (!$verbose) {
            table(['Domain', 'Missing Keys'], collect($missing)
                ->sortKeys()
                ->map(fn ($count, $domain) => [$domain, $count])
            );
        }
	}
}

