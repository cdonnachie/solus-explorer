#!/bin/bash
# Prints the SRI value (sha384) for an integrity="" attribute, after a bundled
# library is replaced. Usage: ./integrity.sh [file ...]  (default: every file in public/js)
cd "$(dirname "$0")" || exit 1
[ $# -eq 0 ] && set -- public/js/*.js public/js/addons/*.js
for f in "$@"; do
    [ -f "$f" ] || { echo "not found: $f" >&2; continue; }
    printf '%s integrity="sha384-%s"\n' "$f" "$(openssl dgst -sha384 -binary "$f" | openssl base64 -A)"
done
