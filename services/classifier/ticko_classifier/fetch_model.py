"""Downloads the base model (BERTimbau base) at a pinned revision and checks
every file against a pinned SHA-256 before keeping it.

Only safetensors weights are fetched: no pickle (pytorch_model.bin) is ever
downloaded or loaded. The revision is the Hugging Face commit that added
the safetensors conversion (refs/pr/5); the weights' hash is the one the
Hub publishes for that file (X-Linked-ETag).
"""

from __future__ import annotations

import argparse
import hashlib
import os
import sys
import tempfile
import urllib.request
from pathlib import Path

REPO = "neuralmind/bert-base-portuguese-cased"
REVISION = "4a78cfbf83c9c97533dd6d6694ca4323029ff061"
FILES: dict[str, str] = {
    "config.json": "5d8e34a2272a548e9c346133253ac20453b0d1ad8d3190d6ba89252c7c544357",
    "vocab.txt": "69c28584c67a0e5018f85ca734aa272cc38e26b5dd0d33fffa28059299f21707",
    "tokenizer_config.json": "5abeb5276424d64097abc25b16c149da7c0fdf0594253c34fd899321db4913ea",
    "special_tokens_map.json": "303df45a03609e4ead04bc3dc1536d0ab19b5358db685b6f3da123d05ec200e3",
    "added_tokens.json": "44136fa355b3678a1146ad16f7e8649e94fb4fc21fe77e8310c060f61caaff8a",
    "model.safetensors": "0dd6935d91a3833b00b4689a9fbf39fcb6328ab122bfd70a334bffadf22fb4e5",
}
DEFAULT_DIR = Path(os.environ.get("TICKO_BASE_MODEL_DIR", "/opt/models/bertimbau-base"))


def sha256(path: Path) -> str:
    digest = hashlib.sha256()
    with path.open("rb") as handle:
        for block in iter(lambda: handle.read(1 << 20), b""):
            digest.update(block)
    return digest.hexdigest()


def verify(directory: Path) -> bool:
    """True when every pinned file is present with the expected hash."""
    return all((directory / name).is_file() and sha256(directory / name) == expected for name, expected in FILES.items())


def fetch(directory: Path) -> None:
    directory.mkdir(parents=True, exist_ok=True)

    for name, expected in FILES.items():
        target = directory / name
        if target.is_file() and sha256(target) == expected:
            continue

        url = f"https://huggingface.co/{REPO}/resolve/{REVISION}/{name}"
        with tempfile.NamedTemporaryFile(dir=directory, delete=False) as tmp:
            tmp_path = Path(tmp.name)
            with urllib.request.urlopen(url, timeout=60) as response:
                while block := response.read(1 << 20):
                    tmp.write(block)

        actual = sha256(tmp_path)
        if actual != expected:
            tmp_path.unlink(missing_ok=True)
            raise SystemExit(f"Checksum mismatch for {name}: expected {expected}, got {actual}. Refusing to use it.")

        tmp_path.chmod(0o644)  # temp files are private; the service user must read the model
        tmp_path.replace(target)
        print(f"ok {name}")


def main() -> None:
    parser = argparse.ArgumentParser(description="Fetch and verify the pinned base model.")
    parser.add_argument("--dir", type=Path, default=DEFAULT_DIR)
    parser.add_argument("--verify-only", action="store_true")
    args = parser.parse_args()

    if args.verify_only:
        sys.exit(0 if verify(args.dir) else 1)

    fetch(args.dir)


if __name__ == "__main__":
    main()
