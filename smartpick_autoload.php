<?php
/**
 * SmartPick Autoload Bootstrap
 *
 * To-mode strategi:
 * 1. Hvis `composer install` er kørt, bruges Composers genererede autoloader (vendor/autoload.php).
 * 2. Ellers falder vi tilbage til et simpelt selvstændigt classmap-scan, så modulet virker
 *    uden Composer på miljøer hvor det ikke er installeret (almindeligt for delte Dolibarr-hosting).
 *
 * OBS: Eksisterende entry-points (admin/*.php, api/*.php, script/*.php) bruger fortsat
 * eksplicitte require_once-kald til de klasser de har brug for — de er IKKE afhængige af
 * denne fil. Denne bootstrap er for ny kode i application/-laget og fremtidige services,
 * så de kan referere klasser via `use` uden at skulle kende den fysiske filsti.
 */

if (!defined('SMARTPICK_MODULE_ROOT')) {
    define('SMARTPICK_MODULE_ROOT', __DIR__);
}

$composerAutoload = SMARTPICK_MODULE_ROOT . '/vendor/autoload.php';

if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
    return;
}

// --- Fallback: manuelt classmap-scan (ingen Composer nødvendig) ---
spl_autoload_register(function ($class) {
    static $classmap = null;

    if ($classmap === null) {
        $classmap = [];
        $dirs = ['domain', 'application', 'infrastructure', 'events', 'rules', 'interfaces', 'legacy'];

        foreach ($dirs as $dir) {
            $path = SMARTPICK_MODULE_ROOT . '/' . $dir;
            if (!is_dir($path)) {
                continue;
            }
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            foreach ($iterator as $file) {
                if ($file->isFile() && preg_match('/\.php$/', $file->getFilename())) {
                    $contents = file_get_contents($file->getPathname());

                    if (preg_match('/^namespace\s+([^;]+);/m', $contents, $nsMatch)
                        && preg_match('/^(?:abstract\s+|final\s+)?(?:class|interface|trait)\s+(\w+)/m', $contents, $classMatch)) {
                        $fqcn = trim($nsMatch[1]) . '\\' . $classMatch[1];
                        $classmap[$fqcn] = $file->getPathname();
                    }
                }
            }
        }
    }

    if (isset($classmap[$class])) {
        require_once $classmap[$class];
    }
});
