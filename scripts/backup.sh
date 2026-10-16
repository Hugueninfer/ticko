#!/bin/sh
# Encrypted database backup: pg_dump (custom format) piped straight into
# AES-256-CBC with PBKDF2 (600k iterations) and the BACKUP_PASSPHRASE from
# .env; the plain dump never touches the disk. Writes
#   backups/ticko-<UTC timestamp>.dump.enc  (+ .sha256)
# Keep the passphrase somewhere else than the backups (a password manager).
# Usage: scripts/backup.sh [compose args...]   e.g. scripts/backup.sh -p ticko
set -eu
cd "$(dirname "$0")/.."

env_value() { grep -m1 "^$1=" .env | cut -d= -f2-; }
passphrase=$(env_value BACKUP_PASSPHRASE)
[ -n "$passphrase" ] || { echo "BACKUP_PASSPHRASE is missing in .env (run make env)." >&2; exit 1; }
superuser=$(env_value POSTGRES_SUPERUSER); superuser=${superuser:-postgres}
database=$(env_value DB_DATABASE); database=${database:-ticko}

mkdir -p backups
chmod 700 backups
umask 077
file="backups/ticko-$(date -u +%Y%m%dT%H%M%SZ).dump.enc"

docker compose "$@" exec -T postgres pg_dump --username "$superuser" --format custom --compress 6 "$database" \
  | BACKUP_PASSPHRASE="$passphrase" openssl enc -aes-256-cbc -pbkdf2 -iter 600000 -salt -pass env:BACKUP_PASSPHRASE -out "$file"

sha256sum "$file" > "$file.sha256"
echo "$file"
