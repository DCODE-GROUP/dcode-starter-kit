<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RuntimeException;

class SetupDefaultUsersMigration
{
    public const string STEP = 'default users migration setup';

    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $path = $this->findMigration($workingDirectory);
            $contents = file_get_contents($path);

            if (! is_string($contents)) {
                throw new RuntimeException('Unable to read the default users migration.');
            }

            $updated = $this->replaceUsersDefinition($contents);

            if ($updated !== $contents && file_put_contents($path, $updated) === false) {
                throw new RuntimeException('Unable to update the default users migration.');
            }

            $output("Default users migration configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function findMigration(string $workingDirectory): string
    {
        $matches = [];

        foreach (glob($workingDirectory.'/database/migrations/*.php') ?: [] as $path) {
            $contents = file_get_contents($path);

            if (is_string($contents)
                && preg_match("/Schema\\s*::\\s*create\\(\\s*['\"]users['\"]\\s*,\\s*function\\s*\\([^)]*\\)\\s*\\{/", $contents) === 1
                && str_contains($contents, "Schema::create('password_reset_tokens'")
                && str_contains($contents, "Schema::create('sessions'")) {
                $matches[] = $path;
            }
        }

        if (count($matches) !== 1) {
            throw new RuntimeException('Unable to identify one default users migration containing the users, password-reset tokens, and sessions tables. No migration was changed.');
        }

        return $matches[0];
    }

    private function replaceUsersDefinition(string $contents): string
    {
        $pattern = "/Schema\\s*::\\s*create\\(\\s*['\"]users['\"]\\s*,\\s*function\\s*\\([^)]*\\)\\s*\\{/";

        if (preg_match($pattern, $contents, $matches, PREG_OFFSET_CAPTURE) !== 1) {
            throw new RuntimeException('Unable to locate the users table definition safely. No migration was changed.');
        }

        $openingBrace = (int) $matches[0][1] + strlen((string) $matches[0][0]) - 1;
        $closingBrace = $this->findMatchingBrace($contents, $openingBrace);
        $lineStart = strrpos(substr($contents, 0, $openingBrace), "\n");
        $lineStart = $lineStart === false ? 0 : $lineStart + 1;
        $openingIndent = preg_match('/^\s*/', substr($contents, $lineStart, $openingBrace - $lineStart), $indentMatch) === 1
            ? $indentMatch[0]
            : '';
        $columnIndent = $openingIndent.'    ';
        $definition = "{\n"
            .$columnIndent."\$table->id();\n"
            .$columnIndent."\$table->string('first_name');\n"
            .$columnIndent."\$table->string('middle_name')->nullable();\n"
            .$columnIndent."\$table->string('last_name');\n"
            .$columnIndent."\$table->string('username')->nullable();\n"
            .$columnIndent."\$table->string('email')->unique();\n"
            .$columnIndent."\$table->timestamp('email_verified_at')->nullable();\n"
            .$columnIndent."\$table->string('password');\n"
            .$columnIndent."\$table->boolean('active')->default(0);\n"
            .$columnIndent."\$table->rememberToken();\n"
            .$columnIndent."\$table->string('profile_photo_path', 2048)->nullable();\n"
            .$columnIndent."\$table->lastModifiedFields();\n"
            .$columnIndent."\$table->softDeletes();\n"
            .$columnIndent."\$table->timestamps();\n"
            .$openingIndent.'}';

        return substr($contents, 0, $openingBrace).$definition.substr($contents, $closingBrace + 1);
    }

    private function findMatchingBrace(string $contents, int $openingBrace): int
    {
        $depth = 0;
        $length = strlen($contents);
        $quote = null;
        $escaped = false;

        for ($index = $openingBrace; $index < $length; $index++) {
            $character = $contents[$index];

            if ($quote !== null) {
                if ($escaped) {
                    $escaped = false;
                } elseif ($character === '\\') {
                    $escaped = true;
                } elseif ($character === $quote) {
                    $quote = null;
                }

                continue;
            }

            if ($character === "'" || $character === '"') {
                $quote = $character;
            } elseif ($character === '{') {
                $depth++;
            } elseif ($character === '}' && --$depth === 0) {
                return $index;
            }
        }

        throw new RuntimeException('Unable to match the users table definition safely. No migration was changed.');
    }
}
