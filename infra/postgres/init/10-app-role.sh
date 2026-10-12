#!/bin/sh
# Runs once, when the Postgres volume is first initialised.
#
# Creates the role the application connects with. It owns the schema (so it
# can run migrations) but is NOT a superuser and does NOT have BYPASSRLS,
# so the Row-Level Security policies (FORCEd on every tenant table) always
# apply to it. The superuser from POSTGRES_USER is only used here.
set -eu

: "${APP_DB_USER:?APP_DB_USER is required}"
: "${APP_DB_PASSWORD:?APP_DB_PASSWORD is required}"

create_db() {
  db="$1"
  psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres \
    -v db="$db" -v app_user="$APP_DB_USER" <<'SQL'
SELECT format('CREATE DATABASE %I OWNER %I', :'db', :'app_user')
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = :'db') \gexec
SQL
  psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname "$db" -v app_user="$APP_DB_USER" <<'SQL'
ALTER SCHEMA public OWNER TO :"app_user";
REVOKE ALL ON SCHEMA public FROM PUBLIC;
CREATE EXTENSION IF NOT EXISTS vector;
SQL
}

psql -v ON_ERROR_STOP=1 --username "$POSTGRES_USER" --dbname postgres \
  -v app_user="$APP_DB_USER" -v app_password="$APP_DB_PASSWORD" <<'SQL'
CREATE ROLE :"app_user" LOGIN PASSWORD :'app_password'
  NOSUPERUSER NOCREATEDB NOCREATEROLE NOREPLICATION NOBYPASSRLS;
SQL

create_db "${APP_DB_NAME:-ticko}"
# Separate database for the test suite (used by `make test`).
create_db "${APP_DB_NAME:-ticko}_testing"
