from pathlib import Path

import numpy as np

from ticko_classifier import baseline
from ticko_classifier.dataset import read
from ticko_classifier.labels import TASKS
from ticko_classifier.sanitize import prepare

ROOT = Path(__file__).resolve().parent.parent


def test_numpy_export_reproduces_scikit_learn_exactly(tmp_path):
    from sklearn.feature_extraction.text import TfidfVectorizer
    from sklearn.linear_model import LogisticRegression

    rows = read(ROOT / "data" / "train.jsonl")[:400]
    baseline.train(rows, tmp_path, version="test")
    model = baseline.BaselineModel(tmp_path)

    texts = [prepare(r["text"]).text for r in rows]
    vectorizer = TfidfVectorizer(
        analyzer="char_wb",
        ngram_range=baseline.NGRAM_RANGE,
        lowercase=True,
        strip_accents="unicode",
        sublinear_tf=True,
        max_features=baseline.MAX_FEATURES,
        dtype=np.float64,
    )
    matrix = vectorizer.fit_transform(texts)
    sample = [r["text"] for r in rows[:50]]
    ours = model.predict_proba(sample)

    for task, classes in TASKS.items():
        clf = LogisticRegression(max_iter=2000, C=4.0, class_weight="balanced").fit(matrix, [classes.index(r[task]) for r in rows])
        theirs = clf.predict_proba(vectorizer.transform([prepare(t).text for t in sample]))
        mine = np.array([row[task][clf.classes_] for row in ours])
        np.testing.assert_allclose(mine, theirs, atol=1e-6)


def test_versioned_model_loads_without_pickle_and_predicts_closed_labels():
    model = baseline.BaselineModel(ROOT / "models" / "baseline-v1")
    [prediction] = model.predict(["Fui cobrado duas vezes no cartão, isso é um absurdo!"])
    assert set(prediction) == set(TASKS)
    assert prediction["category"][0] == "pagamento"
    assert all(prediction[t][0] in TASKS[t] and 0 <= prediction[t][1] <= 1 for t in TASKS)
    assert model.predict([]) == []
    assert not list((ROOT / "models").rglob("*.pkl")) and not list((ROOT / "models").rglob("*.joblib"))
