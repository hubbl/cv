# STRATO: lokaler Build und Artefakt-Deployment

Der Build läuft ausschließlich lokal in Docker. Der Zielserver benötigt weder
Git noch Composer, Node oder rsync. Er benötigt SSH mit einer POSIX-Shell,
`tar` mit gzip-Unterstützung, **PHP >= 8.5** für HTTP sowie STRATOs echte PHP-CLI
unter `/opt/RZphp85/bin/php-cli`. Ctype, DOM, Iconv und die übrigen Composer-
Plattformanforderungen müssen verfügbar sein; Intl wird empfohlen. Die im
STRATO-Kundenbereich gewählte Web-PHP-Version und die CLI-Version müssen passen.
Apache muss `.htaccess`, mod_rewrite und die bestehenden Symlinks unterstützen.

## Verzeichnisstruktur

```text
/mnt/web024/d3/82/51918182/htdocs/
├── dennis-otto.net/
│   └── cv -> ../dennis-otto.net_cv/current/public
└── dennis-otto.net_cv/
    ├── .dep/
    ├── current -> releases/1
    ├── releases/
    │   └── 1/             # Anwendung, vendor/, public/assets/ und warmer var/cache/prod/
    └── shared/
        ├── .env.local
        └── var/log/
```

Deployer behält fünf Releases. Der öffentliche Link bleibt bei weiteren Deployments
unverändert; Deployer schaltet nur `current` um. Die Anwendung ist danach unter
`https://dennis-otto.net/cv/de/` bzw. `/cv/en/` erreichbar.

## 1. Lokal bauen – ohne Serverzugriff

Docker Desktop mit Linux-Containern starten. Aus dem Projektverzeichnis:

```powershell
docker compose -f compose.deploy.yaml run --build --rm build
```

Ergebnis: `.deploy/release.tar.gz` und `.deploy/release.tar.gz.sha256`.
Das Archiv enthält den aktuellen lokalen Dateistand (auch uncommittete Änderungen),
Produktionsabhängigkeiten aus `composer.lock` und kompilierte Tailwind-/AssetMapper-
Assets. Es enthält keine SSH-Schlüssel, lokalen `.env.local`-Dateien, Tests oder
Entwicklungsabhängigkeiten. Jeder neue Stand benötigt diesen Build-Aufruf.
Der Build benötigt Internet für Images, Composer-Pakete und das Tailwind-Binary,
verbindet sich aber nie mit STRATO. Der Export-Container hat kein Netzwerk.
Vor dem Export wird das Archiv in einen anderen Dateipfad entpackt und dort mit
Produktionskonfiguration geprüft: Deutsch, Englisch, eine Detailseite, kompilierte
Assets und die Navigation unter `/cv`. Die Compose-Datei verwendet einen eigenen
Projektnamen und beeinflusst den bestehenden lokalen App-Container nicht.

Symfony-Cache wird absichtlich **nicht lokal übertragen**: Er enthält absolute Pfade.
Der Build prüft den Container, entfernt anschließend `var/` inklusive Build-Cache,
Logs und Tailwind-Binary und legt leere Laufzeitverzeichnisse an. Während des
Deployments führt STRATOs PHP-CLI im noch unveröffentlichten Release
`cache:clear --env=prod --no-debug` aus. Symfony leert und wärmt damit den Cache am
richtigen Serverpfad. Erst nach erfolgreichem Warmup wird `current` umgeschaltet.
Bei einem Fehler bleibt das bisherige Release aktiv. Die Web-PHP-Instanz muss als
Hosting-Benutzer auf `var/` schreiben können; es werden weder sudo noch ACLs benötigt.

## 2. SSH im kurzlebigen Deployer-Container

Unter Windows wird standardmäßig `$env:USERPROFILE/.ssh` schreibgeschützt eingebunden.
Für einen anderen Pfad bzw. Linux/macOS vor den Compose-Befehlen `SSH_DIRECTORY`
setzen (PowerShell: `$env:SSH_DIRECTORY = 'C:/Pfad/.ssh'`; POSIX:
`export SSH_DIRECTORY="$HOME/.ssh"`).

Vor einer Serveraktion kopiert der Entrypoint Config, Includes, bekannte Hostschlüssel
und Schlüssel in `/root/.ssh`, ein ausschließlich im Arbeitsspeicher liegendes
`tmpfs`. Dateien erhalten Modus 600, Verzeichnisse 700. Windows-Pfade innerhalb
dieses SSH-Verzeichnisses werden in `IdentityFile`, `CertificateFile`,
`UserKnownHostsFile` und `Include` auf den Containerpfad umgeschrieben. Die Originale
bleiben unverändert. `--rm` entfernt den Container nach dem Aufruf.

