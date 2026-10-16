#!/usr/bin/env python3
"""Generates the provisioned Grafana dashboards (dashboards/*.json).

Dashboards are code: edit this file and run `make obs-dashboards`; the JSON
files are committed and loaded read-only by Grafana. Every query uses
bounded labels only (route templates, enums, tenant slug).
"""

from __future__ import annotations

import json
from pathlib import Path

PROM = {"type": "prometheus", "uid": "prometheus"}
LOKI = {"type": "loki", "uid": "loki"}
TEMPO = {"type": "tempo", "uid": "tempo"}
OUT = Path(__file__).resolve().parent / "dashboards"


class Board:
    def __init__(self, uid: str, title: str, description: str, tenant_var: bool = False) -> None:
        self.uid, self.title, self.description = uid, title, description
        self.panels: list[dict] = []
        self.y = 0
        self.x = 0
        self.row_h = 0
        self.tenant_var = tenant_var

    def _pos(self, w: int, h: int) -> dict:
        if self.x + w > 24:
            self.x, self.y = 0, self.y + self.row_h
            self.row_h = 0
        pos = {"x": self.x, "y": self.y, "w": w, "h": h}
        self.x += w
        self.row_h = max(self.row_h, h)
        return pos

    def row(self, title: str) -> None:
        if self.x:
            self.x, self.y, self.row_h = 0, self.y + self.row_h, 0
        self.panels.append({"type": "row", "title": title, "collapsed": False, "gridPos": {"x": 0, "y": self.y, "w": 24, "h": 1}, "panels": []})
        self.y += 1

    def panel(self, kind: str, title: str, targets: list[dict], w: int = 12, h: int = 8, unit: str = "short",
              description: str = "", options: dict | None = None, ds: dict = PROM, overrides: list | None = None,
              thresholds: list | None = None) -> None:
        defaults: dict = {"unit": unit}
        if thresholds:
            defaults["thresholds"] = {"mode": "absolute", "steps": thresholds}
            defaults["color"] = {"mode": "thresholds"}
        panel = {
            "type": kind, "title": title, "description": description, "datasource": ds,
            "gridPos": self._pos(w, h),
            "targets": [{**t, "refId": chr(65 + i), "datasource": ds} for i, t in enumerate(targets)],
            "fieldConfig": {"defaults": defaults, "overrides": overrides or []},
            "options": options or {},
        }
        self.panels.append(panel)

    def json(self) -> dict:
        templating = []
        if self.tenant_var:
            templating.append({
                "name": "tenant", "label": "Tenant", "type": "query", "datasource": PROM,
                "query": {"query": "label_values(ticko_tickets_open, tenant)", "refId": "tenant"},
                "definition": "label_values(ticko_tickets_open, tenant)",
                "includeAll": True, "multi": True, "allValue": ".*", "refresh": 2,
                "current": {"selected": True, "text": "All", "value": "$__all"},
            })
        for i, panel in enumerate(self.panels, start=1):
            panel["id"] = i
        return {
            "uid": self.uid, "title": self.title, "description": self.description,
            "tags": ["ticko"], "timezone": "browser", "schemaVersion": 41, "version": 1,
            "editable": False, "graphTooltip": 1, "refresh": "30s",
            "time": {"from": "now-1h", "to": "now"},
            "templating": {"list": templating},
            "panels": self.panels,
            "links": [{"title": "Ticko", "type": "dashboards", "tags": ["ticko"], "asDropdown": True}],
        }


def q(expr: str, legend: str = "", instant: bool = False) -> dict:
    target = {"expr": expr, "legendFormat": legend, "range": not instant, "instant": instant}
    return target


TS = "timeseries"
STAT = "stat"
LEGEND = {"legend": {"displayMode": "table", "placement": "right", "calcs": ["lastNotNull", "max"]}}
API_SEL = 'job="ticko/ticko-api"'


