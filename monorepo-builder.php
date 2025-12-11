<?php

declare(strict_types=1);

use Symplify\MonorepoBuilder\Config\MBConfig;

return static function (MBConfig $mbConfig): void {
    $mbConfig->packageDirectories([
        __DIR__.'/packages',
    ]);

    $mbConfig->dataToAppend([
        'require-dev' => [
            'laravel/pint' => '^1.14',
            'pestphp/pest' => '^3.0',
            'phpstan/phpstan' => '^1.0',
        ],
    ]);

    $mbConfig->packageDirectoriesExcludes([
        // Exclude any test directories or vendor folders
    ]);
};
