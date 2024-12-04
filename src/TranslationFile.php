<?php

namespace Mateffy\AiTranslations;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;

class TranslationFile
{
    public function __construct(
        public readonly string $language,
        public readonly string $domain,
        public array $translations,
        protected readonly string $basePath
    )
    {
    }

    public static function path(string $language, string $domain, string $basePath): string
    {
        return "{$basePath}/{$language}/{$domain}.php";
    }

    public static function load(string $language, string $domain, ?string $basePath = null): static
    {
        $basePath ??= lang_path();

        $path = static::path($language, $domain, $basePath);

        if (! File::exists($path)) {
            return new static($language, $domain, [],  $basePath);
        }

        return new static($language, $domain, require $path, $basePath);
    }

    public function toJson(): string
    {
        if ($this->translations === []) {
            return '{}';
        }

        return json_encode($this->translations, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    public function toPhpArray(): string
    {
        return <<<PHP
        <?php

        return [
        {$this->convertArrayToString($this->translations, indent: 1)}
        ];
        PHP;
    }

    public function set(string $key, string $path): void
    {
        Arr::set($this->translations, $key, $path);
    }

    public function get(string $key): string
    {
        return Arr::get($this->translations, $key);
    }

    public function compare(TranslationFile $file): array
    {
        $flatKeys = $this->flatten($this->translations);
        $flatFileKeys = $this->flatten($file->translations);

        return array_diff($flatKeys, $flatFileKeys);
    }

    public function apply(array $translations): self
    {
        foreach ($translations as $key => $value) {
            $this->set($key, $value);
        }

        return $this;
    }

    public function write(): void
    {
        $path = static::path($this->language, $this->domain, $this->basePath);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $this->toPhpArray());
    }

    protected function flatten(array $array, string $prefix = ''): array
    {
        $output = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $output = array_merge($output, $this->flatten($value, $prefix . $key . '.'));
            } else {
                $output[] = $prefix . $key;
            }
        }

        return $output;
    }

    protected function convertArrayToString(array $array, int $indent = 0) {
        $output = '';
        $spaces = str_repeat('    ', $indent);

        foreach ($array as $key => $value) {
            $output .= $spaces;
            $output .= is_numeric($key) ? $key : "'" . addslashes($key) . "'";
            $output .= ' => ';

            if (is_array($value)) {
                $output .= "[\n" . $this->convertArrayToString($value, $indent + 1) . $spaces . "],\n";
            } else {
                $output .= "'" . addslashes($value) . "',\n";
            }
        }

        return $output;
    }
}
