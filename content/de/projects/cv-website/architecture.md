**YAML** enthält Struktur und Metadaten. **Markdown** trägt redaktionelle Texte. Eine Content-Schicht lädt beides in unveränderliche PHP-Objekte und validiert Pflichtfelder früh. Erst danach entscheiden semantische Twig Components über die Darstellung.

In Entwicklung werden Änderungen direkt gelesen; in Produktion werden die vollständig aufgebauten Content-Objekte im Symfony Cache gehalten.
