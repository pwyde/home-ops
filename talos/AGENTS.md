# Talos Linux

These rules add to the root `AGENTS.md` for everything under `talos/`.

## Templates

| File                    | Content                                                             |
| ----------------------- | ------------------------------------------------------------------- |
| `machineconfig.yaml.j2` | Machine and cluster configuration that every node shares            |
| `networking.yaml.j2`    | Bond and DHCP configuration, also embedded in the schematic         |
| `nodes/<node>.yaml.j2`  | Node patch with the install disk, the ZFS key and the volumes       |
| `schematic.yaml.j2`     | Image Factory schematic with kernel arguments and system extensions |
| `talosconfig.j2`        | Template of the `talosctl` client configuration                     |

`just talos render-config <node>` resolves the `op://` references through `just template` and
patches `machineconfig.yaml.j2` with `networking.yaml.j2` and the node file.

`talosconfig` and the `*.iso` images in `talos/` are local outputs of
`just talos generate-talosconfig` and `just talos download-image`. Git ignores them, and agents
never edit or delete them.

## How Changes Reach the Node

- Flux does not apply `talos/`. A template change reaches the node only through
  `just talos apply-node <node>`, which reboots the node when the change requires it.
- A change to `schematic.yaml.j2` or `networking.yaml.j2` changes the schematic ID and with it
  `machine.install.image`. The node boots the new image only after the next Talos upgrade.
- Talos and Kubernetes upgrades go through tuppr, which first checks the health of the
  CloudNativePG and Dragonfly clusters. `just talos upgrade-node` and `just talos upgrade-k8s` skip
  those checks.

## Data Risks

- `just talos reset-node` wipes `STATE`, `EPHEMERAL`, `local-hostpath` and `openebs-zpool`, which
  destroys every volume on the node.
- A change to a disk selector or to a `VolumeConfig`, `UserVolumeConfig` or `RawVolumeConfig`
  requires confirmation, because these volumes hold the node data and the ZFS pool.

## Validation

The only check of a template change is `just talos render-config <node>`. Only the owner runs it,
because it prints the resolved secrets, so the completion report states that the change is
unchecked.
