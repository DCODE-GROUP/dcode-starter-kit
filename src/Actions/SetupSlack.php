<?php

declare(strict_types=1);

namespace DcodeStarterKit\DcodeStarterKit\Actions;

use RuntimeException;

class SetupSlack
{
    public const string STEP = 'Slack setup';

    /** @param callable(string): void $output */
    public function setup(string $workingDirectory, callable $output): bool
    {
        try {
            $this->configureServices($workingDirectory);
            $this->configureLogging($workingDirectory);
            $output("Slack notifications configured.\n");

            return true;
        } catch (RuntimeException $exception) {
            $output($exception->getMessage()."\n");

            return false;
        }
    }

    private function configureServices(string $workingDirectory): void
    {
        $path = $workingDirectory.'/config/services.php';
        $contents = $this->read($path, 'config/services.php');

        if (str_contains($contents, "'slack' => [")) {
            return;
        }

        $entry = <<<'PHP'

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],
PHP;

        $contents = preg_replace_callback(
            '/(return\s+\[)(.*?)(\n\];\s*)$/s',
            static fn (array $matches): string => $matches[1].$matches[2].$entry.$matches[3],
            $contents,
            1,
            $count,
        ) ?? $contents;

        if ($count !== 1 || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to add Slack notification settings safely to config/services.php.');
        }
    }

    private function configureLogging(string $workingDirectory): void
    {
        $path = $workingDirectory.'/config/logging.php';
        $contents = $this->read($path, 'config/logging.php');

        if (str_contains($contents, "'driver' => 'slack'")) {
            return;
        }

        $channel = <<<'PHP'

        'slack' => [
            'driver' => 'slack',
            'url' => env('LOG_SLACK_WEBHOOK_URL'),
            'username' => env('LOG_SLACK_USERNAME', env('APP_NAME', 'Laravel')),
            'emoji' => env('LOG_SLACK_EMOJI', ':boom:'),
            'level' => env('LOG_LEVEL', 'critical'),
            'replace_placeholders' => true,
        ],
PHP;

        $contents = preg_replace_callback(
            '/(\n    \],\n\n\];\s*)$/s',
            static fn (array $matches): string => $channel.$matches[1],
            $contents,
            1,
            $count,
        ) ?? $contents;

        if ($count !== 1 || file_put_contents($path, $contents) === false) {
            throw new RuntimeException('Unable to add the Slack logging channel safely to config/logging.php.');
        }
    }

    private function read(string $path, string $relative): string
    {
        if (! is_file($path)) {
            throw new RuntimeException("{$relative} was not found; Slack could not be configured safely.");
        }

        $contents = file_get_contents($path);

        if (! is_string($contents)) {
            throw new RuntimeException("Unable to read {$relative}.");
        }

        return $contents;
    }
}