def api() -> Board:
    b = Board("ticko-api", "Ticko · API health", "Latency (p50/p95), errors and throughput of the API, by route template.")
    b.row("Overview")
    b.panel(STAT, "Requests / s", [q(f"sum(rate(ticko_http_server_duration_seconds_count{{{API_SEL}}}[5m]))")], w=6, h=4, unit="reqps")
    b.panel(STAT, "p95 latency", [q(f"histogram_quantile(0.95, sum by (le) (rate(ticko_http_server_duration_seconds_bucket{{{API_SEL}}}[5m])))")],
            w=6, h=4, unit="s", thresholds=[{"color": "green", "value": None}, {"color": "orange", "value": 0.5}, {"color": "red", "value": 1}])
    b.panel(STAT, "5xx ratio", [q(f'(sum(rate(ticko_http_server_duration_seconds_count{{{API_SEL},http_response_status_code=~"5.."}}[5m])) or vector(0)) / clamp_min(sum(rate(ticko_http_server_duration_seconds_count{{{API_SEL}}}[5m])), 1e-9)')],
            w=6, h=4, unit="percentunit", thresholds=[{"color": "green", "value": None}, {"color": "red", "value": 0.01}])
    b.panel(STAT, "403/404 in 15 min", [q(f'sum(increase(ticko_http_server_duration_seconds_count{{{API_SEL},http_response_status_code=~"403|404"}}[15m])) or vector(0)')],
            w=6, h=4, description="Denied or not found: a burst can mean someone probing ids of another tenant.",
            thresholds=[{"color": "green", "value": None}, {"color": "orange", "value": 20}, {"color": "red", "value": 50}])
    b.row("Latency")
    b.panel(TS, "Latency p50 / p95", [
        q(f"histogram_quantile(0.50, sum by (le) (rate(ticko_http_server_duration_seconds_bucket{{{API_SEL}}}[5m])))", "p50"),
        q(f"histogram_quantile(0.95, sum by (le) (rate(ticko_http_server_duration_seconds_bucket{{{API_SEL}}}[5m])))", "p95"),
    ], unit="s")
    b.panel(TS, "p95 by route", [q(f"histogram_quantile(0.95, sum by (le, http_route) (rate(ticko_http_server_duration_seconds_bucket{{{API_SEL}}}[5m])))", "{{http_route}}")],
            unit="s", options=LEGEND)
    b.row("Throughput and errors")
    b.panel(TS, "Requests / s by status", [q(f"sum by (http_response_status_code) (rate(ticko_http_server_duration_seconds_count{{{API_SEL}}}[5m]))", "{{http_response_status_code}}")],
            unit="reqps")
    b.panel(TS, "Requests / s by route", [q(f"sum by (http_route) (rate(ticko_http_server_duration_seconds_count{{{API_SEL}}}[5m]))", "{{http_route}}")],
            unit="reqps", options=LEGEND)
    b.panel(TS, "Failed sign-ins / 5 min", [q("sum by (tenant) (increase(ticko_auth_login_failures_total[5m]))", "{{tenant}}")])
    b.panel("logs", "Application logs (redacted, linked to traces)",
            [{"expr": '{service_name=~"ticko-.*"}', "queryType": "range"}],
            ds=LOKI, options={"showTime": True, "wrapLogMessage": True, "sortOrder": "Descending", "enableLogDetails": True})
    return b


def queue() -> Board:
    b = Board("ticko-queue", "Ticko · Queue (Horizon)", "Jobs processed, failed and released, durations, backlog and wait time.")
    b.row("Now")
    b.panel(STAT, "Pending jobs", [q("sum(ticko_queue_pending)")], w=6, h=4)
    b.panel(STAT, "Wait (Horizon estimate)", [q("max(ticko_queue_wait_seconds)")], w=6, h=4, unit="s",
            thresholds=[{"color": "green", "value": None}, {"color": "orange", "value": 30}, {"color": "red", "value": 120}])
    b.panel(STAT, "Failed (7 days)", [q("max(ticko_queue_failed_recent)")], w=6, h=4,
            thresholds=[{"color": "green", "value": None}, {"color": "red", "value": 1}])
    b.panel(STAT, "Jobs / min", [q("sum(rate(ticko_queue_job_duration_seconds_count[5m])) * 60")], w=6, h=4)
    b.row("Jobs")
    b.panel(TS, "Jobs / min by job and outcome", [q("sum by (job_name, outcome) (rate(ticko_queue_job_duration_seconds_count[5m])) * 60", "{{job_name}} · {{outcome}}")],
            options=LEGEND)
    b.panel(TS, "Job duration p95", [q("histogram_quantile(0.95, sum by (le, job_name) (rate(ticko_queue_job_duration_seconds_bucket[5m])))", "{{job_name}}")],
            unit="s", options=LEGEND)
    b.panel(TS, "Failed and released jobs", [q('sum by (job_name, outcome) (increase(ticko_queue_job_duration_seconds_count{outcome!="processed"}[5m])) or vector(0)', "{{job_name}} {{outcome}}")])
    b.panel(TS, "Backlog and wait", [q("sum by (queue) (ticko_queue_pending)", "pending · {{queue}}"), q("max by (queue) (ticko_queue_wait_seconds)", "wait s · {{queue}}")])
    return b


