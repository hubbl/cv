<?php

declare(strict_types=1);

namespace Deployer;

require 'recipe/symfony.php';

set('application', 'cv-portfolio');
set('repository', static fn (): string => getenv('DEPLOY_REPOSITORY') ?: throw new \RuntimeException('DEPLOY_REPOSITORY is required.'));
set('keep_releases', 5);
set('shared_files', ['.env.local']);
set('writable_dirs', ['var']);
set('allow_anonymous_stats', false);

$host = host(getenv('DEPLOY_HOST') ?: 'example.com')
    ->set('remote_user', getenv('DEPLOY_USER') ?: 'deploy')
    ->set('deploy_path', getenv('DEPLOY_PATH') ?: '/var/www/cv');

if ($identityFile = getenv('DEPLOY_IDENTITY_FILE')) {
    $host->set('identity_file', $identityFile);
}

task('deploy:assets', function (): void {
    run('{{bin/php}} {{release_path}}/bin/console tailwind:build --minify --env=prod');
    run('{{bin/php}} {{release_path}}/bin/console asset-map:compile --env=prod');
});

before('deploy:cache:clear', 'deploy:assets');
after('deploy:failed', 'deploy:unlock');
