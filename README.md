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
│   ├── experience/northstar-digital/...
│   └── projects/cv-website/...
└── en/
    ├── profile.yaml
    ├── experience/northstar-digital/...
    └── projects/cv-website/...
```

Example experience metadata:

```yaml
company: Northstar Digital GmbH
role: Senior Software Engineer
period:
  from: 2022-08
  to: null
summary: summary.md
responsibilities:
  - Technical leadership for a business-critical platform
technologies:
  - PHP
  - Symfony
  - PostgreSQL
projects:
  - title: Partner platform
    description: projects/partner-platform.md
    technologies: [Symfony, PostgreSQL]
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

Open `http://localhost:8080/de/`. The local `content/` directory is mounted read-only, so YAML and Markdown edits are immediately reflected while the container runs in `dev`. Rebuild after changing PHP, Twig, or CSS.

The multi-stage image compiles Tailwind and AssetMapper assets during the build. The final image contains only FrankenPHP, PHP, Symfony, static assets, and content—no Node process or database.

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

The server needs SSH access, Git, PHP 8.5 with the required extensions, Composer 2, PHP-FPM, and nginx. Its layout defaults to:

```text
/var/www/cv/
├── current -> releases/...
├── releases/
└── shared/.env.local
```

Create `shared/.env.local` on the server with `APP_ENV=prod`, `APP_DEBUG=0`, a strong `APP_SECRET`, and the canonical `APP_PUBLIC_URL=https://cv.example.com` used by social metadata. Configure nginx using `deploy/nginx.conf.example`, then deploy from a machine with development dependencies installed:

```bash
export DEPLOY_HOST=cv.example.com
export DEPLOY_USER=deploy
export DEPLOY_PATH=/var/www/cv
export DEPLOY_REPOSITORY=git@github.com:your-user/cv-portfolio.git
vendor/bin/dep deploy
```

Optional `DEPLOY_IDENTITY_FILE` selects a dedicated SSH key. Do not commit secrets. Deployer creates an atomic release, installs optimized production dependencies, builds Tailwind and AssetMapper assets, warms Symfony's cache, updates the `current` symlink, and retains five releases. There is intentionally no CI/CD pipeline and no production Kubernetes dependency in this version.

## Design decisions

- **No database:** content changes only with source deployments; files are simpler to review, translate, and version.
- **Separate locale trees:** translations produce clean diffs and can evolve independently while shared slugs keep equivalent URLs predictable.
- **Semantic components:** `ExperienceCard`, `ProjectCard`, `TechnologyBadge`, and `LanguageSwitch` express CV concepts rather than layout primitives.
- **Simple caching:** development favors instant feedback; production caches fully parsed, validated content objects.
- **Minimal JavaScript:** none is required for the initial experience. Navigation and language switching work with ordinary links.

The included Alex Morgan content is realistic but intentionally generic. Replace names, links, YAML metadata, and Markdown copy in both locale trees before publishing.
