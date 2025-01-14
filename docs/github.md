# GitHub Actions - Workflows

## Sync Labels

The `Sync Labels` workflow must have read and write permissions in the repository to not fail it's run.

1. In the GitHub repository go to **Settings**.
2. In the left menu go to **Actions** > **General**.
3. Locate **Workflow permissions** and select the **Read and write permissions** option.
4. Click **Save**.
5. In the top menu go to **Actions**.
6. Select **Sync Labels** and click **Run workflow**.

A new run of this action will be started and should complete without failure.
