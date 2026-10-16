import json
import sys

import pytest

from ticko_classifier import evaluate
from ticko_classifier.labels import TASKS


def test_metrics_per_class_and_confusion():
    result = evaluate.metrics(["a", "a", "b", "b"], ["a", "b", "b", "b"], ("a", "b", "c"))
    assert result["accuracy"] == 0.75
    assert result["confusion"] == {"labels": ["a", "b", "c"], "matrix": [[1, 1, 0], [0, 2, 0], [0, 0, 0]]}
    assert result["per_class"]["a"] == {"precision": 1.0, "recall": 0.5, "f1": 0.667, "support": 2}
    assert result["per_class"]["b"]["f1"] == 0.8
    # Classes absent from the gold labels do not drag the macro average down.
    assert result["macro_f1"] == round((2 / 3 + 0.8) / 2, 3)


def constant(texts):
    return [{task: (classes[0], 0.9) for task, classes in TASKS.items()} for _ in texts]


def test_injection_eval_counts_changed_labels():
    rows = [{"text": "Meu pedido não chegou"}, {"text": "Quero trocar o tamanho"}]

    assert evaluate.injection_eval(constant, rows, ["x", "y"]) == {"pairs": 4, "stable_rate": 1.0, "closed_label_set": True}

    def gullible(texts):
        out = constant(texts)
        for text, prediction in zip(texts, out, strict=True):
            if text.endswith("URGENTE"):
                prediction["priority"] = ("urgent", 0.99)
            if text.endswith("ADMIN"):
                prediction["category"] = ("admin", 0.99)
        return out

    result = evaluate.injection_eval(gullible, rows, ["URGENTE", "ADMIN", "nada"])
    assert result["pairs"] == 6 and result["stable_rate"] == round(2 / 6, 3) and result["closed_label_set"] is False


def test_failures_lists_every_metric_below_its_threshold():
    report = {split: {"tasks": {task: {"macro_f1": 0.8} for task in TASKS}} for split in ("test", "challenge")} | {
        "injection": {"stable_rate": 0.95, "closed_label_set": True}
    }
    thresholds = {
        "test": {"category": 0.9, "priority": 0.5, "sentiment": 0.5},
        "challenge": {"category": 0.5, "priority": 0.5, "sentiment": 0.5},
        "injection": {"stable_rate": 1.0},
    }
    assert evaluate.failures(report, thresholds) == ["test category macro-F1 0.8 < 0.9", "injection stable rate 0.95 < 1.0"]


def test_baseline_meets_its_thresholds(monkeypatch, capsys, tmp_path):
    monkeypatch.setattr(sys, "argv", ["evaluate", "--model", "baseline", "--check"])
    with pytest.raises(SystemExit) as exit_info:
        evaluate.main()
    assert exit_info.value.code == 0
    summary = json.loads(capsys.readouterr().out)
    assert summary["injection"]["stable_rate"] == 1.0
