# syntax=docker/dockerfile:1.7
#
# Ticko classifier. Build context: services/classifier.
#
#   deps    -> runtime dependencies, installed from the hash-locked file
#   dev     -> + training/evaluation/test tools (make classifier-*)
#   model   -> the pinned base model, every file checked against its SHA-256
#   slim    -> the runtime venv without pip, headers or test suites
#   runtime -> minimal image: no compiler, no pip, non-root, read-only friendly

ARG PYTHON_IMAGE=python:3.12-slim-bookworm@sha256:34386ef0cb081344d7ec1c103ba398e6e9f64e9ab3a1509accc92a4e24a07258

FROM ${PYTHON_IMAGE} AS deps
ENV PIP_DISABLE_PIP_VERSION_CHECK=1 PIP_NO_CACHE_DIR=1 PYTHONDONTWRITEBYTECODE=1
RUN python -m venv /opt/venv
COPY requirements.lock /tmp/requirements.lock
RUN /opt/venv/bin/pip install --require-hashes --no-deps --only-binary=:all: -r /tmp/requirements.lock

# ---------------------------------------------------------------------------
FROM deps AS dev
ARG UID=1000
ARG GID=1000
COPY requirements-dev.lock /tmp/requirements-dev.lock
RUN /opt/venv/bin/pip install --require-hashes --no-deps --only-binary=:all: -r /tmp/requirements-dev.lock \
    && groupadd -g "${GID}" ticko && useradd -m -u "${UID}" -g ticko ticko
ENV PATH=/opt/venv/bin:$PATH PYTHONPATH=/src TICKO_BASE_MODEL_DIR=/models/bertimbau-base
WORKDIR /src
USER ticko

# ---------------------------------------------------------------------------
FROM deps AS model
COPY ticko_classifier/__init__.py ticko_classifier/fetch_model.py /tmp/fetch/ticko_classifier/
RUN cd /tmp/fetch && /opt/venv/bin/python -m ticko_classifier.fetch_model --dir /opt/models/bertimbau-base

# ---------------------------------------------------------------------------
# What the service needs from the environment: no pip, no C++ headers, no test suite.
FROM deps AS slim
RUN cd /opt/venv/lib/python3.12/site-packages \
    && rm -rf /opt/venv/bin/pip* pip pip-* torch/include torch/test

# ---------------------------------------------------------------------------
FROM ${PYTHON_IMAGE} AS runtime
ENV PATH=/opt/venv/bin:$PATH \
    PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    TICKO_BASE_MODEL_DIR=/opt/models/bertimbau-base \
    HF_HUB_OFFLINE=1 \
    TRANSFORMERS_OFFLINE=1 \
    HOME=/tmp \
    HF_HOME=/tmp/hf
# No package manager tooling in the final image.
RUN python -m pip uninstall -y pip setuptools wheel >/dev/null 2>&1 || true \
    && groupadd -g 10001 classifier && useradd -u 10001 -g classifier -M -s /usr/sbin/nologin classifier
COPY --from=slim /opt/venv /opt/venv
COPY --from=model /opt/models /opt/models
WORKDIR /app
COPY ticko_classifier ./ticko_classifier
COPY models ./models
USER classifier
EXPOSE 8000
HEALTHCHECK --interval=15s --timeout=3s --start-period=60s --retries=5 \
    CMD ["python", "-c", "import urllib.request,sys; sys.exit(0 if urllib.request.urlopen('http://127.0.0.1:8000/health', timeout=2).status == 200 else 1)"]
# The factory refuses to start without CLASSIFIER_TOKEN.
CMD ["uvicorn", "--factory", "ticko_classifier.service:create_app", "--host", "0.0.0.0", "--port", "8000", "--no-access-log"]
