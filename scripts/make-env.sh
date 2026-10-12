#!/bin/sh
# Creates .env from .env.example with freshly generated secrets.
# Refuses to overwrite an existing .env.
set -eu

cd "$(dirname "$0")/.."

if [ -f .env ]; then
  echo ".env already exists; leaving it untouched." >&2
  exit 0
fi

secret() { head -c 48 /dev/urandom | base64 | tr -d '/+=\n' | head -c 40; }

app_key="base64:$(head -c 32 /dev/urandom | base64)"

sed \
  -e "s|^APP_KEY=.*|APP_KEY=${app_key}|" \
  -e "s|^DB_PASSWORD=.*|DB_PASSWORD=$(secret)|" \
  -e "s|^POSTGRES_SUPERUSER_PASSWORD=.*|POSTGRES_SUPERUSER_PASSWORD=$(secret)|" \
  -e "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=$(secret)|" \
  .env.example > .env

{
  echo "UID=$(id -u)"
  echo "GID=$(id -g)"
} >> .env

chmod 600 .env
echo "Created .env with generated secrets."
