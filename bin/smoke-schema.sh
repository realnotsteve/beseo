#!/usr/bin/env bash
set -euo pipefail
ROOT_DIR="$(cd "$(dirname "$0")/.." && pwd)"

php -l "$ROOT_DIR/beseo.php"
php -l "$ROOT_DIR/includes/admin/page-schema.php"
php -l "$ROOT_DIR/includes/admin/schema-view.php"
php -l "$ROOT_DIR/includes/admin/schema-view-website.php"
php -l "$ROOT_DIR/includes/admin/schema-service.php"

if command -v wp >/dev/null 2>&1; then
  WP_DEBUG=false wp eval 'echo "WP bootstrap OK" . "\n";'
fi
