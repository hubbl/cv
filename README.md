# Content-first CV

A multilingual CV and project portfolio built as a compact example of modern Symfony architecture. The site is server-rendered, database-free, responsive, and designed so that all résumé content can be edited without touching PHP or Twig.

## Stack

- PHP 8.5 and Symfony 8.1
- Twig and semantic Twig Components
- Tailwind CSS 4 via SymfonyCasts TailwindBundle (no Node.js runtime)
- Symfony YAML, Validator, Cache, Translation, AssetMapper, and HTML Sanitizer
- League CommonMark for Markdown rendering
- Docker/FrankenPHP for local and containerized operation
- PHPUnit, PHPStan, and PHP-CS-Fixer

## Architecture

The application keeps four concerns deliberately separate:

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

Controllers never read files, Twig never knows file paths, and content does not contain layout or CSS decisions. Symfony Validator rejects incomplete or malformed content while it is loaded.

### Content structure

Each language owns a complete tree with stable, shared slugs:

```text
content/
├── de/
│   ├── profile.yaml
│   ├── experience/smartbroker/...
│   └── experience/quadriga-media/...
└── en/
    ├── profile.yaml
    ├── experience/smartbroker/...
    └── experience/quadriga-media/...
```

Example experience metadata:

```yaml
company: Smartbroker AG
role: Senior Backend-Entwickler
period:
  from: 2020-09
  to: null
summary: summary.md
responsibilities:
  - Architektur und Konzeption
technologies:
  - PHP
  - Symfony
  - RabbitMQ
projects:
  - title: Depot-Registrierung
    description: projects/depot-registrierung.md
    technologies: [Symfony, RabbitMQ]
```

YAML describes meaning; Markdown is reserved for prose. Raw HTML in Markdown is stripped and rendered output is passed through Symfony's HTML Sanitizer. To add a language, mirror an existing content tree and add the locale to `app.supported_locales`, the route requirement, and Symfony's enabled locales.

## Local installation

Requirements: PHP 8.5 with Ctype, DOM, and Iconv, Composer 2, and a POSIX-like shell or PowerShell. The `intl` extension is recommended.

```bash
composer install
php bin/console tailwind:build --watch
symfony server:start
```

Composer builds the production CSS and AssetMapper output during installation. The Tailwind watch command is only needed while changing templates or styles. Content changes are read immediately in `dev`.

Open `http://127.0.0.1:8000/de/` or `/en/`.

## Docker

Docker is the shortest path if PHP and Composer are not installed locally:

```bash
docker compose up --build
```

Open `http://localhost:8080/de/`. The local project is mounted read-only at `/app`,
while container-managed `vendor/` dependencies and writable `var/` runtime data live
in Docker volumes shared by the web and Tailwind services. A separate `tailwind`
service watches template and style changes. Changes to PHP, configuration, YAML,
Markdown, Twig, CSS, and JavaScript are therefore reflected without an image rebuild.
Generated host files under `public/assets/` are masked inside the web container so
that Symfony's development AssetMapper always serves the current Tailwind output.

After changing `composer.lock`, rebuild the image and recreate the development
volumes so that `/app/vendor` is initialized from the updated image:

```bash
docker compose down -v
docker compose up --build
```

Follow the Tailwind watcher output with:

```bash
docker compose logs -f tailwind
```

The multi-stage image compiles Tailwind and AssetMapper assets during the production
build. The final image contains only FrankenPHP, PHP, Symfony, static assets, and
content—no Node process or database. The local Compose setup reuses PHP's standalone
Tailwind binary in its additional watcher service.

## Tests and code quality

```bash
php bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
composer qa
```

Tests cover period invariants, safe localized lookup, Markdown handling, YAML mapping and validation, both locale trees, detail routes, unknown slugs, invalid locales, and equivalent language-switch URLs.

## Minikube

The manifests intentionally contain one Deployment, one Service, and one optional Ingress—enough to demonstrate local Kubernetes operation without a Helm chart.

```bash
minikube start
docker build -t cv-portfolio:local .
minikube image load cv-portfolio:local
kubectl create secret generic cv-portfolio --from-literal=app-secret="$(openssl rand -hex 32)"
kubectl apply -f k8s/deployment.yaml -f k8s/service.yaml
minikube service cv-portfolio --url
```

Open the printed URL and append `/de/`. For the Ingress variant:

```bash
minikube addons enable ingress
kubectl apply -f k8s/ingress.yaml
```

Point `cv.local` to the IP printed by `minikube ip`, then open `http://cv.local/de/`. On Windows PowerShell, create the secret with a locally generated value instead of the `openssl` substitution.

## Deployment with Deployer

Production is built locally in Docker and uploaded as an archive to the SSH alias
`strato`, under `.../htdocs/dennis-otto.net_cv`.
The public symlink `dennis-otto.net/cv` must point to `../dennis-otto.net_cv/current/public`.
The server needs PHP >= 8.5 for HTTP, STRATO's PHP 8.5 CLI, SSH and tar; no
server-side Git, Composer or asset build is used. Symfony's production cache is
warmed in the unpublished release before `current` is switched.

Build locally without contacting the server:

```powershell
docker compose -f compose.deploy.yaml run --build --rm build
```

See [the STRATO deployment guide](deploy/README.md) for temporary SSH credentials
inside Docker, local checks, the one-time migration of the existing Hallo test
folder, production settings, the later deployment command and rollback.

## Design decisions

- **No database:** content changes only with source deployments; files are simpler to review, translate, and version.
- **Separate locale trees:** translations produce clean diffs and can evolve independently while shared slugs keep equivalent URLs predictable.
- **Semantic components:** `ExperienceCard`, `ProjectCard`, `TechnologyBadge`, and `LanguageSwitch` express CV concepts rather than layout primitives.
- **Simple caching:** development favors instant feedback; production caches fully parsed, validated content objects.
- **Minimal JavaScript:** none is required for the initial experience. Navigation and language switching work with ordinary links.

The included content mirrors Dennis Otto's résumé in German and English. Keep both locale trees aligned when updating professional experience.
