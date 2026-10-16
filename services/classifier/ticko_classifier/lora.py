"""BERTimbau base + LoRA, with three classification heads (multi-task).

The encoder stays frozen except for LoRA adapters on the attention query
and value projections; three linear heads read the mean-pooled tokens.
Only the adapters and heads are trained and saved (a few MB, in
safetensors), so they can be versioned; the base model is fetched at a
pinned revision with checked hashes (fetch_model.py).
"""

from __future__ import annotations

import argparse
import json
import os
import platform
import random
import time
from pathlib import Path

import torch
from peft import LoraConfig, PeftModel, get_peft_model
from safetensors.torch import load_file, save_file
from torch import nn
from transformers import AutoTokenizer, BertModel

from . import fetch_model
from .labels import TASKS
from .sanitize import prepare

MAX_LENGTH = 128
LORA = {"r": 8, "lora_alpha": 16, "lora_dropout": 0.1, "target_modules": ["query", "value"]}


class MultiTaskClassifier(nn.Module):
    def __init__(self, encoder: nn.Module, hidden_size: int = 768) -> None:
        super().__init__()
        self.encoder = encoder
        self.dropout = nn.Dropout(0.1)
        self.heads = nn.ModuleDict({task: nn.Linear(hidden_size, len(classes)) for task, classes in TASKS.items()})

    def forward(self, input_ids: torch.Tensor, attention_mask: torch.Tensor) -> dict[str, torch.Tensor]:
        hidden = self.encoder(input_ids=input_ids, attention_mask=attention_mask).last_hidden_state
        mask = attention_mask.unsqueeze(-1).to(hidden.dtype)
        pooled = (hidden * mask).sum(dim=1) / mask.sum(dim=1).clamp(min=1.0)
        pooled = self.dropout(pooled)
        return {task: head(pooled) for task, head in self.heads.items()}


def _encoder(base_dir: Path) -> BertModel:
    # Safetensors only: transformers refuses to fall back to pickle here.
    return BertModel.from_pretrained(str(base_dir), use_safetensors=True, add_pooling_layer=False)


class LoraModel:
    """Inference wrapper: base model + adapters merged once at load time."""

    def __init__(self, directory: Path, base_dir: Path = fetch_model.DEFAULT_DIR) -> None:
        if not fetch_model.verify(base_dir):
            raise RuntimeError(f"Base model missing or altered in {base_dir}; run fetch_model.")
        meta = json.loads((directory / "meta.json").read_text(encoding="utf-8"))
        self.version: str = meta["version"]
        self.name = "bertimbau-lora"
        self.labels: dict[str, list[str]] = meta["labels"]
        torch.set_num_threads(max(1, os.cpu_count() or 1))
        encoder = PeftModel.from_pretrained(_encoder(base_dir), str(directory / "adapter")).merge_and_unload()
        self.model = MultiTaskClassifier(encoder)
        self.model.heads.load_state_dict(load_file(str(directory / "heads.safetensors")))
        self.model.eval()
        self.tokenizer = AutoTokenizer.from_pretrained(str(base_dir))

    @torch.inference_mode()
    def predict(self, texts: list[str], batch_size: int = 32) -> list[dict[str, tuple[str, float]]]:
        results: list[dict[str, tuple[str, float]]] = []
        for start in range(0, len(texts), batch_size):
            batch = [prepare(text).text for text in texts[start : start + batch_size]]
            encoded = self.tokenizer(batch, padding=True, truncation=True, max_length=MAX_LENGTH, return_tensors="pt")
            logits = self.model(encoded["input_ids"], encoded["attention_mask"])
            probs = {task: torch.softmax(value, dim=-1) for task, value in logits.items()}
            for i in range(len(batch)):
                results.append({task: (self.labels[task][int(p[i].argmax())], float(p[i].max())) for task, p in probs.items()})
        return results


