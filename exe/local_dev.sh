#!/usr/bin/env bash
set -euo pipefail

echo "Setting up local dev environment..."

#Download test XML if needed
./exe/fetch_test_xml.sh

#run docker compose
docker compose -f docker-compose.yml -f docker-compose.dev.override.yml up --build -d