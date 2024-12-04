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

class CheckCommand extends Command
{
    protected $signature = 'translate:improve {name} {language} {--base-language=}';

    protected $description = 'Chat with an AI to improve your translations';

    public function __construct(protected Translator $translator)
    {
        parent::__construct();
    }

    public function handle(): void
	{
		$from = $this->option('base-language') ?? config('app.locale');

        $translation = new TranslationChat(
            domain: $this->argument('name'),
            source: $from,
            target: $this->argument('language')
        );

        while (true) {
            $messages = spin(fn () => $translation->chat->stream());

            foreach ($messages as $message) {
                $this->line($message->text() ?? '...');
            }

            $prompt = text(
                label: 'Your response',
                required: true
            );

            $translation->chat->addMessage(Magic\LLM\Message\TextMessage::user($prompt));
        }
	}
}

