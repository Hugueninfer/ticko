"""Label taxonomy shared by the dataset, the models and the service.

The classifier uses a generic e-commerce taxonomy; the Ticko API maps a
category label to a tenant's category only when the tenant has a category
with the matching slug (see apps/api config `ticko.classifier.category_slugs`).
"""

from __future__ import annotations

CATEGORIES: tuple[str, ...] = ("entrega", "pagamento", "troca_devolucao", "conta_acesso", "produto", "outro")
PRIORITIES: tuple[str, ...] = ("low", "normal", "high", "urgent")
SENTIMENTS: tuple[str, ...] = ("negative", "neutral", "positive")

TASKS: dict[str, tuple[str, ...]] = {
    "category": CATEGORIES,
    "priority": PRIORITIES,
    "sentiment": SENTIMENTS,
}


def ticket_text(subject: str, body: str) -> str:
    """The single text the models see: subject, then the first message."""
    return f"{subject.strip()}\n{body.strip()}".strip()
