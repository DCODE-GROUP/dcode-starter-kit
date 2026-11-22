<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;

if (! function_exists('findMissingTranslationKeys')) {
    /**
     * Literal dotted keys passed to __() / trans() / trans_choice() that have no English translation.
     * Plain-text keys and dynamic keys are skipped because they cannot be resolved statically.
     *
     * @param  array<int, string>  $paths
     * @return array<string, array<int, string>>
     */
    function findMissingTranslationKeys(array $paths): array
    {
        $missing = [];

        foreach ($paths as $path) {
            foreach (File::allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $source = $file->getContents();

                preg_match_all('/\b(?:__|trans|trans_choice)\(\s*([\'\"])([\w-]+(?:\.[\w-]+)+)\1\s*[,)]/', $source, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

                foreach ($matches as $match) {
                    $key = $match[2][0];

                    if (Lang::has($key, 'en', false)) {
                        continue;
                    }

                    $line = substr_count(substr($source, 0, $match[0][1]), "\n") + 1;
                    $missing[$key][] = str_replace(base_path().'/', '', $file->getPathname()).':'.$line;
                }
            }
        }

        ksort($missing);

        return $missing;
    }
}

it('has an English translation for every translation key used in the app', function (): void {
    $missing = findMissingTranslationKeys([
        app_path(),
        resource_path('views'),
        base_path('routes'),
        config_path(),
        database_path(),
    ]);

    $report = collect($missing)
        ->map(fn (array $locations, string $key): string => $key.' ('.implode(', ', $locations).')')
        ->implode(PHP_EOL);

    expect($missing)->toBeEmpty('Missing translations in lang/en:'.PHP_EOL.$report);
});

describe('finding missing translation keys', function (): void {
    beforeEach(function (): void {
        $this->directory = storage_path('framework/testing/translations-'.uniqid());
        File::ensureDirectoryExists($this->directory);
    });

    afterEach(function (): void {
        File::deleteDirectory($this->directory);
    });

    it('reports missing keys with their file and line', function (): void {
        File::put($this->directory.'/Example.php', implode(PHP_EOL, [
            '<?php',
            "__('generic.not_a_real_key');",
            'trans_choice("schedule.print.not_a_real_choice", 2);',
            "trans('generic.not_a_real_key', ['name' => 'x']);",
        ]));

        $relative = str_replace(base_path().'/', '', $this->directory).'/Example.php';

        expect(findMissingTranslationKeys([$this->directory]))->toBe([
            'generic.not_a_real_key' => [$relative.':2', $relative.':4'],
            'schedule.print.not_a_real_choice' => [$relative.':3'],
        ]);
    });

    it('ignores keys that exist, plain-text keys and dynamic keys', function (): void {
        File::put($this->directory.'/Example.blade.php', implode(PHP_EOL, [
            "{{ __('generic.not_applicable') }}",
            "{{ __('No Brand') }}",
            "{{ __('generic.buttons.\$action') }}",
            '{{ __("generic.{$group}.label") }}',
        ]));

        expect(findMissingTranslationKeys([$this->directory]))->toBeEmpty();
    });
});
