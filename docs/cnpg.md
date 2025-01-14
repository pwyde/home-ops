# CloudNativePG

## S3 Configuration

1. Create the Minio CLI configuration file `~/.mcli/config.json`.

    ```sh
    cat <<EOF > ~/.mcli/config.json
    {
        "version": "10",
        "aliases": {
            "minio": {
                "url": "https://s3.<secret domain>",
                "accessKey": "<access key>",
                "secretKey": "<secret key>",
                "api": "S3v4",
                "path": "auto"
            }
        }
    }
    EOF
    ```

2. Create the `cloudnative-pg` bucket user and password.

    ```sh
    export BUCKET_PASSWORD="$(openssl rand -hex 20)"
    mcli admin user add minio cloudnative-pg "${BUCKET_PASSWORD}"
    ```

3. Create the bucket for CloudNativePG.

    ```sh
    mcli mb minio/cloudnative-pg
    ```

4. Create the user policy `~/.mcli/cloudnative-pg-user-policy.json`

    ```sh
    cat <<EOF > ~/.mcli/cloudnative-pg-user-policy.json
    {
        "Version": "2012-10-17",
        "Statement": [
            {
                "Action": [
                    "s3:ListBucket",
                    "s3:PutObject",
                    "s3:GetObject",
                    "s3:DeleteObject"
                ],
                "Effect": "Allow",
                "Resource": ["arn:aws:s3:::cloudnative-pg/*", "arn:aws:s3:::cloudnative-pg"],
                "Sid": ""
            }
        ]
    }
    EOF
    ```

5. Apply the bucket policies.

    ```sh
    mcli admin policy create minio cloudnative-pg-private ~/.mcli/cloudnative-pg-user-policy.json
    ```

6. Associate private policy with the `cloudnative-pg` bucket user.

    ```sh
    mcli admin policy attach minio cloudnative-pg-private --user cloudnative-pg
    ```

## Clusters

### postgres16

Primary PostgreSQL cluster on major version `16`. Hosts most application databases.

### pgvectors

