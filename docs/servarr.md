# Servarr

## Migrating Data from SQLite to PostgreSQL Databases

This guide will use **Radarr** as an example. Same procedure can be applied to all the **Arr** applications.

- **Lidarr**
- **Prowlarr**
- **Radarr**
- **Sonarr**

Upgrade **Radarr** to at least `v4.1.0.6133` or newer. This brings in support for **PostgreSQL**. This will also ensure that all of the **SQLite** tables have the latest schema migrations applied before migration to the PostgreSQL cluster.

Copy the SQLite databases from the Radarr container/pod, both the **main** database and **logs** database. This guide will assume the files are named `radarr.db` and `logs.db`.

Deploy Radarr in the Kubernetes cluster. Ensure that all schema migrations have been applied using the container/pod logs.

Restart Radarr using the web UI and double-check that connection to PostgreSQL can be re-established and that no other schema migrations is performed. Check the container/pod logs for this

Remove Radarr deployment in the Kubernetes cluster.

Dump the schema for the **main** and **logs** databases using `pg_dump` with the default superuser login.

```sh
pg_dump -h postgres.${SECRET_DOMAIN} -U postgres -s -d radarr_main -f ${HOME}/radarr_main.sql
pg_dump -h postgres.${SECRET_DOMAIN} -U postgres -s -d radarr_log -f ${HOME}/radarr_log.sql
```

In order to reset all tables to a clean starting point before importing the SQLite data, drop and re-create the databases on the PostgreSQL cluster. Using `psql` with the default superuser login.

```sh
psql -h postgres.${SECRET_DOMAIN} -U postgres
```

```sql
DROP DATABASE "radarr_main";
DROP DATABASE "radarr_log";
CREATE DATABASE "radarr_main";
CREATE DATABASE "radarr_log";
ALTER SCHEMA public OWNER TO radarr;
ALTER DATABASE "radarr_main" OWNER TO radarr;
ALTER DATABASE "radarr_log" OWNER TO radarr;
\q
```

The databases are now re-created. Now the schema must be re-imported that was dumped earlier.

```sh
psql -h postgres.${SECRET_DOMAIN} -U radarr -d radarr_main -f ${HOME}/radarr_main.sql
psql -h postgres.${SECRET_DOMAIN} -U radarr -d radarr_log -f ${HOME}/radarr_log.sql
```

Now with the schema re-imported and all tables clean, the SQLite databases can be migrated using `pgloader`.

```sh
docker run --rm -v ${HOME}/radarr.db:/radarr.db:ro --network=host ghcr.io/roxedus/pgloader --with "quote identifiers" --with "data only" /radarr.db "postgresql://radarr:${RADARR__POSTGRES__PASSWORD}@postgres.${SECRET_DOMAIN}/radarr_main"
docker run --rm -v ${HOME}/logs.db:/logs.db:ro --network=host ghcr.io/roxedus/pgloader --with "quote identifiers" --with "data only" /logs.db "postgresql://radarr:${RADARR__POSTGRES__PASSWORD}@postgres.${SECRET_DOMAIN}/radarr_log"
```

**Note:** If `pgloader` generate errors it could be due to the database being too large, to resolve this try adding `--with "prefetch rows = 100" --with "batch size = 1MB"` to the command above.

Re-deploy Radarr in the Kubernetes cluster.

#### References
- [Main migration article from Servarr Wiki](https://wiki.servarr.com/radarr/postgres-setup)
- [Migrate Radarr from SQLite to Postgres](https://gist.github.com/tobz/929fd4ad8da80ac2ce524af73d4ea615)

### Bazarr

**Bazarr** is not officially part of the **Arr** application suite, hence the procedure to migrate data from SQLite to PostgreSQL database differs.

Before starting a migration please ensure that Bazarr has run against the previously created PostgreSQL database at least once successfully.

Remove Bazarr deployment in the Kubernetes cluster.

Connect to the Bazarr database using `psql` and prepare the it for data migration.

```sh
psql -h postgres.${SECRET_DOMAIN} -U bazarr
```

```
DELETE FROM "system" WHERE 1=1;
DELETE FROM "table_settings_languages" WHERE 1=1;
DELETE FROM "table_settings_notifier" WHERE 1=1;
\q
```

SQLite databases can now be migrated using `pgloader`.

```sh
docker run --rm -v ${HOME}/bazarr.db:/bazarr.db --network=host ghcr.io/roxedus/pgloader --with "quote identifiers" --with "data only" --cast "column table_blacklist.timestamp to timestamp" --cast "column table_blacklist_movie.timestamp to timestamp" --cast "column table_history.timestamp to timestamp" --cast "column table_history_movie.timestamp to timestamp" /bazarr.db "postgresql://bazarr:${BAZARR__POSTGRES__PASSWORD}@postgres.${SECRET_DOMAIN}/bazarr"
```

Re-deploy Bazarr in the Kubernetes cluster.

#### References
- [Main migration article from Bazarr Wiki](https://wiki.bazarr.media/Additional-Configuration/PostgreSQL-Database/)
