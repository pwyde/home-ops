# Kubernetes

These rules add to the root `AGENTS.md` for everything under `kubernetes/`: the applications in
`apps/`, the shared components in `components/` and the Flux entry point in `flux/`.

## Key Technologies

Each row names the tool for a need and what an application does to use it.

| Need        | Tool                  | Convention                                                                              |
| ----------- | --------------------- | --------------------------------------------------------------------------------------- |
| Web access  | Envoy Gateway         | A `route` to `envoy-internal`, which serves the wildcard certificate.                   |
| DNS         | external-dns          | UniFi records for every HTTPRoute, Cloudflare records only for `envoy-external`.        |
| Fixed IPs   | Cilium                | A `LoadBalancer` Service with an `lb-pool` address in `lbipam.cilium.io/ips`.           |
| Secrets     | External Secrets      | An `ExternalSecret` that reads 1Password.                                               |
| Volumes     | OpenEBS, VolSync      | The `volsync` component, or the `pvc` component without a backup.                       |
| Bulk data   | TrueNAS NFS           | A `type: nfs` volume on `nas.wyde.network`.                                             |
| PostgreSQL  | CloudNativePG         | A database on `pg18-cluster` through the `postgres-init` init container.                |
| Redis cache | Dragonfly             | `dragonfly.database.svc.cluster.local:6379`.                                            |
| Monitoring  | kube-prometheus-stack | `serviceMonitor` in the HelmRelease values and a `PrometheusRule` for alerts.           |
| Dashboards  | grafana-operator      | A `GrafanaDashboard` with `allowCrossNamespaceImport: true` and the `grafana` instance. |
| Devices     | Device plugins        | `amd.com/gpu: 1` for the GPU and `squat.ai/tun: "1"` for a VPN tunnel.                  |

## Flux Entry Point

`kubernetes/flux/cluster/ks.yaml` defines `cluster-apps`, the Flux Kustomization that applies
`kubernetes/apps/`. Its patches give every HelmRelease of every child Kustomization
`crds: CreateReplace` and a remediation with two retries, so a change there reaches every
application.

## Layout

- A new application follows a neighboring application in the same namespace, and every file in
  `app/` joins `app/kustomization.yaml`.
- `kubernetes/apps/<namespace>/kustomization.yaml` lists the `alerts` and `namespace` components,
  then each `./<app>/ks.yaml` in alphabetical order under the `# Flux-Kustomizations` comment.
- Flux Kustomizations and HelmReleases omit `metadata.namespace`. The namespace `kustomization.yaml`
  sets it, and `spec.targetNamespace` in `ks.yaml` sets the namespace of the workload.
- The Flux Kustomization of an application sets `interval: 1h`, `prune: true` and `wait: false`.
  `wait: true` belongs to the operators that other Kustomizations depend on.
- An application with a second Flux Kustomization, such as an operator and its resources, defines
  both in one `ks.yaml`. The second one has its own directory next to `app/`, such as `cluster/` or
  `config/`, and lists in `dependsOn` the Kustomization whose CRDs it uses.

## Components

`kubernetes/components/` holds the Kustomize components. Every namespace includes `alerts` and
`namespace`, so a change to them reaches every namespace, and `volsync` and `pvc` reach every
application that uses them. The cluster has no Ceph storage, so `pvc-ceph` and `volsync-ceph` stay
unused.

## Dependencies

A Flux Kustomization lists in `dependsOn` what its application needs:

| Need                    | `dependsOn` entries                                             |
| ----------------------- | --------------------------------------------------------------- |
| The `volsync` component | `openebs` in `openebs-system` and `volsync` in `volsync-system` |
| PostgreSQL              | `cloudnative-pg-cluster` in `database`                          |
| Dragonfly               | `dragonfly-cluster` in `database`                               |

## Persistent Data

- Persistent data uses the `volsync` component, as in
  `kubernetes/apps/self-hosted/karakeep/ks.yaml`.
- The `volsync` component names the PVC, the `ReplicationSource`, the `ReplicationDestination` and
  the restic repository `s3:https://s3.wyde.network/volsync/${APP}` after `APP`. In `ks.yaml`,
  `APP: *app` refers to `metadata.name: &app <app>`, and the HelmRelease mounts the claim with
  `existingClaim: "{{ .Release.Name }}"`.
- `kubernetes/components/volsync/` lists the other variables and their defaults. An application
  whose data is not owned by UID and GID 1000 sets `VOLSYNC_PUID` and `VOLSYNC_PGID` to its owner.
- A PVC without a backup uses the `pvc` component.

## HelmReleases

- Applications use the bjw-s `app-template` chart. Operators and cluster infrastructure use their
  upstream charts.
- A HelmRelease references the `OCIRepository` in `ocirepository.yaml` through `chartRef`, and the
  `OCIRepository` pins the chart by `ref.tag`. Container images use `tag@sha256:<digest>`.
- A controller that reads a Secret or ConfigMap carries `reloader.stakater.com/auto: "true"`, so
  that it restarts when they change.
- A web interface gets a `route` in the HelmRelease values, with the hostname
  `"{{ .Release.Name }}.wyde.network"` and the parent `envoy-internal` in the `networking`
  namespace.
- gatus-sidecar monitors every HTTPRoute. On a route, a `gatus.home-operations.com/endpoint`
  annotation changes the check and `gatus.home-operations.com/enabled: "false"` turns it off. A
  Service is monitored only with `gatus.home-operations.com/enabled: "true"`.

## Secrets and Databases

- An application's `ExternalSecret` in `externalsecret.yaml` has the target name `<app>-secret`,
  unless a chart or another resource expects a fixed name. It extracts the 1Password item named
  after the application, plus shared items such as `cloudflare` or `pushover`, and builds the
  environment variables in `target.template.data`.
- The owner creates 1Password items and fields. The completion report names each field that a
  change requires.
- An application that needs PostgreSQL creates its database with the `postgres-init` init container
  on `pg18-cluster`, as in `kubernetes/apps/tools/atuin/app/helmrelease.yaml`. Each application
  migrates its own schema; the repository contains no migrations.
- A PostgreSQL application also extracts the `cloudnative-pg` item and sets the variables of the
  `postgres-init` init container: `INIT_POSTGRES_HOST`
  (`pg18-cluster-rw.database.svc.cluster.local`), `INIT_POSTGRES_DBNAME`, `INIT_POSTGRES_USER`,
  `INIT_POSTGRES_PASS` and `INIT_POSTGRES_SUPER_PASS` (`{{ .POSTGRES_SUPER_PASS }}`).
- The `immich` application connects to its own cluster, `pg18-immich`, which loads the VectorChord
  extension, and has no `postgres-init` init container.
- The cluster names carry the PostgreSQL major version, so the change that replaces the clusters for
  a major upgrade also updates the names in this file.
- The `bootstrap` sections in `kubernetes/apps/database/cloudnative-pg/cluster/cluster.yaml` stay
  commented out unless the owner requests a recovery.

## Validation

Render a single Flux Kustomization or HelmRelease offline, with a directory name under
`kubernetes/apps/` as `<namespace>`:

```bash
flate build ks --namespace <namespace> --output yaml <kustomization>
flate build hr --namespace <namespace> --output yaml <helmrelease>
```
