# TrueNAS SCALE

## Reporting

As of TrueNAS SCALE 23.10 the reporting system was changed to [Netdata](https://github.com/netdata/netdata). The built-in reporting mechanism is limited to [Graphite](https://grafana.com/oss/graphite/) time-series database.

[truenas-graphite-to-prometheus](https://github.com/Supporterino/truenas-graphite-to-prometheus) maps the Graphite metrics format to Prometheus.

### Deploy Graphite Exporter

1. In the TrueNAS admin web UI go to **Apps** > **Discover Apps** > **Custom App**.
2. Install application (container) with the following options:
    - Application Name: `graphite-exporter`
    - Repository: `ghcr.io/supporterino/truenas-graphite-to-prometheus`
    - Tag: `latest`
    - Pull Policy: `Pull the image if it is not already present on the host`
    - Restart Policy: `Always`
    - Ports:
      - Container Port: `9109`
      - Host Port: `9109`
      - Protocol: `TCP`
      - Container Port: `9108`
      - Host Port: `9108`
      - Protocol: `TCP`

### Create Graphite Reporting Exporter

1. In the TrueNAS admin web UI go to **Reporting** > **Exporters** > **Add**.
2. Create reporting exporter with the following configuration:
    - Name: `Graphite Reporting Exporter`
    - Type: `GRAPHITE`
    - Enabled: `true`
    - Destination IP: `localhost`
    - Destination Port: `9109`
    - Prefix: `truenas`
    - Namespace: `nas`
    - Update Every: `15`
    - Buffer On Failures: `10`
    - Send Names Instead Of IDs: `true`
    - Matching Charts: `*`

### Create Scrape Configuration in Prometheus

Create the following scrape in Prometheus.

```yaml
apiVersion: monitoring.coreos.com/v1alpha1
kind: ScrapeConfig
metadata:
  name: &name graphite-exporter
spec:
  staticConfigs:
    - targets:
        - nas.${SECRET_DOMAIN}:9108
  metricsPath: /metrics
  relabelings:
    - action: replace
      targetLabel: job
      replacement: *name

```