Special cluster with the [`pgvecto.rs`](https://github.com/tensorchord/cloudnative-pgvecto.rs) extension. Used by applications that require a [**vector database**](https://en.wikipedia.org/wiki/Vector_database). At the moment only used by [Immich](../kubernetes/apps/self-hosted/immich/).

## Recovery

### Recover a Specific Database from a `Backup` Object

List existing `Backup` objects and get the name to perform the recovery from.

```sh
kubectl -n database get backups.postgresql.cnpg.io
NAME                       AGE    CLUSTER      METHOD              PHASE       ERROR
postgres-20240912000000    7d8h   postgres16   barmanObjectStore   completed
postgres-20240913000000    6d8h   postgres16   barmanObjectStore   completed
postgres-20240914000000    5d8h   postgres16   barmanObjectStore   completed
postgres-20240915000000    4d8h   postgres16   barmanObjectStore   completed
postgres-20240916000000    3d8h   postgres16   barmanObjectStore   completed
postgres-20240917000000    2d8h   postgres16   barmanObjectStore   completed
postgres-20240918000000    32h    postgres16   barmanObjectStore   completed
postgres-20240919000000    8h     postgres16   barmanObjectStore   completed
```

Create a new PostgreSQL cluster from `Backup` object.

```yaml
---
apiVersion: postgresql.cnpg.io/v1
kind: Cluster
metadata:
  name: postgres16-recovery
  namespace: database
spec:
  instances: 1 # Only 1 instance is needed.
  imageName: ghcr.io/cloudnative-pg/postgresql:16.4-12
  primaryUpdateStrategy: unsupervised
  storage:
    size: 20Gi
    storageClass: truenas-ssd-iscsi
  superuserSecret:
    name: cloudnative-pg
  enableSuperuserAccess: true
  postgresql:
    parameters:
      max_connections: "400"
      shared_buffers: 256MB
      idle_in_transaction_session_timeout: "300000"  # 5 min.
  resources:
    requests:
      cpu: 500m
      memory: 800Mi
    limits:
      memory: 2Gi
  monitoring:
    enablePodMonitor: false
  bootstrap:
    recovery:
      backup:
        name: postgres-20240913000000 # Name of Backup object.
```

```sh
kubectl apply -f kubernetes/apps/database/cloudnative-pg/cluster/postgres16/postgres16-recovery.yaml
```

Connect to the `postgres16-recovery` cluster.

```sh
kubectl -n database exec -it postgres16-recovery-1 -- bash
```

Dump the database into a SQL file. Note that the backup must be written into `/var/lib/postgresql/data` as the rest of the filesystem is set to **Read-Only**.

```sh
postgres@postgres16-recovery-1:/$ pg_dump -U postgres -d dbName -f /var/lib/postgresql/data/dbName_backup.sql
postgres@postgres16-recovery-1:/$ exit
```

Copy the dump from the pod to the local machine.

```sh
kubectl cp database/postgres16-recovery-1:/var/lib/postgresql/data/dbName_backup.sql ./dbName_backup.sql
```

Suspend Flux objects.

```sh
flux suspend -n namespace helmrelease appName
flux suspend -n flux-system kustomization appName
```

Delete application deployments.

```sh
kubectl -n namespace delete deployments appName
```

Get the name of the primary PostgreSQL cluster node.

```sh
kubectl-cnpg -n database status postgres16
Instances status
Name          Database Size  Current LSN  Replication role  Status  QoS        Manager Version  Node
----          -------------  -----------  ----------------  ------  ---        ---------------  ----
postgres16-1  855 MB         2A/8101FF08  Primary           OK      Burstable  1.24.0           talos-1
postgres16-3  854 MB         2A/81024E00  Standby (async)   OK      Burstable  1.24.0           talos-2
postgres16-2  854 MB         2A/81024D58  Standby (async)   OK      Burstable  1.24.0           talos-3
```

Connect to the primary PostgreSQL cluster node.

```sh
kubectl -n database exec -it postgres16-1 -- psql -U postgres
```

Drop and re-create the database.

 ```sql
DROP DATABASE dbName;
CREATE DATABASE dbName;
ALTER DATABASE dbName OWNER TO dbOwner;
\q
 ```

Restore the database from dump.

 ```sh
kubectl cp ./dbName_backup.sql database/postgres16-1:/var/lib/postgresql/data/dbName_backup.sql
kubectl -n database exec -it postgres16-1 -- psql -U postgres -d dbName -f /var/lib/postgresql/data/dbName_backup.sql
 ```

Delete the copied dump.

```sh
kubectl -n database exec -it pods/postgres16-1 -- bash
```

```sh
postgres@postgres16-1:/$ rm /var/lib/postgresql/data/dbName_backup.sql
postgres@postgres16-1:/$ exit
```

Resume Flux objects.

```sh
flux resume -n namespace helmrelease appName
flux resume -n flux-system kustomization appName
```

Delete local copy of dump.

```sh
rm ./dbName_backup.sql
```

Delete `postgres16-recovery` cluster.

```sh
kubectl delete -f kubernetes/apps/database/cloudnative-pg/cluster/postgres16/postgres16-recovery.yaml
```

Delete the released `persistentvolume`.

```
kubectl get persistentvolumes -A
NAME                                       CAPACITY   ACCESS MODES   RECLAIM POLICY   STATUS     CLAIM                                                     STORAGECLASS        VOLUMEATTRIBUTESCLASS   REASON   AGE
pvc-9946efa8-0b1f-4162-9f8d-18daf74975c3   20Gi       RWO            Retain           Released   database/postgres16-recovery-1                            truenas-ssd-iscsi   <unset>                          96m

kubectl delete persistentvolumes pvc-9946efa8-0b1f-4162-9f8d-18daf74975c3
```
