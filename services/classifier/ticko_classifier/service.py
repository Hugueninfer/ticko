"""HTTP service: POST /v1/classify -> category, priority and sentiment.

Security:
* only reachable on an internal Docker network, and every request needs the
  shared service token (constant-time comparison);
* request bodies are capped (16 KB) and the text bounded (8,000 chars);
* the ticket text is never logged, echoed in errors or stored;
* responses carry only labels from closed sets and confidences;
* no interactive docs or OpenAPI page.
"""

from __future__ import annotations

import hmac
import logging
import os
from collections.abc import Awaitable, Callable, MutableMapping
from contextlib import asynccontextmanager
from pathlib import Path
from typing import Annotated, Protocol

from fastapi import Depends, FastAPI, Header, HTTPException, Request
from fastapi.exceptions import RequestValidationError
from fastapi.responses import JSONResponse
from pydantic import BaseModel, ConfigDict, Field

from .labels import TASKS
from .sanitize import prepare

MAX_BODY_BYTES = 16 * 1024
MAX_TEXT_CHARS = 8000
ROOT = Path(__file__).resolve().parent.parent

logger = logging.getLogger("ticko.classifier")


class Predictor(Protocol):
    version: str

    def predict(self, texts: list[str]) -> list[dict[str, tuple[str, float]]]: ...


class ClassifyRequest(BaseModel):
    model_config = ConfigDict(extra="forbid")

    text: Annotated[str, Field(min_length=1, max_length=MAX_TEXT_CHARS)]


class Label(BaseModel):
    label: str
    confidence: float


class ClassifyResponse(BaseModel):
    model: str
    category: Label
    priority: Label
    sentiment: Label
    flags: list[str]


Message = MutableMapping[str, object]
Receive = Callable[[], Awaitable[Message]]
Send = Callable[[Message], Awaitable[None]]


class _TooLarge(Exception):
    pass


class BodyLimit:
    """ASGI middleware: answers 413 when a request body exceeds `limit` bytes."""

    def __init__(self, app: Callable[[Message, Receive, Send], Awaitable[None]], limit: int) -> None:
        self.app = app
        self.limit = limit

    async def __call__(self, scope: Message, receive: Receive, send: Send) -> None:
        if scope["type"] != "http":
            await self.app(scope, receive, send)
            return

        headers = dict(scope.get("headers", []))  # type: ignore[arg-type]
        declared = headers.get(b"content-length")
        if declared is not None and (not declared.isdigit() or int(declared) > self.limit):
            await self._reject(send)
            return

        received = 0

        async def limited() -> Message:
            nonlocal received
            message = await receive()
            if message["type"] == "http.request":
                received += len(message.get("body", b""))  # type: ignore[arg-type]
                if received > self.limit:
                    raise _TooLarge
            return message

        try:
            await self.app(scope, limited, send)
        except _TooLarge:
            await self._reject(send)

    @staticmethod
    async def _reject(send: Send) -> None:
        body = b'{"detail":"Request body too large."}'
        await send({"type": "http.response.start", "status": 413, "headers": [(b"content-type", b"application/json")]})
        await send({"type": "http.response.body", "body": body})


def load_predictor(name: str) -> Predictor:
    if name == "baseline":
        from .baseline import BaselineModel

        return BaselineModel(ROOT / "models" / "baseline-v1")
    if name == "lora":
        from .lora import LoraModel

        return LoraModel(ROOT / "models" / "lora-v1")
    raise RuntimeError("CLASSIFIER_MODEL must be 'lora' or 'baseline'.")


def create_app(predictor: Predictor | None = None, token: str | None = None) -> FastAPI:
    secret = token if token is not None else os.environ.get("CLASSIFIER_TOKEN", "")
    if len(secret) < 32:
        raise RuntimeError("CLASSIFIER_TOKEN must be set (at least 32 characters).")

    state: dict[str, Predictor] = {}

    @asynccontextmanager
    async def lifespan(_: FastAPI):
        state["model"] = predictor or load_predictor(os.environ.get("CLASSIFIER_MODEL", "lora"))
        logger.info("classifier ready: %s", state["model"].version)
        yield

    app = FastAPI(title="Ticko classifier", docs_url=None, redoc_url=None, openapi_url=None, lifespan=lifespan)

    app.add_middleware(BodyLimit, limit=MAX_BODY_BYTES)

    @app.exception_handler(RequestValidationError)
    async def validation_error(_: Request, exc: RequestValidationError) -> JSONResponse:
        # Field names and reasons only: never echo the submitted text back.
        errors = [{"field": ".".join(str(p) for p in e.get("loc", ())[1:]), "type": e.get("type")} for e in exc.errors()]
        return JSONResponse({"detail": "Invalid request.", "errors": errors}, status_code=422)

    def authorize(authorization: Annotated[str | None, Header()] = None) -> None:
        expected = f"Bearer {secret}"
        if authorization is None or not hmac.compare_digest(authorization.encode(), expected.encode()):
            raise HTTPException(status_code=401, detail="Unauthorized.")

    @app.get("/health")
    def health() -> dict[str, str]:
        model = state.get("model")
        return {"status": "ok" if model else "starting", "model": model.version if model else ""}

    @app.post("/v1/classify", dependencies=[Depends(authorize)])
    def classify(payload: ClassifyRequest) -> ClassifyResponse:
        prepared = prepare(payload.text)
        flags = []
        if prepared.instructions_removed:
            flags.append("instructions_removed")
        if prepared.pii_masked:
            flags.append("pii_masked")

        [prediction] = state["model"].predict([payload.text])
        labels = {}
        for task, classes in TASKS.items():
            label, confidence = prediction[task]
            if label not in classes:  # pragma: no cover - defensive: models only know these classes
                raise HTTPException(status_code=500, detail="Invalid model output.")
            labels[task] = Label(label=label, confidence=round(float(confidence), 4))

        return ClassifyResponse(model=state["model"].version, flags=flags, **labels)

    return app
