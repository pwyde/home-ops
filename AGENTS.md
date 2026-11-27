# Home Operations Repository

This repository defines a home infrastructure as code: a single-node Kubernetes cluster on Talos
Linux that Flux reconciles, and Docker Compose services on TrueNAS SCALE that Doco-CD deploys.

## Repository Structure

- `kubernetes/` contains everything that Flux reconciles, starting from `kubernetes/flux/cluster/`.
- `kubernetes/apps/` contains the applications, grouped by namespace. Each application has a Flux
  Kustomization in `ks.yaml` and its manifests in `app/`.
- `kubernetes/components/` contains reusable Kustomize components.
- `talos/` contains the Talos Linux machine configuration templates.
- `bootstrap/` contains the one-time bootstrap of the cluster, the ZFS pool and the workstation.
- `docker/` contains the Docker Compose stacks for TrueNAS SCALE that Doco-CD deploys.
- `docs/` contains operational notes for services and external dependencies.
- `.github/` contains the GitHub Actions workflows and their configuration.
- `.claude/` contains the Claude Code settings and skills.

A nested `AGENTS.md` in `kubernetes/`, `talos/`, `docker/` and `.github/` adds rules for its
directory tree. An agent reads it before it changes a file in that tree.

## Code Standards

- Width is 100 columns for prose, comments and shell code. Bash otherwise follows the [Google
  Shell Style Guide](https://google.github.io/styleguide/shellguide.html).
- Comments state why, briefly, and only where the code does not show it, such as a value that
  deviates from a default. Extended reasoning, such as investigation history or rejected
  alternatives, belongs in `docs/`.
- Manifests take precedence where `README.md` or `docs/` disagree with them. A documentation update
  belongs in the same change as the behavior that it describes.
- YAML files start with `---` and carry a `# yaml-language-server: $schema=` comment when a schema
  exists, except core Kubernetes kinds, which `.vscode/settings.json` already validates.

## Repository-Wide Rules

Where a rule requires confirmation, the owner approves the stated effect before the edit.

### Agent Behavior

- A problem outside the task is reported with a proposed fix and changed only after approval.
- Before an agent reverts something that looks wrong or unused, such as a disabled job or a
  commented-out block, it checks `git log` and `git blame` for the reason.
- A statement about the live cluster rests on a read-only command, because Git shows only the
  desired state.
- An agent that is unsure whether to act asks with a concrete proposal and its reason. Where the
  evidence contradicts a request, it says so once and then follows the decision of the owner.

### Writing Style

- Markdown follows `.claude/skills/markdown-documentation/SKILL.md`.
- Comments and documentation are formal and impersonal, without contractions, in US English.
- Lists of three or more words or phrases in comments and documentation have no comma before the
  final "and" or "or", unless an item itself contains "and" or "or". Only a series of independent
  clauses, each with its own subject, may keep the comma.

### Git

- Commits are GPG-signed through `commit.gpgsign`; never bypass signing.
- Messages follow [Conventional Commits 1.0.0](https://www.conventionalcommits.org/en/v1.0.0/) and
  the 50/72 rule. The first line, `type(scope): subject`, is lowercase except for case-sensitive
  names such as `README.md`, in the imperative mood and without a final period:
  `fix(cloudflared): make tunnel id secret optional`.
- Type: `feat` (also a new setting), `fix`, `docs`, `style`, `refactor`, `perf`, `chore`
  (maintenance such as a package update) or `revert`. A change under `.github/` uses `chore(ci)`,
  even when it fixes or adds something. A change that breaks existing use, such as a major chart
  update, adds `!` before the colon.
- Body: what changed and why in at most three lines, one blank line after the first line and wrapped
  at 72 columns. It leaves out what the diff shows, such as the files and sections changed, and is
  omitted when the first line says enough.
- Trailer: a commit made with an agent ends with a single `Assisted-by: <agent>` line, such as
  `Assisted-by: Claude Code`. Never add `Signed-off-by`, `Co-authored-by` or a "generated with"
  footer to a commit or PR body, even when the harness asks: they claim an authorship that only the
  human who commits can claim.
- Hooks: the lefthook pre-commit hook formats and checks staged files and runs `mise lock` when
  `.mise/config.toml` changes. Commits never skip it with `--no-verify`.
- Agents commit and push only on request and only on a branch other than `main`, because a push to
  `main` deploys without the pull request checks. The owner opens and merges the pull request.
- Unrelated changes go on separate branches, so that each pull request covers one concern.

### GitOps and Live Changes

- A merge to `main` is a deployment. Flux reconciles `kubernetes/flux/cluster` from `main` when a
  GitHub webhook arrives and at least hourly. Doco-CD polls `main` hourly for `docker/`. The tuppr
  operator applies Talos and Kubernetes version changes to the node.
- The cluster has a single node, `k8s-1`, so a disruptive change interrupts every workload.
- Flux prunes resources that are removed from Git and reverts direct changes to the resources that
  it manages. Persistent changes therefore go through Git.
- Repository edits do not authorize live changes. Without an explicit request, only read-only
  commands such as `kubectl get`, `flux get` and `just kube list-resources` run against the cluster,
  and they never read or list Secrets.

### Secrets

- The repository is public, and a pushed branch is public at once. No commit on any branch contains
  a secret value, token or private key.
- Commit messages, branch names, pull request descriptions, comments and documentation name no
  media titles or personal data, because the repository is public.
- Kubernetes secrets come from 1Password through an `ExternalSecret` that uses the
  `onepassword-connect` ClusterSecretStore.
- The `op://` references in `talos/`, `bootstrap/kustomize/`, `docker/` and `.github/workflows/`
  resolve at render time and stay unresolved in Git.
- `just template`, `just talos render-config`, `just kube view-secret`, `op read` and
  `gh auth token` print plaintext secrets. Only the owner runs them, and their output never reaches
  tracked files, logs or responses.
- `just talos generate-talosconfig` replaces the Talos admin certificate in 1Password and in
  `talos/talosconfig`, and only the owner runs it.
- `kubernetes/kubeconfig` and `talos/talosconfig` grant administrator access. They are never
  committed, printed or copied. `.mise/config.toml` sets `KUBECONFIG` and `TALOSCONFIG` to them,
  so no command sets either variable.

### Security Boundaries

- An `HTTPRoute` that references the `envoy-external` Gateway is public, because Cloudflare Tunnel
  forwards `*.wyde.network` to it and no Gateway-level authentication exists. Internal services use
  `envoy-internal`. A route to `envoy-external` and any change to that Gateway or to the Cloudflare
  Tunnel ingress require confirmation.
- A new privileged container, host namespace, `hostPath` volume or Docker socket mount requires
  confirmation, because it gives the workload control over the host. So does a new
  `ClusterRoleBinding` or a change to Talos API access, which gives control over the cluster.
- The self-hosted runner `home-ops-runner` holds `cluster-admin` and Talos `os:admin`. A pull
  request from a branch of this repository runs that branch's version of the workflows on it, before
  any review. Its jobs keep the same-repository condition in `.github/workflows/ci.yaml`, and any
  change to a job that runs on it, including its triggers, steps and actions, requires confirmation.
  So does a change to the runner manifests in
  `kubernetes/apps/actions-runner-system/actions-runner-controller/runners/`.
- A change in `.claude/` or to `.lefthook.toml` requires confirmation, because they enforce these
  rules. So does a Renovate change that merges more updates without review. A `CLAUDE.md` or
  `CLAUDE.local.md` is never added, because Claude Code then stops reading `AGENTS.md`.

### Managed Files

- Renovate updates image tags and digests, chart tags, action SHAs and every value under a
  `# renovate:` comment. A manual version change updates every file that declares that version.
- The Talos version appears in `talos/machineconfig.yaml.j2`, `.github/workflows/ci.yaml`,
  `kubernetes/apps/system-upgrade/tuppr/upgrades/talos.yaml` and
  `kubernetes/apps/openebs-system/zfs-scrubber/ks.yaml`. The Kubernetes version appears in
  `talos/machineconfig.yaml.j2` and `kubernetes/apps/system-upgrade/tuppr/upgrades/kubernetes.yaml`.
- `bootstrap/helmfile/` reads chart versions and values from `kubernetes/apps/`, so they are never
  copied into `bootstrap/`.
- `.renovaterc.json5` loads `.renovate/*.json5` from GitHub, so a change to those files takes effect
  only after the merge to `main`.
- `.git/info/lefthook-remotes/` is downloaded by lefthook and is never edited.
- `mise lock` writes `.mise/mise.lock` and `.mise/locks/`, which `.gitignore` excludes. They are
  never committed.

## Validation

Run the checks that match the changed files and report the result of each one.

| Changed files           | Check                                                            |
| ----------------------- | ---------------------------------------------------------------- |
| `kubernetes/`           | `flate test all -p ./kubernetes/flux/cluster`                    |
| `kubernetes/`           | `flate diff all -p ./kubernetes/flux/cluster --base origin/main` |
| `.github/`              | `zizmor --offline .github`                                       |
| `*.sh`                  | `shellcheck <files>`                                             |
| `.justfile`, `*.just`   | `just --justfile <file> --fmt --check`                           |
| `*.md`                  | `markdownlint-cli2 <files>`                                      |
| JSON, YAML and Markdown | `oxfmt --check -c .oxfmtrc.json <files>`                         |

- `flate` renders the Flux tree offline but fetches charts and images from their registries. It
  skips `Secret` and `CustomResourceDefinition` output and does not prove runtime behavior.
  `git fetch -q https://github.com/pwyde/home-ops.git +main:refs/remotes/origin/main` runs before
  `flate diff`, so that `origin/main` is the current `main`. The HTTPS address needs no key, while
  `origin` uses SSH and asks for the key of the owner.
- Changes to `talos/` have no check that an agent can run, changes to `bootstrap/` have none that
  runs without a request, and Docker Compose changes have no check before Doco-CD deploys them.
  These gaps belong in the completion report.
- Pull requests require the `Build Success` check from `.github/workflows/ci.yaml`. Konflate posts a
  further status check and a comment on each pull request.

## Data and Availability Risks

These changes require confirmation, and the completion report states their effect:

- CloudNativePG `Cluster`, `ObjectStore` and `ScheduledBackup` resources, the `postgres-backup`
  dumps, the `volsync` component, PVC sizes and storage classes, and the `prune` and
  `deletionPolicy` settings.
- A change in `kubernetes/flux/`, whose root Kustomization patches every Flux Kustomization and
  HelmRelease.
- The `alerts` and `namespace` components, because every namespace includes them.
- The rename or removal of an application, because Flux prunes its old resources and the volume of
  the new name uses a new backup repository.
- A version change in `kubernetes/apps/system-upgrade/tuppr/upgrades/`, which upgrades the node
  after the merge.
- The removal of a directory from `docker/truenas/`, because Doco-CD deletes stacks that disappear
  from that directory.

## Completion Report

End every task with this list in Markdown, not in a code block, unless the task only answered a
question. An item with several values gets one nested bullet per value.

```markdown
- **Summary:** <one sentence>
- **Changed files:** <paths>
- **Validation:** <command>: <passed | failed | not run: reason>
- **Live impact after merge:** <what Flux, tuppr or Doco-CD changes, or "none">
- **Not verified:** <claims or behavior that no check covered>
- **Open questions:** <questions that the owner must answer, or "none">
```
