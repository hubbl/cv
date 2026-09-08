<?php

declare(strict_types=1);

// Run against an extracted artifact at a different path from /app.
$root = $argv[1] ?? throw new RuntimeException('Pass the extracted artifact directory.');
foreach (['vendor/deployer', '.env.local', '.git', 'tests'] as $excluded) {
    if (file_exists($root . '/' . $excluded)) {
        throw new RuntimeException('Unexpected artifact input: ' . $excluded);
    }
}
if (count(glob($root . '/var/cache/*')) !== 0) {
    throw new RuntimeException('The artifact must not contain the build cache.');
}

require $root . '/vendor/autoload.php';
foreach ([
    'APP_ENV' => 'prod',
    'APP_DEBUG' => '0',
    'APP_SECRET' => 'artifact-verification-only',
    'APP_PUBLIC_URL' => 'https://dennis-otto.net/cv',
    'DEFAULT_URI' => 'https://dennis-otto.net/cv',
] as $name => $value) {
    putenv($name . '=' . $value);
    $_SERVER[$name] = $_ENV[$name] = $value;
}
(new Symfony\Component\Dotenv\Dotenv())->bootEnv($root . '/.env');

$kernel = new App\Kernel('prod', false);
foreach (['/de/', '/en/', '/de/experience/smartbroker'] as $path) {
    $request = Symfony\Component\HttpFoundation\Request::create(
        'https://dennis-otto.net/cv' . $path,
        'GET',
        server: [
            'SCRIPT_NAME' => '/cv/index.php',
            'SCRIPT_FILENAME' => $root . '/public/index.php',
            'PHP_SELF' => '/cv/index.php',
        ],
    );
    $response = $kernel->handle($request);
    if ($response->getStatusCode() !== 200) {
        throw new RuntimeException('Artifact route failed: ' . $path . ' (' . $response->getStatusCode() . ')');
    }
    $html = $response->getContent();
    preg_match_all('~(?:href|src)="(/cv/assets/[^"?#]+)"~', $html, $matches);
    if (count($matches[1]) < 2) {
        throw new RuntimeException('CSS/JavaScript URLs do not include /cv.');
    }
    foreach ($matches[1] as $asset) {
        if (!is_file($root . '/public' . substr($asset, strlen('/cv')))) {
            throw new RuntimeException('Compiled asset is missing: ' . $asset);
        }
    }
    if (!str_contains($html, 'content="https://dennis-otto.net/cv' . $path . '"')) {
        throw new RuntimeException('Social URL does not include the correct mount path.');
    }
    if (!str_contains($html, 'href="/cv/de/"') || !str_contains($html, 'href="/cv/en/"')) {
        // Detail pages retain the current route when changing language.
        if ($path === '/de/' || $path === '/en/') {
            throw new RuntimeException('Navigation lost the /cv prefix.');
        }
    }
    $kernel->terminate($request, $response);
}
$kernel->shutdown();
echo "Artifact verified after relocation: German, English, detail route, assets and /cv URLs.\n";
