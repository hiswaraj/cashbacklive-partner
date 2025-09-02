<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withAttributesSets()
    ->withImportNames(importDocBlockNames: false, importShortClasses: false)
    ->withPaths([
        __DIR__.'/app',
        __DIR__.'/bootstrap/app.php',
        __DIR__.'/config',
        __DIR__.'/database',
        __DIR__.'/public_html',
        __DIR__.'/routes',
        __DIR__.'/tests',
    ])
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        codingStyle: false, // TODO: refactor gradually
        typeDeclarations: true,
        privatization: true,
        naming: false, // TODO: refactor gradually
        instanceOf: false, // TODO: refactor gradually
        earlyReturn: true,
        strictBooleans: true,
        carbon: true,
        rectorPreset: true,
        phpunitCodeQuality: true,
    )
    ->withPhpSets()
    ->withSkip([
        Rector\Privatization\Rector\ClassMethod\PrivatizeFinalClassMethodRector::class => [
            __DIR__.'/app/Filament/Pages/ManageTelegramSettings.php',
        ],
        Rector\DeadCode\Rector\ClassMethod\RemoveUnusedPublicMethodParameterRector::class => [
            __DIR__.'/app/Listeners/LogSuccessfulLogin.php',
        ],
    ]);
