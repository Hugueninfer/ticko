"""Baseline: character n-gram TF-IDF + logistic regression, one per task.

Trained with scikit-learn, exported WITHOUT pickle: the vocabulary goes to
JSON and the arrays (idf, coefficients, intercepts) to safetensors. The
service predicts with plain numpy, reproducing scikit-learn's transform
exactly (a test checks both give the same probabilities).
"""

from __future__ import annotations

import argparse
import json
import math
import re
import time
import unicodedata
from collections import Counter
from pathlib import Path

import numpy as np
from safetensors.numpy import load_file, save_file

from .labels import TASKS
from .sanitize import prepare

NGRAM_RANGE = (2, 5)
MAX_FEATURES = 40_000
_WHITESPACE = re.compile(r"\s\s+")


def _preprocess(text: str) -> str:
    # Same order as scikit-learn: lowercase, then strip accents ("unicode").
    lowered = text.lower()
    return "".join(c for c in unicodedata.normalize("NFKD", lowered) if not unicodedata.combining(c))


def _char_wb_ngrams(text: str) -> list[str]:
    """scikit-learn's char_wb analyzer (sklearn.feature_extraction.text)."""
    text = _WHITESPACE.sub(" ", text)
    min_n, max_n = NGRAM_RANGE
    ngrams: list[str] = []
    for word in text.split():
        w = f" {word} "
        length = len(w)
        for n in range(min_n, max_n + 1):
            offset = 0
            ngrams.append(w[offset : offset + n])
            while offset + n < length:
                offset += 1
                ngrams.append(w[offset : offset + n])
            if offset == 0:
                break
    return ngrams


class BaselineModel:
    """Numpy-only predictor loaded from a safetensors + JSON export."""

    name = "baseline-tfidf-logreg"

    def __init__(self, directory: Path) -> None:
        meta = json.loads((directory / "model.json").read_text(encoding="utf-8"))
        arrays = load_file(str(directory / "weights.safetensors"))
        self.vocabulary: dict[str, int] = meta["vocabulary"]
        self.labels: dict[str, list[str]] = meta["labels"]
        self.version: str = meta["version"]
        self.idf = arrays["idf"]
        self.coef = {task: arrays[f"{task}.coef"] for task in TASKS}
        self.intercept = {task: arrays[f"{task}.intercept"] for task in TASKS}

    def _vector(self, text: str) -> np.ndarray:
        counts = Counter(i for gram in _char_wb_ngrams(_preprocess(text)) if (i := self.vocabulary.get(gram)) is not None)
        vector = np.zeros(len(self.idf), dtype=np.float64)
        for index, count in counts.items():
            vector[index] = (1.0 + math.log(count)) * self.idf[index]
        norm = np.linalg.norm(vector)
        return vector / norm if norm > 0 else vector

    def predict_proba(self, texts: list[str]) -> list[dict[str, np.ndarray]]:
        matrix = np.vstack([self._vector(prepare(text).text) for text in texts]) if texts else np.zeros((0, len(self.idf)))
        results: list[dict[str, np.ndarray]] = [{} for _ in texts]
        for task in TASKS:
            logits = matrix @ self.coef[task].T + self.intercept[task]
            logits -= logits.max(axis=1, keepdims=True)
            probs = np.exp(logits)
            probs /= probs.sum(axis=1, keepdims=True)
            for row, p in zip(results, probs, strict=True):
                row[task] = p
        return results

    def predict(self, texts: list[str]) -> list[dict[str, tuple[str, float]]]:
        return [
            {task: (self.labels[task][int(p.argmax())], float(p.max())) for task, p in row.items()} for row in self.predict_proba(texts)
        ]


def train(rows: list[dict[str, str]], out: Path, version: str) -> dict[str, object]:
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.linear_model import LogisticRegression

    started = time.perf_counter()
    texts = [prepare(row["text"]).text for row in rows]
    vectorizer = TfidfVectorizer(
        analyzer="char_wb",
        ngram_range=NGRAM_RANGE,
        lowercase=True,
        strip_accents="unicode",
        sublinear_tf=True,
        max_features=MAX_FEATURES,
        dtype=np.float64,
    )
    matrix = vectorizer.fit_transform(texts)

    arrays: dict[str, np.ndarray] = {"idf": vectorizer.idf_.astype(np.float64)}
    labels: dict[str, list[str]] = {}
    for task, classes in TASKS.items():
        y = [classes.index(row[task]) for row in rows]
        model = LogisticRegression(max_iter=2000, C=4.0, class_weight="balanced")
        model.fit(matrix, y)
        # Coefficients in taxonomy order, even if a class were missing.
        coef = np.zeros((len(classes), matrix.shape[1]))
        intercept = np.full(len(classes), -1e9)
        for row_index, class_index in enumerate(model.classes_):
            coef[class_index] = model.coef_[row_index]
            intercept[class_index] = model.intercept_[row_index]
        arrays[f"{task}.coef"] = coef
        arrays[f"{task}.intercept"] = intercept
        labels[task] = list(classes)

    out.mkdir(parents=True, exist_ok=True)
    save_file(arrays, str(out / "weights.safetensors"))
    vocabulary = {gram: int(index) for gram, index in vectorizer.vocabulary_.items()}
    info = {"version": version, "training_seconds": round(time.perf_counter() - started, 1), "examples": len(rows)}
    (out / "model.json").write_text(
        json.dumps({"version": version, "labels": labels, "vocabulary": vocabulary, "training": info}, ensure_ascii=False, sort_keys=True),
        encoding="utf-8",
    )
    return info


def main() -> None:
    from .dataset import read

    root = Path(__file__).resolve().parent.parent
    parser = argparse.ArgumentParser(description="Train the TF-IDF + logistic regression baseline.")
    parser.add_argument("--data", type=Path, default=root / "data")
    parser.add_argument("--out", type=Path, default=root / "models" / "baseline-v1")
    args = parser.parse_args()
    info = train(read(args.data / "train.jsonl"), args.out, version="baseline-v1")
    print(json.dumps(info))


if __name__ == "__main__":
    main()
