#!/bin/sh
# Restores an encrypted backup made by scripts/backup.sh.
#
#   scripts/restore.sh <file.dump.enc> --into <database>   restore into a new or scratch database
#   scripts/restore.sh <file.dump.enc> --replace --yes      replace the application database
#
# The checksum is verified and the file decrypted in a pipe (no plain dump on
# disk). Any extra arguments after `--` go to docker compose (e.g. -- -p ticko).
set -eu
cd "$(dirname "$0")/.."

file=${1:?usage: scripts/restore.sh <file.dump.enc> (--into <db> | --replace --yes) [-- compose args]}
shift
target=""; replace=false; confirmed=false
while [ $# -gt 0 ]; do
  case "$1" in
    --into) target=$2; shift 2 ;;
    --replace) replace=true; shift ;;
    --yes) confirmed=true; shift ;;
    --) shift; break ;;
    *) echo "unknown option $1" >&2; exit 2 ;;
  esac
done

env_value() { grep -m1 "^$1=" .env | cut -d= -f2-; }
passphrase=$(env_value BACKUP_PASSPHRASE)
superuser=$(env_value POSTGRES_SUPERUSER); superuser=${superuser:-postgres}
app_user=$(env_value DB_USERNAME); app_user=${app_user:-ticko_app}
database=$(env_value DB_DATABASE); database=${database:-ticko}

[ -n "$passphrase" ] || { echo "BACKUP_PASSPHRASE is missing in .env." >&2; exit 1; }
[ -f "$file.sha256" ] && sha256sum --check --quiet "$file.sha256"

if $replace; then
  $confirmed || { echo "Replacing the application database needs --yes (stop the api and queue first)." >&2; exit 1; }
  target=$database
fi
[ -n "$target" ] || { echo "Choose --into <database> or --replace --yes." >&2; exit 1; }

if ! $replace; then
  echo "DROP DATABASE IF EXISTS \"$target\" WITH (FORCE); CREATE DATABASE \"$target\" OWNER \"$app_user\";" \
    | docker compose "$@" exec -T postgres psql --username "$superuser" --dbname postgres -v ON_ERROR_STOP=1 --quiet
fi

BACKUP_PASSPHRASE="$passphrase" openssl enc -d -aes-256-cbc -pbkdf2 -iter 600000 -pass env:BACKUP_PASSPHRASE -in "$file" \
  | docker compose "$@" exec -T postgres pg_restore --username "$superuser" --dbname "$target" --clean --if-exists --exit-on-error
echo "Restored $file into $target."
