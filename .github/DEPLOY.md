# Deployment Setup

## GitHub Actions - Deploy to Dokploy

### Required Secrets

Go to **Settings → Secrets and variables → Actions → New repository secret**

Add these secrets:

1. **DOKPLOY_WEBHOOK_URL**
   - Get this from Dokploy: Project → Settings → Webhooks
   - Example: `https://your-dokploy.com/api/webhook/deploy/abc123`

2. **DOKPLOY_TOKEN** (if required)
   - Get this from Dokploy API settings
   - If Dokploy doesn't require auth, you can skip this

### How It Works

1. **Push to `demo` branch** → Triggers deployment
2. GitHub Actions builds Docker image
3. Pushes to GitHub Container Registry (ghcr.io)
4. Triggers Dokploy webhook
5. Dokploy pulls and deploys the new image

### Workflow

```bash
# Create demo branch
git checkout -b demo
git push -u origin demo

# Make changes
git add .
git commit -m "Update feature"
git push origin demo  # ← This triggers deployment
```

### Image Tags

- `ghcr.io/roelpaulo/backender:demo` - Latest demo version
- `ghcr.io/roelpaulo/backender:demo-{sha}` - Specific commit

### Make Repository Public

For GHCR images to be accessible by Dokploy without authentication:

1. Go to: https://github.com/roelpaulo/backender/settings
2. Scroll to **Danger Zone**
3. Click **Change visibility** → Make public

OR configure Dokploy with GitHub token to pull private images.

### Alternative: Direct Dokploy Deployment

If Dokploy has GitHub integration:

1. Connect Dokploy to your GitHub repo
2. Set branch to `demo`
3. Enable auto-deploy on push
4. No GitHub Actions needed!

### Troubleshooting

**Error: "unauthorized: access forbidden"**
- Make sure repository is public, or
- Configure Dokploy with GitHub Container Registry credentials

**Webhook fails:**
- Check DOKPLOY_WEBHOOK_URL is correct
- Verify webhook is enabled in Dokploy
- Check Dokploy logs

**Image not updating:**
- Dokploy might be caching
- Try manual redeploy in Dokploy UI
- Check image tag matches

### Monitoring

View deployments:
- GitHub: Actions tab → Deploy to Dokploy (Demo)
- Dokploy: Project → Deployments

### Rollback

```bash
# Deploy specific version
docker pull ghcr.io/roelpaulo/backender:demo-{previous-sha}
```

Or use Dokploy UI to rollback to previous deployment.
