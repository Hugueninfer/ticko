#!/bin/sh
# Resolves requirements*.in into hash-pinned lockfiles for Python 3.12 on
# Linux x86_64 (the service image and CI). torch is referenced by the exact
# CPU wheel URL; everything else comes from PyPI; hashes pin every file.
set -eu
cd "$(dirname "$0")"
for name in requirements requirements-dev; do
  uv pip compile "$name.in" -o "$name.lock" \
    --python-version 3.12 --python-platform x86_64-manylinux_2_28 \
    --generate-hashes --emit-index-url --no-header \
    --index-url https://pypi.org/simple
done
