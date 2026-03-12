#!/usr/bin/env bash

set -euo pipefail

MAX_LENGTH=${MAX_LENGTH:-120}

TARGETS=(
  "app"
  "resources/views"
  "public/js"
)

EXIT_CODE=0

for target in "${TARGETS[@]}"; do
  if [ -d "$target" ]; then
    find "$target" -type f \( -name '*.php' -o -name '*.twig' -o -name '*.js' \) -print0 | \
      xargs -0 -I{} awk -v max="$MAX_LENGTH" 'length($0) > max { print FILENAME ":" NR ":" $0; exit_code=1 } END { if (exit_code) exit 1 }' "{}" || EXIT_CODE=1
  fi
done

if [ "$EXIT_CODE" -ne 0 ]; then
  echo "Found lines longer than ${MAX_LENGTH} characters."
fi

exit "$EXIT_CODE"
