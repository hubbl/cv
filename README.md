# Content-first CV & Portfolio

A bilingual CV and project portfolio built as a compact example of modern Symfony
architecture. The application is server-rendered, database-free, responsive, and
keeps editorial content separate from application code and presentation.

**Live:** [Deutsch](https://dennis-otto.net/cv/de/) ·
[English](https://dennis-otto.net/cv/en/)

## Engineering highlights

- **Typed content pipeline:** YAML and Markdown are mapped to readonly PHP objects
  and validated while loading.
- **Safe file access:** locale and slug allowlists prevent content lookups from
  escaping the expected directory tree.
- **Sanitized Markdown:** raw HTML is stripped and rendered output passes through
  Symfony's HTML Sanitizer.
- **Meaningful UI components:** Twig Components model CV concepts such as
  experiences, projects, periods, technologies, and language switching.
- **Predictable localization:** German and English use mirrored content trees and
  shared slugs, so the language switch keeps users on the equivalent page.
- **Production-oriented delivery:** parsed content is cached in production, assets
  are compiled into the image, and deployment uses checked, atomic release artifacts.

## Stack

- PHP 8.4+ and Symfony 8.1
- Twig and Symfony UX Twig Components
- Tailwind CSS 4 through TailwindBundle, without a Node.js runtime
- AssetMapper, Validator, Cache, Translation, YAML, and HTML Sanitizer
- League CommonMark
- Docker and FrankenPHP
- PHPUnit, PHPStan, and PHP-CS-Fixer

The supplied Docker images run PHP 8.5. The application itself supports PHP 8.4
and later.

## Architecture

```text
YAML      = structured data and metadata
Markdown  = longer editorial content
PHP       = typed, validated content model
Twig      = presentation
```

```text
content/de + content/en       Localized, mirrored source trees
           │
           ▼
LocalizedContentLocator       Safe and locale-aware paths
           │
           ▼
YamlContentLoader ───────► MarkdownRenderer + HTML sanitizer
           │
           ▼
readonly PHP content objects  Profile, Experience, Project, Period, Link…
           │
           ▼
ContentRepository             Direct reads in dev, Symfony cache in prod
           │
           ▼
Controllers → Twig Components
```

Controllers do not read files, Twig does not know content paths, and content files
contain no layout or CSS decisions.

The localized source trees are deliberately independent:

```text
content/
├── de/
│   ├── profile.yaml
│   └── experience/<slug>/...
└── en/
    ├── profile.yaml
    └── experience/<slug>/...
```

YAML describes structured facts; Markdown is reserved for prose. Adding a language
means mirroring one content tree and registering the locale in Symfony's locale and
route configuration.

## Quick start with Docker

Docker is the shortest path to a local instance:

```bash
docker compose up --build
```

Open [http://localhost:8080/de/](http://localhost:8080/de/) or `/en/`.

The source tree is mounted read-only. Container-managed `vendor/` dependencies and
writable `var/` data live in shared Docker volumes. The additional `tailwind`
service checks Twig, PHP, and CSS sources for changes and rebuilds the stylesheet.
Polling is intentional because filesystem events from Windows bind mounts can be
unreliable.

TailwindBundle downloads and invokes Tailwind's standalone CLI through Symfony; no
Node.js process is involved. The web and watcher containers share the downloaded
binary and generated development data through the `var/` volume.

After changing `composer.lock`, recreate the development volumes:

```bash
docker compose down -v
docker compose up --build
```

Watcher output is available with `docker compose logs -f tailwind`.

## Native development

Requirements: PHP 8.4+ with Ctype, DOM, and Iconv; Composer 2; the Symfony CLI; and
a POSIX-like shell or PowerShell. The `intl` extension is recommended.

```bash
composer install
php bin/console tailwind:build --watch
symfony server:start
```

Open `http://127.0.0.1:8000/de/` or `/en/`. Composer compiles production assets
during installation. The watcher is only needed while editing templates or styles;
content changes are read immediately in `dev`.

## Tests and code quality

```bash
composer qa
```

This runs PHPUnit, PHPStan, and PHP-CS-Fixer in dry-run mode. Tests cover content
invariants and validation, safe localized lookup, Markdown sanitization, both locale
trees, detail routes, invalid slugs and locales, and equivalent language-switch URLs.

## Production and deployment

The multi-stage production image compiles Tailwind and AssetMapper assets and ships
only FrankenPHP, PHP, Symfony, static assets, and content. It needs neither a Node.js
runtime nor a database.

The live STRATO deployment is built locally as a versioned archive and uploaded over
SSH. The server performs no Git checkout, Composer install, or asset build. Its PHP
8.5 CLI warms Symfony's production cache before Deployer atomically switches the
`current` symlink.

Build the artifact without deploying it:

```powershell
docker compose -f compose.deploy.yaml run --build --rm build
```

See the [STRATO deployment runbook](deploy/README.md) for initial setup, deployment,
verification, and rollback.

## License

[MIT](LICENSE)
