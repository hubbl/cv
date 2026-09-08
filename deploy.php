<?php

declare(strict_types=1);

namespace Deployer;

require 'recipe/common.php';

set('application', 'cv-portfolio');
set('keep_releases', 5);
set('shared_files', ['.env.local']);
set('shared_dirs', ['var/log']);
set('writable_dirs', ['var', 'var/cache', 'var/log']);
set('writable_mode', 'chmod');
set('writable_chmod_mode', '0755');
set('writable_use_sudo', false);
set('allow_anonymous_stats', false);

set('artifact', __DIR__ . '/.deploy/release.tar.gz');
set('target', 'local-artifact');

$host = host('strato')
    ->set('deploy_path', '/mnt/web024/d3/82/51918182/htdocs/dennis-otto.net_cv')
    ->set('bin/php', '/opt/RZphp85/bin/php-cli')
    ->set('forward_agent', false)
    // Deployer defaults to ~/.ssh/strato, which is also this host's key file.
    ->set('ssh_control_path', '/tmp/deployer-ssh-%C')
    ->set('ssh_arguments', ['-o StrictHostKeyChecking=yes']);

// HostName, User, Port and IdentityFile otherwise come from the SSH config.
if ($remoteUser = getenv('DEPLOY_USER')) {
    $host->set('remote_user', $remoteUser);
}

desc('Checks the local artifact without connecting to STRATO');
task('artifact:check', function (): void {
    $artifact = get('artifact');
    if (!is_file($artifact) || !is_file($artifact . '.sha256')) {
        throw new \RuntimeException('Build first: docker compose -f compose.deploy.yaml run --build --rm build');
    }
    $expected = trim(file_get_contents($artifact . '.sha256'));
    if (!hash_equals($expected, hash_file('sha256', $artifact))) {
        throw new \RuntimeException('Artifact checksum mismatch. Rebuild before deploying.');
    }
    info('Artifact SHA-256: ' . $expected);
});

desc('Checks server prerequisites without changing the existing site');
task('deploy:preflight', function (): void {
    run('command -v tar >/dev/null');
    if (!test('[ -x {{bin/php}} ]')) {
        throw new \RuntimeException('PHP CLI 8.5 is missing at {{bin/php}}.');
    }
    run('{{bin/php}} -r ' . quote(
        'exit(PHP_VERSION_ID >= 80500 && extension_loaded("ctype") && extension_loaded("dom") && extension_loaded("iconv") ? 0 : 1);',
    ));
    if (test('[ -d {{current_path}} ] && [ ! -L {{current_path}} ]')) {
        throw new \RuntimeException('current is a real directory. Preserve the Hallo test directory under another name before the first deployment; see deploy/README.md.');
    }
    if (!test('[ -s {{deploy_path}}/shared/.env.local ]')) {
        throw new \RuntimeException('Create shared/.env.local on STRATO first; see deploy/production.env.example.');
    }
});

// Stream one archive over SSH. No Git, Composer or rsync on STRATO.
desc('Uploads and extracts the locally built production artifact');
task('deploy:update_code', function (): void {
    $host = currentHost();
    $extract = 'umask 022; tar -xzf - -C ' . quote(get('release_path'));
    $command = array_merge(['ssh'], $host->connectionOptions(), [$host->connectionString(), $extract]);
    runLocally(implode(' ', array_map(static fn ($arg): string => quote((string) $arg), $command))
        . ' < ' . quote(get('artifact')), timeout: 600);
    $revision = hash_file('sha256', get('artifact'));
    run('printf "%s\\n" ' . quote($revision) . ' > {{release_path}}/REVISION');
});

desc('Clears and warms Symfony cache in the unpublished release');
task('deploy:cache:warmup', function (): void {
    within('{{release_path}}', function (): void {
        run(
            '{{bin/php}} bin/console cache:clear --env=prod --no-debug --no-interaction',
            env: ['APP_ENV' => 'prod', 'APP_DEBUG' => '0'],
            timeout: 300,
        );
        if (!test('[ -d var/cache/prod ] && [ "$(find var/cache/prod -mindepth 1 -print -quit)" ]')) {
            throw new \RuntimeException('Symfony did not create a populated production cache.');
        }
    });
});

task('deploy', [
    'artifact:check',
    'deploy:preflight',
    'deploy:prepare',
    'deploy:cache:warmup',
    'deploy:publish',
]);

// Only unlock automatically if this deployment acquired and still holds the lock.
after('deploy:lock', function (): void {
    set('artifact_lock_acquired', true);
});
after('deploy:unlock', function (): void {
    set('artifact_lock_acquired', false);
});
after('deploy:failed', function (): void {
    if (get('artifact_lock_acquired', false)) {
        invoke('deploy:unlock');
    }
});
