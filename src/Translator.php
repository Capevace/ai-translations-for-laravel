<?php

namespace Mateffy\AiTranslations;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Mateffy\Magic;
use Mateffy\Magic\Chat\Messages\TextMessage;
use Mateffy\Magic\Exceptions\InvalidRequest;

class Translator
{
    public function getLanguages(): Collection
    {
        $languages = config('ai-translations.languages');

        if ($languages) {
            return collect($languages);
        }

        return collect(File::directories(lang_path()))
            ->map(fn ($dir) => basename($dir))
            ->filter(fn ($dir) => ! in_array($dir, ['vendor', 'lang']))
            ->values();
    }

    public function getModel(): Magic\Models\LLM
    {
        return Magic\Models\ElElEm::fromString(config('ai-translations.model'));
    }

    public function translate(TranslationFile $from, TranslationFile $to, array $missingKeys): array
    {
        $systemPrompt = <<<PROMPT
        <instructions>
        You are a real estate software translator.
        You translate Laravel language files. You are given the translations in {$from->language} language and you need to translate it to {$to->language} language.
        Do not output any text directly, only call the `translate` tool with a flat array of dot-notation keys that you have translated.

        You are given the contents of the original {$from->language} file, and contents of the {$to->language} file if it exists already and is outdated.
        You are given a JSON representation of the Laravel langauge files. Imagine, you are looking at a Laravel language file in PHP array format instead.
        If a file is passed, try to reuse the old translations or to adapt your new translations with the existing ones as context. We don't want to change existing UI around too much.

        You are also given a list of all the missing keys so you can better spot the missing translations. If the translation doesn't exist yet, the missing keys will be empty. In this case, you need to translate the full file.

        When translating the complete language file, DO NOT LEAVE ANY MISSING KEYS! Translate the full file, including ALL keys. No skipping permitted.
        DO NOT OUTPUT ANY MARKDOWN ```php OR STUFF LIKE THAT, ONLY THE CODE!
        DO NOT MISS ANY KEYS! INCLUDE ALL TRANSLATIONS, including `blade` ones.
        </instructions>
        PROMPT;

        $task = count($missingKeys) === 0
            ? "Please translate the complete <{$from->language}-file> to the `{$to->language}` language. Translate the full file, including ALL keys. No skipping permitted."
            : "Please translate the given keys that have not been translated from <{$from->language}-file> to the <{$to->language}-file> yet. Translate ONLY the missing keys. No need to translate the full file.";

        $missingKeysText = collect($missingKeys)
//            ->map(fn ($key) => "{$key}: {$from->get($key)}")
            ->implode("\n");

        $allTranslations = collect();

        try {
            return Magic::chat()
                ->model($this->getModel())
                ->system($systemPrompt)
                ->tools([
                    /**
                     * @description Translate the missing keys or the full file. Pass a flat key-value array of translations (array<string, string>). DO NOT NEST ARRAYS, ONLY FLAT KEY-VALUE PAIRS OF DOT-NOTATED KEYS and their values.
                     * @type $translations {"type":"object","additionalProperties":{"type":"string"}}
                     */
                    'translate' => function (array $translations = []) use ($missingKeys, &$allTranslations) {
                        // If the AI doesn't return a string[] array, we tell it to try again.
                        $hasNestedTranslations = collect($translations)
                            ->some(fn($value) => is_array($value));

                        if ($hasNestedTranslations) {
                            $translations = TranslationFile::flattenWithValues($translations);
                            //                        return Magic::error('The translations you have given is not a flat array<string, string> array. The response needs to be a flat array, keyed by dot-notated keys and their translations. For example ["key1.subkey1": "translation1", "key1.subkey2": "translation2"]. Please try again.');
                        }

                        $allTranslations = $allTranslations->merge($translations);

                        $currentlyMissingKeys = collect($missingKeys)
                            ->filter(fn(string $key) => empty($allTranslations[$key] ?? null))
                            ->values();

                        if ($currentlyMissingKeys->isNotEmpty()) {
                            return Magic::error('The translations you have given are missing some keys. Skip the ones you just translated and please translate and add the following missing keys: ' . $currentlyMissingKeys->join(', ') . '. IT IS NOW OKAY TO ONLY TRANSLATE THE MISSING KEYS. DO NOT RE-TRANSLATE THE KEYS YOU HAVE ALREADY TRANSLATED.');
                        }

                        return Magic::end($allTranslations);
                    }
                ])
                ->toolChoice('translate')
//                ->onMessageProgress(fn ($message) => dump($message->partial ?? $message))
                ->messages([
                    TextMessage::user(<<<PROMPT
                    <{$from->language}-file>
                    {$from->toJson()}
                    </{$from->language}-file>
                    
                    <{$to->language}-file>
                    {$to->toJson()}
                    </{$to->language}-file>
                    
                    <missing-keys>
                    {$missingKeysText}
                    </missing-keys>
                    
                    <task>{$task}</task>
                    PROMPT
                    )
                ])
                ->stream()
                ->lastData();
        } catch (InvalidRequest $request) {
            if (str($request->getMessage())->startsWith('This request would exceed your organization')) {
                dump([
                    'Exceeded rate limits, waiting for 60 seconds...',
                ]);

                sleep(60);

                return $this->translate($from, $to, $missingKeys);
            }

            throw $request;
        }
    }
}
