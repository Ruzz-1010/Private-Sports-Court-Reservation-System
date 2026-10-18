#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -eq 0 ]]; then
  APT=(apt-get)
else
  APT=(sudo apt-get)
fi

echo "Updating package index..."
"${APT[@]}" update

echo "Installing PHP, Composer, PostgreSQL, and Laravel system dependencies..."
"${APT[@]}" install -y \
  php-cli \
  php-bcmath \
  php-curl \
  php-gd \
  php-intl \
  php-mbstring \
  php-pgsql \
  php-sqlite3 \
  php-xml \
  php-zip \
  composer \
  postgresql \
  postgresql-contrib \
  unzip \
  curl \
  ca-certificates

echo "Environment versions:"
php --version
composer --version
node --version
npm --version
psql --version

echo "Environment installation completed."

