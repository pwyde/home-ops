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

## Choosing a Machine Learning CLIP Model for Smart Search

The following information is sourced from discussion [#11862](https://github.com/immich-app/immich/discussions/11862) on the official Immich GitHub [repository](https://github.com/immich-app/immich).

### Performance Metrics of Different Models

- [Monolingual model metrics](https://github.com/mlfoundations/open_clip/blob/main/docs/openclip_retrieval_results.csv)
- [Multilingual model metrics](https://github.com/mlfoundations/open_clip/blob/main/docs/openclip_multilingual_retrieval_results.csv)

### Interactive Plots

To aid in model selection, interactive plots are available, comparing quality and efficiency:

- [CLIP models: quality vs efficiency](assets/html/clip_quality_vs_efficiency.html)
- [Multilingual CLIP models: quality vs efficiency](assets/html/multilingual_clip_quality_vs_efficiency.html)
- [Multilingual CLIP models: quality by language](assets/html/multilingual_clip_quality_by_language.html)

#### Bubble Size = RAM
Bubble size represents the memory (RAM) footprint of each model. The charts do not factor in concurrency (i.e., executing multiple tasks simultaneously). A small increase in RAM usage (10–20 MB) is expected at the default concurrency setting of 2, while higher concurrency levels will result in more significant RAM consumption.

#### MACs (x-axis) = Model Speed
Multiply–Accumulate Operations (MACs) indicate the computational workload required by a model. Higher MACs correlate with longer processing times.

- **Lower MACs = Faster Model Execution**
- **Higher MACs = Slower Model Execution**

On high-performance GPUs/CPUs, processing time differences may be less noticeable.

#### Quality of the Search (y-axis)
Search quality is represented on the y-axis, where higher values indicate better search accuracy. Although models may use different datasets, the quality score provides a general performance comparison.

#### Efficiency
Model efficiency is a balance between MACs (x-axis) and quality (y-axis). More efficient models deliver higher search quality with fewer computational resources.

For example, the model `ViT-B-16-SigLIP-256__webli` requires 29.45 billion MACs and delivers a quality score of `0.767`, while the model `ViT-H-14-378-quickgelu__dfn5b` demands 542.15 billion MACs for a slightly higher quality score of `0.828`. Although the latter model offers an approximately 7% improvement in quality, the substantial increase in computational cost must be considered when determining its suitability.

## Response Time During Smart Search

By default, Immich loads the machine learning model into memory upon the first search after startup. For larger models, this may result in a delay of a few seconds. After a default idle period of 300 seconds, the model is unloaded to conserve memory.

To ensure the model remains constantly in memory, set the environment variable `MACHINE_LEARNING_MODEL_TTL: 0` in the machine learning container configuration.

To reduce initial loading time, the environment variable `MACHINE_LEARNING_PRELOAD__CLIP: <model-name>` can be used to preload the specified model during startup. Once preloaded, the model remains in memory for the entire runtime of Immich, without being unloaded due to inactivity. This behavior results in continuous RAM usage, as the model remains loaded even after periods of idleness.

Preloading models is recommended if sufficient RAM is available, as it improves search performance and reduces latency.