def sla() -> Board:
    b = Board("ticko-sla", "Ticko · Tickets and SLA", "Open, unassigned and overdue tickets per tenant, sampled every minute.", tenant_var=True)
    sel = 'tenant=~"$tenant"'
    b.row("Now")
    b.panel(STAT, "Open tickets", [q(f"sum(ticko_tickets_open{{{sel}}})")], w=6, h=4)
    b.panel(STAT, "Unassigned", [q(f"sum(ticko_tickets_unassigned{{{sel}}})")], w=6, h=4)
    b.panel(STAT, "Past first-response deadline", [q(f'sum(ticko_tickets_overdue{{{sel},deadline="first_response"}})')], w=6, h=4,
            thresholds=[{"color": "green", "value": None}, {"color": "red", "value": 1}])
    b.panel(STAT, "Past resolution deadline", [q(f'sum(ticko_tickets_overdue{{{sel},deadline="resolution"}})')], w=6, h=4,
            thresholds=[{"color": "green", "value": None}, {"color": "red", "value": 1}])
    b.row("Over time")
    b.panel(TS, "Open tickets by priority", [q(f"sum by (priority) (ticko_tickets_open{{{sel}}})", "{{priority}}")],
            options={"stacking": {"mode": "normal"}})
    b.panel(TS, "Overdue tickets by tenant and deadline", [q(f"sum by (tenant, deadline) (ticko_tickets_overdue{{{sel}}})", "{{tenant}} · {{deadline}}")])
    b.panel("bargauge", "Overdue by tenant", [q(f"sum by (tenant) (ticko_tickets_overdue{{{sel}}})", "{{tenant}}", instant=True)],
            options={"orientation": "horizontal", "displayMode": "gradient"})
    b.panel(TS, "Unassigned by tenant", [q(f"sum by (tenant) (ticko_tickets_unassigned{{{sel}}})", "{{tenant}}")])
    return b


def ai() -> Board:
    b = Board("ticko-ai", "Ticko · AI suggestions", "Model latency and errors, \"I don't know\" rate, agent approvals and discards, tokens and estimated cost per tenant.",
              tenant_var=True)
    sel = 'tenant=~"$tenant"'
    b.row("Quality")
    b.panel(STAT, "\"I don't know\" rate", [q(f'(sum(increase(ticko_ai_suggestions_total{{{sel},outcome="insufficient"}}[$__range])) or vector(0)) / clamp_min(sum(increase(ticko_ai_suggestions_total{{{sel}}}[$__range])), 1e-9)')],
            w=6, h=4, unit="percentunit", description="Suggestions where the knowledge base had no good answer.")
    b.panel(STAT, "Approved without edits", [q(f'(sum(increase(ticko_ai_suggestion_reviews_total{{{sel},result="approved"}}[$__range])) or vector(0)) / clamp_min(sum(increase(ticko_ai_suggestion_reviews_total{{{sel}}}[$__range])), 1e-9)')],
            w=6, h=4, unit="percentunit")
    b.panel(STAT, "Discarded", [q(f'(sum(increase(ticko_ai_suggestion_reviews_total{{{sel},result="discarded"}}[$__range])) or vector(0)) / clamp_min(sum(increase(ticko_ai_suggestion_reviews_total{{{sel}}}[$__range])), 1e-9)')],
            w=6, h=4, unit="percentunit")
    b.panel(STAT, "Estimated cost (range)", [q(f"sum(increase(ticko_ai_cost_usd_total{{{sel}}}[$__range]))")], w=6, h=4, unit="currencyUSD",
            options={"reduceOptions": {"calcs": ["lastNotNull"]}, "text": {}}, overrides=[], thresholds=None)
    b.panel(TS, "Suggestions by outcome", [q(f"sum by (outcome) (increase(ticko_ai_suggestions_total{{{sel}}}[5m]))", "{{outcome}}")])
    b.panel(TS, "Reviews by result and discard reason", [q(f"sum by (result, reason) (increase(ticko_ai_suggestion_reviews_total{{{sel}}}[5m]))", "{{result}} · {{reason}}")])
    b.row("Model calls")
    b.panel(TS, "Model latency p50 / p95", [
        q("histogram_quantile(0.50, sum by (le, provider) (rate(ticko_ai_llm_duration_seconds_bucket[5m])))", "p50 · {{provider}}"),
        q("histogram_quantile(0.95, sum by (le, provider) (rate(ticko_ai_llm_duration_seconds_bucket[5m])))", "p95 · {{provider}}"),
    ], unit="s")
    b.panel(TS, "Model errors by type", [q('sum by (provider, outcome) (increase(ticko_ai_llm_duration_seconds_count{outcome!="ok"}[5m])) or vector(0)', "{{provider}} {{outcome}}")])
    b.row("Usage and cost")
    b.panel(TS, "Tokens / min by tenant and type", [q(f"sum by (tenant, type) (rate(ticko_ai_tokens_total{{{sel}}}[5m])) * 60", "{{tenant}} · {{type}}")])
    b.panel(TS, "Estimated cost per tenant (USD / hour)", [q(f"sum by (tenant) (rate(ticko_ai_cost_usd_total{{{sel}}}[15m])) * 3600", "{{tenant}}")], unit="currencyUSD")
    return b


