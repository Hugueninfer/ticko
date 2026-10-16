# Postgres 17 + pgvector, hardened:
#  - the pinned upstream image (Debian 12, same glibc and collations as before),
#  - with the Debian security updates applied at build time,
#  - without gosu: the container runs as the postgres user from the start
#    (compose.yaml), so the entrypoint never needs to drop privileges.
ARG POSTGRES_IMAGE=pgvector/pgvector:0.8.7-pg17-bookworm@sha256:ac08538c6f8b9904c33c8224c5e5706dbe760aca29db1d096972b4052c22a75d
FROM ${POSTGRES_IMAGE}
RUN apt-get update \
    && apt-get -y --no-install-recommends upgrade \
    && rm -rf /var/lib/apt/lists/* /usr/local/bin/gosu
USER postgres
