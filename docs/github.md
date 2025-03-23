# GitHub Actions - Workflows

## Label Sync

The `Label Sync` workflow must have read and write permissions in the repository to not fail it's run.

1. In the GitHub repository go to **Settings**.
2. In the left menu go to **Actions** > **General**.
3. Locate **Workflow permissions** and select the **Read and write permissions** option.
4. Click **Save**.
5. In the top menu go to **Actions**.
6. Select **Label Sync** and click **Run workflow**.

A new run of this action will be started and should complete without failure.

## Schemas

Set up **Cloudflare Pages** for hosting **Kubernetes CRD schemas** and integrate it with the GitHub Actions workflow.

### Create a Cloudflare Pages Project

1. Go to the [**Cloudflare Dashboard**](https://dash.cloudflare.com/).
2. Navigate to **Workers & Pages** > **Create** > **Pages**.
3. Choose **Direct Upload** instead of connecting a Git repository.
4. Set the project name to `home-kubernetes-schemas` and click **Create project**.
5. Do not upload any project assets since it will be deployed via GitHub Actions.
6. Click **Deploy site** to create the project.

### Generate API Token for GitHub Actions

Create an API token for GitHub Actions to authenticate with Cloudflare Pages.

1. Go to **Cloudflare Dashboard** > **Profile** > **API Tokens**.
2. Click **Create Token** > **Create Custom Token**.
3. Assign the following permissions:
   `Account -> Pages -> Edit`
4. Assign the following account resources:
   `Include -> Cloudflare account`
5. Click **Continue to summary** > **Create Token** and copy the generated token.

### Add Secrets to GitHub Repository

1. Go to GitHub repository.
2. Navigate to **Settings** > **Secrets and variables** > **Actions**.
3. Click **New repository secret** and add:
   `CLOUDFLARE_API_TOKEN -> Paste the API token from Cloudflare`
   `CLOUDFLARE_ACCOUNT_ID -> Find this in Cloudflare under Account Home`

### Verify Deployment

1. After the workflow runs, go to **Cloudflare Dashboard** > **Workers & Pages** and verify that assets are uploaded.
2. The latest schemas should be published to the Cloudflare Pages URL. For example:
   `https://home-kubernetes-schemas.pages.dev/helm.toolkit.fluxcd.io/helmrelease_v2.json`
3. Access and reference the schemas should now be possible using tools such as `kubeval`, `kubeconform`, or `datree`.
