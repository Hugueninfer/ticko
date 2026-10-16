#!/bin/sh
# Audits the runtime lockfile with pip-audit. pip-audit cannot look up a URL
# requirement, so torch (pinned by its CPU wheel URL and hash) is checked by
# its version instead; every other line is audited as locked.
set -eu
cd "$(dirname "$0")"
tmp=$(mktemp)
trap 'rm -f "$tmp"' EXIT
sed -E 's#^torch @ https://download\.pytorch\.org/whl/cpu/torch-([0-9.]+)%2Bcpu-[^ ]+\.whl#torch==\1#' requirements.lock > "$tmp"
grep -q '^torch==' "$tmp"
pip-audit --strict --require-hashes --disable-pip --progress-spinner off -r "$tmp"
