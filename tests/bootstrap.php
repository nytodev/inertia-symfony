<?php

declare(strict_types=1);

require_once __DIR__.'/../vendor/autoload.php';

// Clear all compiled kernel caches before every test run so that
// InertiaBundle::configure() and loadExtension() are always executed
// from source (not from stale cached containers).
$baseCacheDir = sys_get_temp_dir().'/inertia-bundle-tests/cache';

foreach (['test', 'ssr'] as $env) {
    $cacheDir = $baseCacheDir.'/'.$env;

    if (!is_dir($cacheDir)) {
        continue;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($cacheDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );
    foreach ($iterator as $file) {
        $file->isDir() ? rmdir($file->getPathname()) : unlink($file->getPathname());
    }
}