def train(
    train_rows: list[dict[str, str]],
    val_rows: list[dict[str, str]],
    out: Path,
    base_dir: Path,
    *,
    epochs: int = 3,
    batch_size: int = 16,
    lr: float = 5e-4,
    seed: int = 13,
) -> dict[str, object]:
    random.seed(seed)
    torch.manual_seed(seed)
    torch.set_num_threads(max(1, os.cpu_count() or 1))
    device = "cuda" if torch.cuda.is_available() else "cpu"

    tokenizer = AutoTokenizer.from_pretrained(str(base_dir))
    encoder = get_peft_model(_encoder(base_dir), LoraConfig(bias="none", **LORA))
    model = MultiTaskClassifier(encoder).to(device)
    trainable = sum(p.numel() for p in model.parameters() if p.requires_grad)
    total = sum(p.numel() for p in model.parameters())

    def batches(rows: list[dict[str, str]], shuffle: bool):
        order = list(range(len(rows)))
        if shuffle:
            random.shuffle(order)
        for start in range(0, len(order), batch_size):
            chunk = [rows[i] for i in order[start : start + batch_size]]
            encoded = tokenizer(
                [prepare(r["text"]).text for r in chunk], padding=True, truncation=True, max_length=MAX_LENGTH, return_tensors="pt"
            )
            targets = {task: torch.tensor([classes.index(r[task]) for r in chunk]) for task, classes in TASKS.items()}
            yield encoded["input_ids"].to(device), encoded["attention_mask"].to(device), {k: v.to(device) for k, v in targets.items()}

    optimizer = torch.optim.AdamW([p for p in model.parameters() if p.requires_grad], lr=lr, weight_decay=0.01)
    steps = epochs * ((len(train_rows) + batch_size - 1) // batch_size)
    scheduler = torch.optim.lr_scheduler.OneCycleLR(optimizer, max_lr=lr, total_steps=steps, pct_start=0.1)
    loss_fn = nn.CrossEntropyLoss()

    started = time.perf_counter()
    history = []
    for epoch in range(epochs):
        model.train()
        running = 0.0
        for input_ids, mask, targets in batches(train_rows, shuffle=True):
            logits = model(input_ids, mask)
            loss = sum(loss_fn(logits[task], targets[task]) for task in TASKS)
            optimizer.zero_grad()
            loss.backward()
            optimizer.step()
            scheduler.step()
            running += loss.item()

        model.eval()
        correct = dict.fromkeys(TASKS, 0)
        with torch.inference_mode():
            for input_ids, mask, targets in batches(val_rows, shuffle=False):
                logits = model(input_ids, mask)
                for task in TASKS:
                    correct[task] += int((logits[task].argmax(-1) == targets[task]).sum())
        accuracy = {task: round(correct[task] / len(val_rows), 4) for task in TASKS}
        history.append(
            {
                "epoch": epoch + 1,
                "train_loss": round(running, 2),
                "val_accuracy": accuracy,
                "elapsed_seconds": round(time.perf_counter() - started, 1),
            }
        )
        print(json.dumps(history[-1]), flush=True)

    out.mkdir(parents=True, exist_ok=True)
    model.encoder.save_pretrained(str(out / "adapter"), safe_serialization=True)
    save_file({k: v.detach().cpu().contiguous() for k, v in model.heads.state_dict().items()}, str(out / "heads.safetensors"))
    info = {
        "version": out.name,
        "base_model": {"repo": fetch_model.REPO, "revision": fetch_model.REVISION},
        "labels": {task: list(classes) for task, classes in TASKS.items()},
        "lora": LORA,
        "training": {
            "examples": len(train_rows),
            "epochs": epochs,
            "batch_size": batch_size,
            "learning_rate": lr,
            "max_length": MAX_LENGTH,
            "trainable_parameters": trainable,
            "total_parameters": total,
            "device": device,
            "hardware": platform.processor() or platform.machine(),
            "threads": torch.get_num_threads(),
            "seconds": round(time.perf_counter() - started, 1),
            "history": history,
        },
    }
    (out / "meta.json").write_text(json.dumps(info, ensure_ascii=False, indent=2), encoding="utf-8")
    return info


def main() -> None:
    from .dataset import read

    root = Path(__file__).resolve().parent.parent
    parser = argparse.ArgumentParser(description="Fine-tune BERTimbau with LoRA on the ticket dataset.")
    parser.add_argument("--data", type=Path, default=root / "data")
    parser.add_argument("--out", type=Path, default=root / "models" / "lora-v1")
    parser.add_argument("--base", type=Path, default=fetch_model.DEFAULT_DIR)
    parser.add_argument("--epochs", type=int, default=3)
    args = parser.parse_args()
    fetch_model.fetch(args.base)
    info = train(read(args.data / "train.jsonl"), read(args.data / "validation.jsonl"), args.out, args.base, epochs=args.epochs)
    print(json.dumps(info["training"], indent=2))


if __name__ == "__main__":
    main()
