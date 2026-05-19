# Immich

## PostgreSQL

**Note:** The instructions below applies to [`pgvecto.rs`](https://github.com/tensorchord/cloudnative-pgvecto.rs) extension. As of Immich `v1.133.0` the deprecated `pgvecto.rs` database extension has been replaced with its successor [VectorChord](https://github.com/tensorchord/VectorChord). See the [release notes](https://github.com/immich-app/immich/releases/tag/v1.133.0) for more information.

The Immich database is hosted in a dedicated PostgreSQL [cluster](../kubernetes/apps/database/cloudnative-pg/cluster/pg17-immich.yaml).

By default, Immich requires superuser access to the PostgreSQL database, as it depends on certain extensions that must be installed. To simplify installation and allow seamless updates, especially during major version updates, Immich is granted superuser privileges within the cluster.

```sql
ALTER USER immich WITH SUPERUSER;
```

For more information, see the Immich [documentation](https://immich.app/docs/administration/postgres-standalone).

### Without Superuser Permission

While Immich can be configured to run without superuser permissions, this approach may require manual intervention during updates and is recommended only for **advanced users**.

To configure Immich without superuser access, adjustments can be made during the bootstrap process by updating the [`pg17-immich.yaml`](../kubernetes/apps/database/cloudnative-pg/cluster/pg17-immich.yaml) file as follows:

```yaml
postgresql:
    shared_preload_libraries:
      - "vectors.so"
    parameters:
      # https://github.com/tensorchord/cloudnative-pgvecto.rs/issues/29
      search_path: '"$user", public, vectors'
bootstrap:
  initdb:
    database: immich
    owner: immich
    secret:
      name: immich-db
    postInitApplicationSQL:
      # List of SQL queries to be executed as superuser in the application
      # database right after it is created.
      - CREATE EXTENSION IF NOT EXISTS cube;
      - CREATE EXTENSION IF NOT EXISTS vectors;
      - CREATE EXTENSION IF NOT EXISTS earthdistance CASCADE;
      # Disabled. See 'search_path' parameter above.
      # - ALTER DATABASE immich SET search_path TO "$user", public, vectors;
      - GRANT ALL ON SCHEMA vectors TO immich;
      - GRANT SELECT ON TABLE pg_vector_index_stat to immich;
```

With CloudNativePG version `1.22` and later, the use of the `ALTER SYSTEM` command is disabled by default in new PostgreSQL clusters due to the associated risks. Instead, the `search_path` parameter must be set via the `postgresql` configuration. For further details, refer to the `cloudnative-pgvecto.rs` issue [#29](https://github.com/tensorchord/cloudnative-pgvecto.rs/issues/29) and the CloudNativePG [documentation](https://cloudnative-pg.io/documentation/1.22/postgresql_conf/#enabling-alter-system).

## Response Time During Smart Search

By default, Immich loads the machine learning model into memory upon the first search after startup. For larger models, this may result in a delay of a few seconds. After a default idle period of 300 seconds, the model is unloaded to conserve memory.

To ensure the model remains constantly in memory, set the environment variable `MACHINE_LEARNING_MODEL_TTL: 0` in the machine learning container configuration.

To reduce initial loading time, the environment variable `MACHINE_LEARNING_PRELOAD__CLIP: <model-name>` can be used to preload the specified model during startup. Once preloaded, the model remains in memory for the entire runtime of Immich, without being unloaded due to inactivity. This behavior results in continuous RAM usage, as the model remains loaded even after periods of idleness.

Preloading models is recommended if sufficient RAM is available, as it improves search performance and reduces latency.
