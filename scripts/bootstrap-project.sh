#!/usr/bin/env bash
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TEMP_ROOT="$(mktemp -d)"
trap 'rm -rf "${TEMP_ROOT}"' EXIT

cd "${TEMP_ROOT}"
composer create-project laravel/laravel scaffold

cd "${PROJECT_ROOT}"

echo "Laravel was downloaded to: ${TEMP_ROOT}/scaffold"
echo "Merge the verified scaffold into ${PROJECT_ROOT} while preserving docs and AGENTS.md."
echo "The automated merge will be implemented after the environment setup succeeds."

