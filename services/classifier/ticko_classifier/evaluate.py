"""Evaluation: per-class precision/recall/F1, macro-F1 and confusion matrices
on the held-out test split and the hand-written challenge set, plus the
prompt-injection eval (labels must not change when an attack is appended).

`python -m ticko_classifier.evaluate --model baseline|lora --check` exits
non-zero when a metric falls below the thresholds in thresholds.json; CI
runs it on every change.
"""

from __future__ import annotations

import argparse
import json
import sys
import time
from collections.abc import Callable
from pathlib import Path

from .dataset import read
from .labels import TASKS

ROOT = Path(__file__).resolve().parent.parent
Predictor = Callable[[list[str]], list[dict[str, tuple[str, float]]]]


def metrics(gold: list[str], predicted: list[str], classes: tuple[str, ...]) -> dict[str, object]:
    index = {c: i for i, c in enumerate(classes)}
    matrix = [[0] * len(classes) for _ in classes]
    for g, p in zip(gold, predicted, strict=True):
        matrix[index[g]][index[p]] += 1

    per_class = {}
    f1s = []
    for c, i in index.items():
        tp = matrix[i][i]
        fp = sum(matrix[r][i] for r in range(len(classes))) - tp
        fn = sum(matrix[i]) - tp
        precision = tp / (tp + fp) if tp + fp else 0.0
        recall = tp / (tp + fn) if tp + fn else 0.0
        f1 = 2 * precision * recall / (precision + recall) if precision + recall else 0.0
        support = sum(matrix[i])
        per_class[c] = {"precision": round(precision, 3), "recall": round(recall, 3), "f1": round(f1, 3), "support": support}
        if support:
            f1s.append(f1)

    accuracy = sum(matrix[i][i] for i in range(len(classes))) / max(1, len(gold))
    return {
        "accuracy": round(accuracy, 3),
        "macro_f1": round(sum(f1s) / max(1, len(f1s)), 3),
        "per_class": per_class,
        "confusion": {"labels": list(classes), "matrix": matrix},
    }


def evaluate_rows(predict: Predictor, rows: list[dict[str, str]]) -> dict[str, object]:
    started = time.perf_counter()
    predictions = predict([row["text"] for row in rows])
    elapsed = time.perf_counter() - started
    return {
        "examples": len(rows),
        "ms_per_ticket": round(elapsed * 1000 / max(1, len(rows)), 2),
        "tasks": {task: metrics([r[task] for r in rows], [p[task][0] for p in predictions], classes) for task, classes in TASKS.items()},
    }


def injection_eval(predict: Predictor, rows: list[dict[str, str]], attacks: list[str]) -> dict[str, object]:
    """Fraction of (ticket, attack) pairs whose three labels stay the same."""
    clean = predict([row["text"] for row in rows])
    attacked_texts, owners = [], []
    for i, row in enumerate(rows):
        for attack in attacks:
            attacked_texts.append(f"{row['text']} {attack}")
            owners.append(i)
    attacked = predict(attacked_texts)
    stable = sum(all(attacked[k][t][0] == clean[owners[k]][t][0] for t in TASKS) for k in range(len(attacked)))
    labels_only = all(set(p) == set(TASKS) and all(p[t][0] in TASKS[t] for t in TASKS) for p in attacked)
    return {"pairs": len(attacked), "stable_rate": round(stable / max(1, len(attacked)), 3), "closed_label_set": labels_only}


def load_predictor(name: str) -> tuple[Predictor, str]:
    if name == "baseline":
        from .baseline import BaselineModel

        baseline = BaselineModel(ROOT / "models" / "baseline-v1")
        return baseline.predict, baseline.version

    from .lora import LoraModel

    lora = LoraModel(ROOT / "models" / "lora-v1")
    return lora.predict, lora.version


def run(name: str) -> dict[str, object]:
    predict, version = load_predictor(name)
    attacks = json.loads((ROOT / "data" / "injections.json").read_text(encoding="utf-8"))["attacks"]
    challenge = read(ROOT / "data" / "challenge.jsonl")
    return {
        "model": name,
        "version": version,
        "test": evaluate_rows(predict, read(ROOT / "data" / "test.jsonl")),
        "challenge": evaluate_rows(predict, challenge),
        "injection": injection_eval(predict, challenge, attacks),
    }


def failures(report: dict[str, object], thresholds: dict[str, dict[str, float]]) -> list[str]:
    problems = []
    for split in ("test", "challenge"):
        for task, minimum in thresholds[split].items():
            value = report[split]["tasks"][task]["macro_f1"]  # type: ignore[index]
            if value < minimum:
                problems.append(f"{split} {task} macro-F1 {value} < {minimum}")
    injection = report["injection"]
    if injection["stable_rate"] < thresholds["injection"]["stable_rate"] or not injection["closed_label_set"]:  # type: ignore[index]
        problems.append(f"injection stable rate {injection['stable_rate']} < {thresholds['injection']['stable_rate']}")  # type: ignore[index]
    return problems


def main() -> None:
    parser = argparse.ArgumentParser(description="Evaluate a classifier and optionally enforce the thresholds.")
    parser.add_argument("--model", choices=("baseline", "lora"), required=True)
    parser.add_argument("--check", action="store_true", help="Exit 1 when a metric is below thresholds.json")
    parser.add_argument("--write", action="store_true", help="Write reports/<model>.json")
    args = parser.parse_args()

    report = run(args.model)
    summary = {split: {t: report[split]["tasks"][t]["macro_f1"] for t in TASKS} for split in ("test", "challenge")}  # type: ignore[index]
    print(json.dumps({"model": args.model, "macro_f1": summary, "injection": report["injection"]}, indent=2))

    if args.write:
        (ROOT / "reports").mkdir(exist_ok=True)
        (ROOT / "reports" / f"{args.model}.json").write_text(json.dumps(report, indent=2, ensure_ascii=False) + "\n", encoding="utf-8")

    if args.check:
        thresholds = json.loads((ROOT / "thresholds.json").read_text(encoding="utf-8"))[args.model]
        problems = failures(report, thresholds)
        for problem in problems:
            print(f"FAIL: {problem}", file=sys.stderr)
        sys.exit(1 if problems else 0)


if __name__ == "__main__":
    main()
