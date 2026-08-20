**YAML** holds structure and metadata. **Markdown** carries editorial text. A content layer loads both into immutable PHP objects and validates required fields early. Only then do semantic Twig Components decide how the information is presented.

Development reads changes directly; production keeps fully constructed content objects in Symfony's cache.
