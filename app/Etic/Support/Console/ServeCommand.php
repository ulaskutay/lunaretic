<?php

namespace App\Etic\Support\Console;

use Illuminate\Foundation\Console\ServeCommand as LaravelServeCommand;

use function Illuminate\Support\php_binary;

class ServeCommand extends LaravelServeCommand
{
    /**
     * @return list<string>
     */
    protected function serverCommand(): array
    {
        $command = parent::serverCommand();
        $command[0] = $this->phpBinaryWithIntl();

        array_splice($command, 1, 0, [
            '-d', 'upload_max_filesize=64M',
            '-d', 'post_max_size=64M',
            '-d', 'memory_limit=256M',
        ]);

        return $command;
    }

    private function phpBinaryWithIntl(): string
    {
        $candidates = [
            php_binary(),
            '/opt/homebrew/opt/php@8.3/bin/php',
            '/usr/local/opt/php@8.3/bin/php',
        ];

        foreach (array_unique(array_filter($candidates)) as $binary) {
            if (! is_executable($binary)) {
                continue;
            }

            $loaded = trim((string) shell_exec(escapeshellarg($binary).' -m 2>/dev/null | grep -i intl'));

            if ($loaded !== '') {
                return $binary;
            }
        }

        return php_binary();
    }
}
