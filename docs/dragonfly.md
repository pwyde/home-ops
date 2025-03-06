# Dragonfly

## Database Index

1. `null`
2. [`authentik`](../kubernetes/apps/security/authentik/)
3. [`immich`](../kubernetes/apps/self-hosted/immich/)
4. [`searxng`](../kubernetes/apps/self-hosted/searxng/)
5. [`nextcloud`](../kubernetes/apps/self-hosted/nextcloud/)

## Arguments

### Undeclared Keys

Undeclared keys are enabled in the cluster using the `--default_lua_flags=allow-undeclared-keys` argument. This configuration is generally not recommended and may lead to suboptimal performance. By default, Dragonfly disables access to undeclared keys in scripts for this reason. However, [Immich](../kubernetes/apps/self-hosted/immich/) uses Redis through BullMQ to manage job queues, and therefore requires undeclared keys to be allowed.

For more information, refer to the [Dragonfly documentation](https://www.dragonflydb.io/docs/integrations/bullmq#using-undeclared-keys-not-optimized) and the [Immich documentation](https://immich.app/docs/developer/architecture#redis).
