#!/usr/bin/env bash
set -euo pipefail

if [ ! -f vendor/bin/phpunit ]; then
  echo "phpunit not installed. Install dev dependencies with: composer install --dev"
  exit 2
fi

echo "Running PHPUnit tests..."
vendor/bin/phpunit --configuration phpunit.xml
