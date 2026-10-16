# services/classifier — ticket classification service

**Status:** skeleton. Phase 4 put retrieval (pgvector) and reply suggestions inside the API
(`apps/api/app/Services/Ai`, `Services/Knowledge`, `Services/Suggestions`); automatic
classification (category, priority, sentiment) is still planned here.

Planned: a separate service that suggests category, priority and similar tickets using
embeddings stored in Postgres with **pgvector** (already enabled in the database image).

Ground rules for later phases:

- AI provider keys live **only** in the local `.env` (or a secret manager in production);
  never in the repository, images or logs.
- The service receives only the data it needs (ticket subject/body), always with the
  tenant id, and must never mix data from different tenants in prompts or indexes.
- The API calls it asynchronously through the queue (Horizon), so the helpdesk keeps
  working when the classifier is down.
