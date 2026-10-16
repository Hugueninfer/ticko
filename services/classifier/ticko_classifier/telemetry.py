"""OpenTelemetry for the classifier service (`make up-obs`).

Off unless TICKO_TELEMETRY=true. When on:

* one server span per request, continuing the trace of the Ticko queue job
  that called it (W3C `traceparent`), with the route and status only;
* metrics: inference duration by model and outcome, confidence by task and
  label, and how often instructions or personal data were removed;
* never the ticket text, the token or any header value.
"""

from __future__ import annotations

import os
import time
from collections.abc import Iterator
from contextlib import contextmanager
from dataclasses import dataclass

from opentelemetry import metrics, trace
from opentelemetry.metrics import Meter
from opentelemetry.sdk.metrics import MeterProvider
from opentelemetry.sdk.metrics.export import MetricReader, PeriodicExportingMetricReader
from opentelemetry.sdk.resources import Resource
from opentelemetry.sdk.trace import TracerProvider
from opentelemetry.sdk.trace.export import BatchSpanProcessor, SpanExporter

SECONDS = (0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5)
CONFIDENCE = (0.1, 0.2, 0.3, 0.4, 0.5, 0.6, 0.7, 0.8, 0.9, 0.95, 1)


def enabled() -> bool:
    return os.environ.get("TICKO_TELEMETRY", "").lower() in {"1", "true", "yes"}


@dataclass
class Instruments:
    duration: metrics.Histogram
    confidence: metrics.Histogram
    flags: metrics.Counter

    @classmethod
    def create(cls, meter: Meter) -> Instruments:
        return cls(
            duration=meter.create_histogram(
                "ticko.classifier.inference.duration",
                unit="s",
                description="Model inference time per ticket",
                explicit_bucket_boundaries_advisory=list(SECONDS),
            ),
            confidence=meter.create_histogram(
                "ticko.classifier.model.confidence",
                unit="1",
                description="Model confidence by task and label",
                explicit_bucket_boundaries_advisory=list(CONFIDENCE),
            ),
            flags=meter.create_counter(
                "ticko.classifier.sanitized",
                description="Requests whose text had instructions or personal data removed",
            ),
        )

    @contextmanager
    def inference(self, model: str) -> Iterator[None]:
        started = time.perf_counter()
        outcome = "ok"
        try:
            yield
        except Exception:
            outcome = "error"
            raise
        finally:
            self.duration.record(time.perf_counter() - started, {"model": model, "outcome": outcome})

    def labels(self, labels: dict[str, tuple[str, float]], flags: list[str]) -> None:
        for task, (label, confidence) in labels.items():
            self.confidence.record(confidence, {"task": task, "label": label})
        for flag in flags:
            self.flags.add(1, {"flag": flag})


def setup(span_exporter: SpanExporter | None = None, metric_reader: MetricReader | None = None) -> Instruments:
    """Configures providers once per process. Exporters are injectable for tests."""
    resource = Resource.create(
        {
            "service.name": os.environ.get("OTEL_SERVICE_NAME", "ticko-classifier"),
            "service.namespace": "ticko",
            "service.version": "1.0.0",
        }
    )

    if span_exporter is None or metric_reader is None:
        from opentelemetry.exporter.otlp.proto.http.metric_exporter import OTLPMetricExporter
        from opentelemetry.exporter.otlp.proto.http.trace_exporter import OTLPSpanExporter

        endpoint = os.environ.get("OTEL_EXPORTER_OTLP_ENDPOINT", "http://otel-collector:4318").rstrip("/")
        span_exporter = span_exporter or OTLPSpanExporter(endpoint=f"{endpoint}/v1/traces", timeout=2)
        metric_reader = metric_reader or PeriodicExportingMetricReader(
            OTLPMetricExporter(endpoint=f"{endpoint}/v1/metrics", timeout=2),
            export_interval_millis=15_000,
        )

    tracer_provider = TracerProvider(resource=resource)
    tracer_provider.add_span_processor(BatchSpanProcessor(span_exporter))
    trace.set_tracer_provider(tracer_provider)
    meter_provider = MeterProvider(resource=resource, metric_readers=[metric_reader])
    metrics.set_meter_provider(meter_provider)

    return Instruments.create(metrics.get_meter("ticko.classifier"))


def instrument(app: object) -> None:
    from opentelemetry.instrumentation.fastapi import FastAPIInstrumentor

    # /health is polled every few seconds by Docker: not worth a span.
    FastAPIInstrumentor.instrument_app(app, excluded_urls="health")
