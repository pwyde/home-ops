# Cloudflare

## Cloudflare Tunnel

Set up Cloudflare tunnel using the [`cloudflared`](https://github.com/cloudflare/cloudflared) Command Line Interface (CLI) tool.

Authenticate `cloudflared` to the domain.
```bash
cloudflared tunnel login
```

Create the tunnel
```bash
cloudflared tunnel create ${CLUSTER_NAME}
```

Obtain the **tunnel ID** of the newly created tunnel.
```bash
jq -r .TunnelID ~/.cloudflared/*.json
```

Retrieve the **account ID**.
```bash
jq -r .AccountTag ~/.cloudflared/*.json
```

Obtain the **tunnel secret**.
```bash
jq -r .TunnelSecret ~/.cloudflared/*.json
```
