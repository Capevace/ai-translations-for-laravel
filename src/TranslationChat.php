<?php

namespace Mateffy\AiTranslations;

use Mateffy\Magic;
use Mateffy\Magic\Chat\Messages\TextMessage;

class TranslationChat
{
    public Magic\Builder\ChatPreconfiguredModelBuilder $chat;

    public function __construct(
        protected string $domain,
        protected string $source,
        protected string $target
    )
    {
        $this->chat = $this->makeChat();
    }

    public function makeChat(): Magic\Builder\ChatPreconfiguredModelBuilder
    {
        $fromFile = TranslationFile::load($this->source, $this->domain);
        $toFile = TranslationFile::load($this->target, $this->domain);

        return Magic::chat()
            ->model(app(Translator::class)->getModel())
            ->system(<<<PROMPT
            You are a Laravel translations expert.
            You will be given a two Laravel translation files in different languages.
            One is the main language (source) the other is the language we want to translate to (target).

            Your initial task is to analyze the translation file you are given, especially the target in relation to the source.

            Is it a good translation?
            Are there any missing keys?
            Any major translation errors?
            Are there possible misconceptions we should be aware of?
            Are there any "we're eating grandpa" type of errors?
            Does the context of the translation make sense (no "Retten" for "Save" etc.)?

            Answer questions like these in your analysis, you can also suggest improvements.
            In your response, provide actionable feedback and suggestions for improvement and ask the user, what they want you to do / if they want you to do them.

            You have access to two tools:
            1.`translate`
            Writes some translations to the target file. Pass a flat key-value array of translations in dot-notation.
            For example: `translate(['auth.failed' => 'These credentials do not match our records.'])
            Make sure to not nest any arrays using this tool! Only use flattened dot-notated keys.

            2.`loadTranslationFile`
            Loads the translation file for the given language and domain and reads it to you.
            Useful if the user wants you to look at other files or has made manual changes in between messages.

            Using the tools you are given, help the user to improve their translations.
            Don't load the files right at the beginning using the tool and use the file contents provided in the initial message.
            Only after the user responds may you load the files using the `loadTranslationFile` tool.

            The tone of your output should be straight to the point. No thank yous, no goodbyes, no introductions. Just the facts.
            `
            PROMPT)
            ->messages([
                TextMessage::user(<<<PROMPT
                Hi, I'm a Laravel developer and I need help with my translations.
                I need you to take a look at my {$this->domain} translation domain/file.
                I have a file in the {$this->source} language that I want to translate to {$this->target}.

                <source-file language="{$fromFile->language}">
                {$fromFile->toJson()}
                </source-file>

                <target-file language="{$toFile->language}">
                {$toFile->toJson()}
                </target-file>
                PROMPT)
            ])
            ->tools([
                /**
                 * @description Translate the missing keys or the full file. Pass a flat key-value array of translations (array<string, string>).
                 * @type $translations {"type":"object","additionalProperties":{"type":"string"}}
                 */
                'translate' => function (string $language, string $domain, array $translations) {
                    if ($language === app()->getLocale()) {
                        return Magic::error('You cannot translate to the same language as the source file.');
                    }

                    $toFile = TranslationFile::load($language, $domain);
                    $toFile->apply($translations)->write();
                },

                'loadTranslationFile' => fn (string $language, string $domain) => TranslationFile::load($language, $domain)->toJson(),
            ]);
    }
}
