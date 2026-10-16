#!/bin/sh
# Proves that backups can be restored: makes an encrypted backup, restores it
# into a scratch database and compares the row count of every table (as the
# superuser, so Row-Level Security does not hide rows), then drops it.
# Usage: scripts/backup-verify.sh [compose args...]
set -eu
cd "$(dirname "$0")/.."

env_value() { grep -m1 "^$1=" .env | cut -d= -f2-; }
superuser=$(env_value POSTGRES_SUPERUSER); superuser=${superuser:-postgres}
database=$(env_value DB_DATABASE); database=${database:-ticko}
scratch=ticko_restore_check

file=$(scripts/backup.sh "$@")

# Encrypted at rest: OpenSSL's salted header, and no readable demo data.
[ "$(head -c 8 "$file")" = "Salted__" ] || { echo "Backup is not encrypted." >&2; exit 1; }
if grep -q "acme.test" "$file"; then echo "Backup contains plain text." >&2; exit 1; fi
scripts/restore.sh "$file" --into "$scratch" -- "$@" >/dev/null

counts() {
  db=$1; shift
  docker compose "$@" exec -T postgres psql --username "$superuser" --dbname "$db" -At -c "
    SELECT string_agg(format('%s=%s', table_name,
      (xpath('/row/c/text()', query_to_xml(format('SELECT count(*) AS c FROM public.%I', table_name), false, true, '')))[1]::text), ' ' ORDER BY table_name)
    FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'"
}

original=$(counts "$database" "$@")
restored=$(counts "$scratch" "$@")
docker compose "$@" exec -T postgres psql --username "$superuser" --dbname postgres --quiet -c "DROP DATABASE \"$scratch\" WITH (FORCE)"

if [ "$original" != "$restored" ]; then
  echo "Restore check FAILED: row counts differ." >&2
  echo "original: $original" >&2
  echo "restored: $restored" >&2
  exit 1
fi

tables=$(echo "$original" | wc -w)
echo "Restore check passed: $tables tables, identical row counts ($file)."
