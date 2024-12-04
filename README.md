# AI Translations for Laravel 🤖

> Automatically translate your Laravel application's language files with high accuracy and context awareness using the power of LLMs. Translate to completely new languages or keep your existing language files up-to-date, without completely re-generating the full file. 
> 
> This package also includes tools for validation of the files, maintaining quality and consistency.

<img src="./docs/screenshot-1.webp" >

## ✨ Features

- 🔄 Automatic translation of Laravel language files
- 🧠 Context-aware translations using Claude AI
- 🔍 Smart detection of missing translations
- 💬 Interactive chat mode for translation refinement
- ✅ Validation tools for quality assurance

## 🚀 Installation

```bash
composer require mateffy/laravel-ai-translations
```

Publish the configuration file:

```bash
php artisan vendor:publish --provider="Mateffy\AiTranslations\AiTranslationServiceProvider"
```

## 🛠️ Commands

### Translate Files

```bash
php artisan translate
```

This command translates your language files to all configured languages. It:
1. Detects missing translations
2. Uses AI to generate appropriate translations
3. Writes the translations to your language files

**Options:**
- `--dry-run` : Preview translations without saving
- `--name=<file>` : Translate a specific file only
- `--language=<code>` : Translate to a specific language only
- `--base-language=<code>` : Source language (defaults to `app.locale`)

### Add New Language

```bash
php artisan translate:add {language}
```

Adds a new language to your application by:
1. Creating the language directory
2. Translating all existing files to the new language

**Options:**
- `--base-language=<code>` : Source language for translations

### Improve Translations

```bash
php artisan translate:improve {name} {language}
```

Opens an interactive chat session with the AI to refine translations for:
- Specific translation files
- Context-aware improvements
- Cultural nuances

**Options:**
- `--base-language=<code>` : Source language for comparison

### Validate Translations

```bash
php artisan translate:validate
```

Performs comprehensive validation of your translations by:
1. Checking for missing keys
2. Comparing source and target files
3. Generating detailed reports

**Options:**
- `--name=<file>` : Validate specific file
- `--language=<code>` : Validate specific language
- `--base-language=<code>` : Source language for validation

## 🔧 How It Works

The TranslationFile class reads Laravel's PHP language files into memory, preserving their nested array structure while allowing access through dot notation (e.g., 'auth.failed' => 'message'). It handles both reading existing translations and creating new language files.

When translating, the system always provides the full source and target language files to the LLM to ensure it has complete context about the existing translations and their relationships. This helps maintain consistency in terminology and style across the application.

The LLM then returns only the translated strings that need to be added or updated, using dot notation. This selective return is efficient as it allows precise updates without regenerating the entire translation file. These dot-notated translations are automatically merged into the existing translation structure, handling both new keys and updates to existing ones.

## ⚙️ Configuration

Configuration options in `config/ai-translations.php`:

```php
return [
    // Supported languages (optional)
    'languages' => ['en', 'de', 'fr', 'es'],
    
    // Additional configuration options can be added here
];
```

## 🤝 Contributing

Contributions are welcome! Please feel free to submit pull requests.

## 📄 License

This package is open-source software licensed under the [MIT license](./LICENSE).
```
