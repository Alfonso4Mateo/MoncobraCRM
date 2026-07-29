<?php
// scripts/check_routes.php
// Boot the Laravel app and check all `route('name')` usages in PHP/Blade files
chdir(__DIR__ . '/..');
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
// Bootstrap kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(getcwd()));
$uses = [];
foreach ($iterator as $file) {
    if (!$file->isFile()) continue;
    $path = $file->getPathname();
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if (!in_array($ext, ['php', 'blade.php', 'blade'])) continue;
    $contents = file_get_contents($path);
    if ($contents === false) continue;
    if (preg_match_all("/route\\(\\s*['\"]([A-Za-z0-9\\._\\-]+)['\"]/", $contents, $m)) {
        foreach ($m[1] as $name) {
            $uses[$name] = true;
        }
    }
}
$router = $app->make('router');
$missing = [];
foreach (array_keys($uses) as $name) {
    if (!method_exists($router, 'has') || !$router->has($name)) {
        $missing[] = $name;
    }
}

echo "Checked usages: " . count($uses) . "\n";
echo "Missing routes: " . count($missing) . "\n";
if (!empty($missing)) {
    echo "\nMissing list:\n";
    foreach ($missing as $m) echo " - $m\n";
}

return 0;
