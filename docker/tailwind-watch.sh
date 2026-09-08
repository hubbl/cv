#!/bin/sh

set -u

cd /app

poll_interval="${TAILWIND_POLL_INTERVAL:-0.5}"
previous_fingerprint=""

fingerprint_sources() {
    find assets/styles templates src -type f \
        \( -name '*.css' -o -name '*.twig' -o -name '*.php' \) \
        -exec sha256sum '{}' \; 2>/dev/null \
        | sort \
        | sha256sum \
        | cut -d ' ' -f 1
}

echo "Tailwind polling watcher started (interval: ${poll_interval}s)."

while true; do
    current_fingerprint="$(fingerprint_sources)"

    if [ "$current_fingerprint" != "$previous_fingerprint" ]; then
        previous_fingerprint="$current_fingerprint"
        echo "Tailwind sources changed; rebuilding..."

        if php bin/console tailwind:build; then
            # AssetMapper can cache the previous Tailwind output when a request
            # arrives during the build. Its generated files are not removed by
            # cache:pool:clear, so invalidate this dedicated dev cache directly.
            if [ -d var/cache/dev/asset_mapper ]; then
                if find var/cache/dev/asset_mapper -type f -delete; then
                    echo "AssetMapper cache invalidated."
                else
                    echo "Tailwind built, but the AssetMapper cache could not be invalidated." >&2
                fi
            fi
        else
            echo "Tailwind build failed; waiting for the next source change." >&2
        fi
    fi

    sleep "$poll_interval"
done
