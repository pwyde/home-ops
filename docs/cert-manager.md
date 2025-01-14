# cert-manager

## Introduction

[**cert-manager**](https://cert-manager.io) is used to manage certificates inside the cluster. It provides CRDs for automated requests of Let's Encrypt certificates and also automatically renews them before expiration.

### Example:

```yaml
apiVersion: cert-manager.io/v1
kind: Certificate
metadata:
  name: auth.${SECRET_DOMAIN}
  namespace: authelia
spec:
  secretName: auth.${SECRET_DOMAIN}
  dnsNames:
    - auth.${SECRET_DOMAIN}
  issuerRef:
    name: lets-encrypt-production
    kind: ClusterIssuer
```

## Created Resources

| Kind                                  | Name                                              |
| ------------------------------------- | ------------------------------------------------- |
| [`Namespace`][ref-namespace]          | `security`                                        |
| [`HelmRelease`][ref-helm-release]     | `cert-manager`                                    |
| [`Secret`][ref-secret]                | `cert-manager-cloudflare`                         |
| [`ClusterIssuer`][ref-cluster-issuer] | `lets-encrypt-production`, `lets-encrypt-staging` |

[ref-namespace]: https://kubernetes.io/docs/reference/kubernetes-api/cluster-resources/namespace-v1/
[ref-helm-release]: https://fluxcd.io/docs/components/helm/helmreleases/
[ref-secret]: https://kubernetes.io/docs/reference/kubernetes-api/config-and-storage-resources/secret-v1/
[ref-cluster-issuer]: https://cert-manager.io/docs/reference/api-docs/#cert-manager.io/v1.ClusterIssuer

## CLI

`cert-manager` has a great CLI tool to interact with the controller running inside the cluster. The installation guide can be found [here](https://cert-manager.io/docs/usage/cmctl/#installation).

### Examples:

#### Manually renew certificate(s):
```shell
$ cmctl renew <certificate>
```
More information about this command can be found [here](https://cert-manager.io/docs/usage/cmctl/#renew).

#### Get the status of a certificate:
```shell
$ cmctl status certificate -n <namespace> <certificate>
```
More information about this command can be found [here](https://cert-manager.io/docs/usage/cmctl/#status-certificate).

## References

- [Documentation](https://cert-manager.io/docs/)
- [GitHub Repository](https://github.com/cert-manager/cert-manager)
