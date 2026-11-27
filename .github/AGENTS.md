# GitHub Actions

These rules add to the root `AGENTS.md` for everything under `.github/`.

## Workflows

- Beyond the zizmor audits, an action SHA carries a version comment, every job sets its own
  `permissions` and a GitHub App token requests only the `permission-*` inputs that its step needs.
- A new job in `.github/workflows/ci.yaml` joins the `needs` list of the `status` job.
