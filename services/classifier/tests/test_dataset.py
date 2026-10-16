import json
import re
from pathlib import Path

from ticko_classifier import dataset
from ticko_classifier.labels import TASKS
from ticko_classifier.sanitize import mask_pii

ROOT = Path(__file__).resolve().parent.parent


def test_generation_is_deterministic_and_matches_the_versioned_files():
    first = dataset.generate()
    assert first == dataset.generate()
    for split, rows in first.items():
        assert rows == dataset.read(ROOT / "data" / f"{split}.jsonl"), f"data/{split}.jsonl is stale: run make classifier-data"


def test_labels_belong_to_the_taxonomy():
    for path in [*(ROOT / "data").glob("*.jsonl")]:
        for row in dataset.read(path):
            for task, classes in TASKS.items():
                assert row[task] in classes


def test_every_phrase_belongs_to_exactly_one_split():
    for topic in dataset.TOPICS:
        parts = dataset._partition(topic.problems, topic.name)
        assert sum(len(v) for v in parts.values()) == len(topic.problems)
        assert not set(parts["train"]) & set(parts["test"])
        assert not set(parts["train"]) & set(parts["validation"])
        assert all(parts[split] for split in dataset.SPLITS)

    cue_lists = (
        (dataset.URGENT_CUES, "urgent"),
        (dataset.NEGATIVE_CUES, "negative"),
        (dataset.POSITIVE_CUES, "positive"),
        (dataset.CALM_CUES, "calm"),
    )
    for cues, salt in cue_lists:
        parts = dataset._partition(cues, salt)
        assert not set(parts["train"]) & set(parts["test"])


def test_test_tickets_use_phrasings_never_seen_in_training():
    train_text = "\n".join(r["text"] for r in dataset.read(ROOT / "data" / "train.jsonl"))
    for topic in dataset.TOPICS:
        for template in dataset._partition(topic.problems, topic.name)["test"]:
            stem = re.split(r"\{", template)[0].strip()
            if len(stem) > 12:
                assert stem not in train_text


def test_all_data_is_fictional_and_free_of_personal_data():
    # The same detectors the service uses to mask CPF, card numbers, e-mails and phones.
    for path in (ROOT / "data").glob("*.jsonl"):
        for row in dataset.read(path):
            assert mask_pii(row["text"])[1] == 0, (path.name, row["id"])


def test_injection_set_is_documented():
    data = json.loads((ROOT / "data" / "injections.json").read_text(encoding="utf-8"))
    assert len(data["attacks"]) >= 10
