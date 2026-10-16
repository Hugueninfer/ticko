import json
from pathlib import Path

import pytest

from ticko_classifier.sanitize import MAX_CHARS, mask_pii, prepare, strip_instructions

ROOT = Path(__file__).resolve().parent.parent


@pytest.mark.parametrize(
    ("text", "placeholder"),
    [
        ("meu cpf é 123.456.789-09", "[CPF]"),
        ("cpf 12345678909", "[CPF]"),
        ("cartão 4111 1111 1111 1111", "[CARTAO]"),
        ("cartão 5555444433331111", "[CARTAO]"),
        ("escreve pra fulana.tal@example.com", "[EMAIL]"),
        ("liga no (11) 98765-4321", "[TELEFONE]"),
        ("whats +55 11 98765 4321", "[TELEFONE]"),
    ],
)
def test_masks_personal_data(text, placeholder):
    masked, count = mask_pii(text)
    assert placeholder in masked and count >= 1


def test_keeps_order_numbers_amounts_and_dates():
    text = "Pedido #88213 de R$ 320,32 comprado em 06/10, cartão final 2407."
    assert mask_pii(text) == (text, 0)


def test_removes_every_attack_and_keeps_the_request():
    attacks = json.loads((ROOT / "data" / "injections.json").read_text(encoding="utf-8"))["attacks"]
    for attack in attacks:
        for glue in (". ", " ", "\n"):
            prepared = prepare(f"Pedido atrasado\nMeu pedido não chegou{glue}{attack}")
            assert prepared.instructions_removed >= 1, attack
            assert "pedido não chegou" in prepared.text.lower(), attack
            assert "urgent" not in prepared.text.lower(), attack
            # Nothing of the attack is left behind, not even its markers.
            assert prepared.text.rstrip(" .").endswith("chegou"), (attack, prepared.text)


def test_keeps_genuine_urgency_and_complaints():
    text = "Preciso disso para amanhã, é urgente! Estou indignado com o atraso."
    assert strip_instructions(text) == (text, 0)


def test_normalizes_and_bounds_the_text():
    prepared = prepare("  muito   espaço\n\n aqui " + "x" * 5000)
    assert "  " not in prepared.text and len(prepared.text) == MAX_CHARS