Der Alias **strato** liefert Hostname, Benutzer, Port und Schlüssel. `DEPLOY_USER`
kann den Benutzer optional überschreiben. Der Server-Hostschlüssel muss bereits
in `known_hosts` stehen; Hostkey-Prüfung bleibt aktiviert. Agent-Forwarding zum
Server ist ausgeschaltet, weil dort kein Git-Zugriff nötig ist.
Deployer legt seinen Multiplexing-Socket unter `/tmp/deployer-ssh-%C` ab.
Sein Standardpfad `~/.ssh/strato` würde hier mit dem gleichnamigen privaten
Schlüssel kollidieren und die temporäre Schlüsselkopie beim Verbindungsaufbau
entfernen. Deshalb muss der Socketpfad getrennt von den Schlüsseldateien bleiben.

Schlüssel/Includes außerhalb des eingebundenen SSH-Verzeichnisses, Windows-spezifische
ProxyCommands, Hardware-Schlüssel und ein ausschließlich im Windows-Agenten
vorhandener Schlüssel funktionieren mit dieser Dateikopie nicht automatisch.
Dafür ein separates SSH-Verzeichnis mit passender Config und benötigten Dateien
über `SSH_DIRECTORY` bereitstellen. Bei passwortgeschützten Schlüsseln kann SSH im
interaktiven Container nach der Passphrase fragen; `docker compose run` dafür ohne
`-T` ausführen. Es wird kein Schlüssel in das Image eingebaut.

Konfiguration lokal inspizieren (keine SSH-Verbindung):

```powershell
docker compose -f compose.deploy.yaml build deployer
docker compose -f compose.deploy.yaml run --rm deployer list
docker compose -f compose.deploy.yaml run --rm deployer tree deploy
docker compose -f compose.deploy.yaml run --rm deployer artifact:check
```

## 3. Einmalige Vorbereitung auf STRATO – noch nicht ausgeführt

1. Im STRATO-Kundenbereich PHP >= 8.5 für diese Website einstellen und erforderliche
   Extensions sicherstellen. Der Preflight prüft zusätzlich STRATOs CLI unter
   `/opt/RZphp85/bin/php-cli`; die Web-PHP-Konfiguration kann er nicht prüfen.
2. Unter `dennis-otto.net_cv/shared/` eine `.env.local` entsprechend
   `deploy/production.env.example` anlegen. Einen echten zufälligen `APP_SECRET`
   einsetzen, Modus 600 setzen. `APP_PUBLIC_URL` und `DEFAULT_URI` enthalten `/cv`.
3. Falls `dennis-otto.net_cv/current` derzeit ein **echtes Verzeichnis** ist, dieses
   mitsamt `index.html` beispielsweise nach `current-hallo-backup` umbenennen.
   Deployer überschreibt/löscht das Testverzeichnis nicht und bricht andernfalls ab.
4. Den bestehenden Symlink `dennis-otto.net/cv` auf
   `../dennis-otto.net_cv/current/public` ändern. Zuvor prüfen, dass `cv` tatsächlich
   ein Symlink ist. Beim ersten Deployment kann der neue Link kurzzeitig ins Leere
   zeigen; die Vorbereitung daher unmittelbar vor dem ersten Deployment durchführen.

Das Release enthält im Wurzelverzeichnis einen Apache-Zugriffsschutz und unter
`public/` die Rewrite-Regeln. Nur `public/` darf öffentlich erreichbar sein.
Die Regeln leiten Symfony-Routen unter `/cv` an `index.php` weiter und liefern
vorhandene Assets direkt aus. Die Symfony-URL-Erzeugung berücksichtigt den
Request-Unterpfad; kein festes `/cv` wird in allgemeine Templates eingebaut.
Die Domain selbst kann weiterhin für eine andere Anwendung genutzt werden.

## 4. Später ausdrücklich deployen

**Die folgenden Befehle verändern STRATO. Sie wurden beim Einrichten nicht ausgeführt.**

```powershell
docker compose -f compose.deploy.yaml run --rm deployer deploy
```

Deployer prüft zuerst das lokale Archiv samt SHA-256 und die Servervoraussetzungen.
Danach: neues Release anlegen, Archiv über SSH direkt mit `tar` entpacken,
`.env.local` und Logs verlinken, Schreibrechte setzen, den Symfony-Produktionscache
im neuen Release erzeugen, `current` umschalten und alte Releases aufräumen. Es werden
keine Composer-, Git- oder Asset-Build-Befehle auf STRATO ausgeführt. Der Quellstand
wird über den Artefakt-Hash in `REVISION` dokumentiert.

Danach `/cv/de/`, `/cv/en/`, eine Detailseite sowie CSS/JavaScript prüfen.
Ein HTTP-Test gegen die echte Domain ist kein Bestandteil des lokalen Builds.

```powershell
docker compose -f compose.deploy.yaml run --rm deployer releases
docker compose -f compose.deploy.yaml run --rm deployer rollback
```

Rollback schaltet `current` auf das vorherige erhaltene Release; beim allerersten
Deployment gibt es noch keines. Das Hallo-Backup gehört nicht zur Release-Historie.
Bei Prozessabbruch kann ein Lock übrig bleiben. Erst sicherstellen, dass kein
Deployment mehr läuft, dann bei Bedarf `deployer deploy:unlock` ausführen.
