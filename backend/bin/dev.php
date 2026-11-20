<?php

declare(strict_types=1);

$php = selectPhp();
$phpCommand = $php['command'];

$commands = [
    $phpCommand.' artisan serve',
    $phpCommand.' artisan queue:listen --tries=1 --timeout=0',
];
$colors = ['#93c5fd', '#c4b5fd'];
$names = ['server', 'queue'];

if ($php['pcntl']) {
    $commands[] = $phpCommand.' artisan pail --timeout=0';
    $colors[] = '#fb7185';
    $names[] = 'logs';
} else {
    fwrite(
        STDERR,
        $phpCommand.' '.$php['version']." には pcntl が無いため php artisan pail は起動しません。ログは storage/logs/laravel.log を参照してください。\n"
    );
}

$commands[] = 'npm run dev';
$colors[] = '#fdba74';
$names[] = 'vite';

$arguments = array_merge(
    ['npx', 'concurrently', '-c', implode(',', $colors)],
    $commands,
    ['--names='.implode(',', $names), '--kill-others']
);

$command = implode(' ', array_map(
    static fn (string $argument): string => '"'.str_replace('"', '""', $argument).'"',
    $arguments
));

$resultCode = 1;
passthru($command, $resultCode);
exit($resultCode);

/**
 * @return array{command: string, version: string, pcntl: bool}
 */
function selectPhp(): array
{
    $best = ['command' => 'php', 'version' => '0', 'pcntl' => false];

    foreach (['php', 'php.exe'] as $command) {
        $output = [];
        $code = 1;
        $probe = 'echo PHP_VERSION, " ", function_exists("pcntl_fork") ? "yes" : "no";';
        exec($command.' -r '.escapeshellarg($probe), $output, $code);
        if ($code !== 0) {
            continue;
        }

        $parsed = null;
        foreach ($output as $line) {
            if (preg_match('/(\d+\.\d+\.\d+)\s+(yes|no)/', $line, $matches) === 1) {
                $parsed = $matches;
            }
        }
        if ($parsed === null) {
            continue;
        }
        if (version_compare($parsed[1], $best['version'], '<=')) {
            continue;
        }

        $best = [
            'command' => $command,
            'version' => $parsed[1],
            'pcntl' => $parsed[2] === 'yes',
        ];
    }

    return $best;
}
