#!/bin/sh
# Creates .env from .env.example with freshly generated secrets.
# On an existing .env, only appends the settings it is missing (generating
# their secrets); values already there are never changed.
set -eu

cd "$(dirname "$0")/.."

secret() { head -c 48 /dev/urandom | base64 | tr -d '/+=\n' | head -c 40; }

# Value for a key of .env.example: a fresh secret where the example leaves it blank.
value_for() {
  case "$1" in
    APP_KEY) echo "base64:$(head -c 32 /dev/urandom | base64)" ;;
    DB_PASSWORD | POSTGRES_SUPERUSER_PASSWORD | REDIS_PASSWORD | REVERB_APP_SECRET | CLASSIFIER_TOKEN) secret ;;
    REVERB_APP_KEY) secret | tr '[:upper:]' '[:lower:]' | head -c 20 ;;
    *) grep -m1 "^$1=" .env.example | cut -d= -f2- ;;
  esac
}

if [ -f .env ]; then
  added=""
  for key in $(grep -oE '^[A-Z0-9_]+=' .env.example | tr -d '='); do
    if ! grep -q "^${key}=" .env; then
      echo "${key}=$(value_for "$key")" >> .env
      added="${added} ${key}"
    fi
  done
  [ -n "$added" ] && echo "Added to .env:${added}" >&2
  exit 0
fi

: > .env
chmod 600 .env
while IFS= read -r line; do
  case "$line" in
    [A-Z]*=*)
      key=${line%%=*}
      if [ -z "${line#*=}" ]; then
        echo "${key}=$(value_for "$key")" >> .env
      else
        echo "$line" >> .env
      fi
      ;;
    *) echo "$line" >> .env ;;
  esac
done < .env.example

{
  echo "UID=$(id -u)"
  echo "GID=$(id -g)"
} >> .env

echo "Created .env with generated secrets."