def classifier() -> Board:
    b = Board("ticko-classifier", "Ticko · Classifier", "Classifier latency and failures, model confidence, labels applied and agents' corrections.", tenant_var=True)
    b.row("Service")
    b.panel(STAT, "Calls / min", [q("sum(rate(ticko_classifier_duration_seconds_count[5m])) * 60")], w=6, h=4)
    b.panel(STAT, "Failure ratio", [q('(sum(rate(ticko_classifier_duration_seconds_count{outcome!="ok"}[15m])) or vector(0)) / clamp_min(sum(rate(ticko_classifier_duration_seconds_count[15m])), 1e-9)')],
            w=6, h=4, unit="percentunit", thresholds=[{"color": "green", "value": None}, {"color": "red", "value": 0.05}])
    b.panel(STAT, "Inference p95 (service)", [q("histogram_quantile(0.95, sum by (le) (rate(ticko_classifier_inference_duration_seconds_bucket[15m])))")], w=6, h=4, unit="s")
    b.panel(STAT, "Corrections (range)", [q('sum(increase(ticko_classifier_corrections_total{tenant=~"$tenant",set_by="ai"}[$__range]))')], w=6, h=4,
            description="Agent changes to values the AI had set.")
    b.panel(TS, "Latency p50 / p95 (API → classifier)", [
        q("histogram_quantile(0.50, sum by (le) (rate(ticko_classifier_duration_seconds_bucket[5m])))", "p50 call"),
        q("histogram_quantile(0.95, sum by (le) (rate(ticko_classifier_duration_seconds_bucket[5m])))", "p95 call"),
        q("histogram_quantile(0.95, sum by (le) (rate(ticko_classifier_inference_duration_seconds_bucket[5m])))", "p95 inference"),
    ], unit="s")
    b.panel(TS, "Calls by outcome", [q("sum by (outcome) (increase(ticko_classifier_duration_seconds_count[5m]))", "{{outcome}}")])
    b.row("Model")
    b.panel(TS, "Average confidence by task", [q("sum by (task) (rate(ticko_classifier_confidence_sum[15m])) / clamp_min(sum by (task) (rate(ticko_classifier_confidence_count[15m])), 1e-9)", "{{task}}")],
            unit="percentunit")
    b.panel(TS, "Applied vs kept as suggestion", [q("sum by (task, applied) (increase(ticko_classifier_confidence_count[15m]))", "{{task}} · applied={{applied}}")])
    b.panel("bargauge", "Labels predicted (range)", [q("sum by (task, label) (increase(ticko_classifier_confidence_count[$__range]))", "{{task}} · {{label}}", instant=True)],
            options={"orientation": "horizontal", "displayMode": "gradient"})
    b.panel(TS, "Corrections by field and who had set it", [q('sum by (field, set_by) (increase(ticko_classifier_corrections_total{tenant=~"$tenant"}[15m]))', "{{field}} · {{set_by}}")])
    return b


def main() -> None:
    OUT.mkdir(exist_ok=True)
    for board in (api(), queue(), sla(), ai(), classifier()):
        (OUT / f"{board.uid}.json").write_text(json.dumps(board.json(), indent=2, ensure_ascii=False) + "\n", encoding="utf-8")
        print(f"wrote dashboards/{board.uid}.json ({len(board.panels)} panels)")


if __name__ == "__main__":
    main()
