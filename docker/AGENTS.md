# Docker Compose Stacks

These rules add to the root `AGENTS.md` for everything under `docker/`.

## Deployment Chain

| Component                       | Deployed by                                                                      | Compose file                                  |
| ------------------------------- | -------------------------------------------------------------------------------- | --------------------------------------------- |
| `doco-cd-updater`               | The TrueNAS SCALE UI, and `cron.sh` after a change to its compose file on `main` | `docker/.doco-cd/docker-compose.updater.yaml` |
| `doco-cd`                       | `doco-cd-updater`, which polls `main` hourly through `.doco-cd.updater.yaml`     | `docker/.doco-cd/docker-compose.app.yaml`     |
| Each stack in `docker/truenas/` | `doco-cd`, which polls `main` hourly through `docker/truenas/.doco-cd.yaml`      | `docker/truenas/<stack>/docker-compose.yml`   |

- A change under `docker/.doco-cd/` requires confirmation, because a broken Doco-CD instance stops
  every later deployment and needs a manual repair on TrueNAS.
- `cron.sh` runs from a copy on TrueNAS and fetches only `docker-compose.updater.yaml`, so a change
  to `cron.sh` reaches TrueNAS only by hand.

## Stacks

- Auto-discovery treats each directory in `docker/truenas/` as one stack and deletes a stack whose
  directory disappears. A renamed directory therefore removes the old stack and creates a new one.
- A directory name starts with a number, so that a stack sorts after the stacks that it needs:
  `01-traefik` creates the `apps` network, which later stacks join with `external: true`.
- A web interface gets Traefik labels with a `Host()` rule on `wyde.network`, the `https`
  entrypoint and TLS, as in `docker/truenas/03-rustfs/docker-compose.yml`.
- Images use the full registry path and a version tag, without a digest.

## Secrets

A compose file reads a secret as `${VARIABLE}`. The `external_secrets` map in
`docker/truenas/.doco-cd.yaml` resolves each variable from an `op://` reference, and the owner
creates the 1Password field.

## Data Risks

`03-rustfs` serves `s3.wyde.network`, which stores the VolSync and CloudNativePG backups of the
cluster, and `01-traefik` routes it. A change to either stack requires confirmation, because a
failure of either stops every backup.
