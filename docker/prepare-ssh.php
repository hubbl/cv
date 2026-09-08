<?php

declare(strict_types=1);

// The destination is tmpfs; no credentials are copied into the image or project.
$source = '/ssh-source';
$destination = '/root/.ssh';
if (!is_file($source . '/config')) {
    throw new RuntimeException('SSH config missing. Set SSH_DIRECTORY to your local .ssh directory.');
}

$hostDirectory = rtrim(str_replace('\\', '/', getenv('SSH_SOURCE_DIRECTORY') ?: ''), '/');
$iterator = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST,
);
foreach ($iterator as $file) {
    $relative = substr($file->getPathname(), strlen($source) + 1);
    $target = $destination . '/' . $relative;
    if ($file->isLink()) {
        throw new RuntimeException('SSH source contains a symlink. Provide a directory with regular config/key files.');
    }
    if ($file->isDir()) {
        if (!is_dir($target) && !mkdir($target, 0700, true)) {
            throw new RuntimeException('Cannot create temporary SSH directory.');
        }
        continue;
    }
    if (!$file->isFile()) {
        continue; // For example, an agent socket.
    }
    $contents = file_get_contents($file->getPathname());
    if ($contents === false) {
        throw new RuntimeException('Cannot read an SSH input file.');
    }
    // Rewrite only configuration directives, never key material or known_hosts.
    $contents = preg_replace_callback(
        '/^(\s*(?:Include|IdentityFile|CertificateFile|UserKnownHostsFile)\s+)(.+)$/mi',
        static function (array $match) use ($hostDirectory, $destination): string {
            $path = str_replace('\\', '/', rtrim($match[2], "\r"));
            if ($hostDirectory !== '') {
                $path = str_ireplace($hostDirectory . '/', $destination . '/', $path);
            }
            return $match[1] . $path;
        },
        $contents,
    );
    if (file_put_contents($target, $contents) === false || !chmod($target, 0600)) {
        throw new RuntimeException('Cannot prepare a temporary SSH input file.');
    }
}
