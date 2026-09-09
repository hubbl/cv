# STRATO artifact deployment

The production release is built locally in Docker and uploaded as a checked archive.
STRATO needs no Git, Composer, Node.js, rsync, or asset tooling. It only needs SSH,
a POSIX shell, `tar` with gzip support, PHP 8.4+ for HTTP, and STRATO's PHP 8.5 CLI
at `/opt/RZphp85/bin/php-cli`. Ctype, DOM, and Iconv are required; Intl is
recommended. The web and CLI PHP configurations must satisfy the same Composer
platform requirements.

Deployer keeps five releases and switches `current` only after the new release has
passed its checks and production cache warmup.

## Server layout

```text
/mnt/web024/d3/82/51918182/htdocs/
├── dennis-otto.net/
│   └── cv -> ../dennis-otto.net_cv/current/public
└── dennis-otto.net_cv/
    ├── .dep/
    ├── current -> releases/1
    ├── releases/
    │   └── 1/             # application, vendor/, assets, and warm prod cache
    └── shared/
        ├── .env.local
        └── var/log/
```

The application is available at `https://dennis-otto.net/cv/de/` and `/cv/en/`.

## 1. Build the release locally

Start Docker Desktop with Linux containers, then run from the repository root:

```powershell
docker compose -f compose.deploy.yaml run --build --rm build
```

This creates `.deploy/release.tar.gz` and its SHA-256 file. The archive contains the
current working tree, including uncommitted changes, production dependencies from
`composer.lock`, and compiled Tailwind and AssetMapper assets. It excludes local
secrets, tests, development dependencies, and SSH keys.

Before export, the build extracts the archive elsewhere and checks both languages,
a detail page, compiled assets, and navigation below `/cv`. Symfony's cache is not
included because it contains absolute paths; it is created on STRATO before the
release becomes public.

## 2. SSH access from the Deployer container

On Windows, `$env:USERPROFILE/.ssh` is mounted read-only by default. To use another
directory, set `SSH_DIRECTORY` before running Deployer:

```powershell
$env:SSH_DIRECTORY = 'C:/path/to/.ssh'
```

On Linux or macOS, use `export SSH_DIRECTORY="$HOME/.ssh"`.

The SSH alias `strato` supplies hostname, user, port, and identity file.
`DEPLOY_USER` can override the user. The host key must already be present in
`known_hosts`

At startup, the container copies the needed SSH configuration and keys into a
memory-only filesystem with restrictive permissions. The originals remain unchanged
and `--rm` discards the copy. Deployer uses `/tmp/deployer-ssh-%C` for multiplexing so
its socket cannot collide with an identity file named `~/.ssh/strato`.

Keys or includes outside the mounted directory, Windows-only proxy commands,
hardware keys, and keys available only through the Windows agent need a dedicated
compatible SSH directory. Run interactively when SSH needs a key passphrase.

Inspect the local configuration without contacting STRATO:

```powershell
docker compose -f compose.deploy.yaml build deployer
docker compose -f compose.deploy.yaml run --rm deployer list
docker compose -f compose.deploy.yaml run --rm deployer tree deploy
docker compose -f compose.deploy.yaml run --rm deployer artifact:check
```

## 3. One-time server setup

1. Select PHP 8.4 or later for the website in STRATO and ensure the required
   extensions are enabled. The deployment preflight separately checks the PHP 8.5
   CLI; it cannot inspect the web PHP configuration.
2. Create `dennis-otto.net_cv/shared/.env.local` from
   `deploy/production.env.example`, replace `APP_SECRET` with a random value, and set
   file mode 600. `APP_PUBLIC_URL` and `DEFAULT_URI` must include `/cv`.
3. Verify that `dennis-otto.net/cv` is a symlink, then point it to
   `../dennis-otto.net_cv/current/public`. Before the first deployment, the new target
   may briefly be absent.

Only `public/` may be web-accessible. The release contains the root access guard and
the `public/` rewrite rules needed to serve Symfony below `/cv` while leaving the main
domain available for another application.

## 4. Deploy and roll back

The following command changes the live STRATO installation:

```powershell
docker compose -f compose.deploy.yaml run --rm deployer deploy
```

Deployer verifies the local checksum and server prerequisites, creates a release,
streams the archive over SSH, links shared configuration and logs, warms the Symfony
production cache, switches `current`, and removes expired releases. The artifact hash
is stored in `REVISION`.

After deployment, check `/cv/de/`, `/cv/en/`, one detail page, CSS, and JavaScript.
The local artifact build does not make an HTTP request to the live domain.

```powershell
docker compose -f compose.deploy.yaml run --rm deployer releases
docker compose -f compose.deploy.yaml run --rm deployer rollback
```

Rollback switches to the previous retained release; none exists after the first
deployment. The placeholder backup is outside Deployer's release history.

If an interrupted deployment leaves a lock, first verify that no deployment is still
running, then run `deployer deploy:unlock` in the Deployer container.
