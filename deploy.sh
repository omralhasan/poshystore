#!/bin/bash
# ============================================================
# Poshy Store - One-Command Deploy Script
# Usage:  ./deploy.sh "Your commit message"
# ============================================================

set -e

WEBHOOK_URL="http://159.223.180.154/deploy_webhook.php?token=poshy_deploy_2026_secure"
REPO_DIR="/home/omar/poshystore"
REMOTE_NAME="origin"
BRANCH="main"

# ─── Colors ──────────────────────────────────────────────────
RED='\033[0;31m'; GREEN='\033[0;32m'; YELLOW='\033[1;33m'
CYAN='\033[0;36m'; BOLD='\033[1m'; NC='\033[0m'

echo -e "${CYAN}${BOLD}╔══════════════════════════════════════╗${NC}"
echo -e "${CYAN}${BOLD}║      Poshy Store Auto Deploy         ║${NC}"
echo -e "${CYAN}${BOLD}╚══════════════════════════════════════╝${NC}"

cd "$REPO_DIR"

# ─── Step 1: Check for commit message ───────────────────────
COMMIT_MSG="${1:-Auto deploy $(date '+%Y-%m-%d %H:%M')}"
echo -e "\n${BOLD}📝 Commit message:${NC} $COMMIT_MSG"

# ─── Step 2: Check git status ───────────────────────────────
CHANGED=$(git status --short | wc -l)
if [ "$CHANGED" -eq 0 ]; then
    echo -e "${YELLOW}⚠  No local changes to commit.${NC}"
    echo -e "${BOLD}→ Triggering deploy on production anyway...${NC}"
else
    echo -e "\n${BOLD}📦 Staging all changes ($CHANGED files)...${NC}"
    git add -A
    git status --short

    # ─── Step 3: Commit ─────────────────────────────────────
    echo -e "\n${BOLD}✍  Committing...${NC}"
    git commit -m "$COMMIT_MSG"
fi

# ─── Step 4: Push to GitHub ─────────────────────────────────
echo -e "\n${BOLD}🚀 Pushing to GitHub...${NC}"

# Check if PAT is configured
REMOTE_URL=$(git remote get-url origin)
if [[ "$REMOTE_URL" == *"@"* ]]; then
    # PAT already embedded in URL
    git push origin "$BRANCH"
else
    # Try push — if it fails, show setup instructions
    if ! git push origin "$BRANCH" 2>/dev/null; then
        echo -e "${RED}✗ GitHub push failed!${NC}"
        echo -e "\n${YELLOW}To fix this, run the setup script once:${NC}"
        echo -e "  ${CYAN}./setup_deploy.sh${NC}\n"
        echo -e "Or manually enter your GitHub PAT:"
        read -rp "GitHub Personal Access Token (or press Enter to skip): " PAT
        if [ -n "$PAT" ]; then
            GITHUB_USER=$(git config user.name)
            NEW_URL="https://${GITHUB_USER}:${PAT}@github.com/omralhasan/poshystore.git"
            git remote set-url origin "$NEW_URL"
            # Save for reuse (without printing)
            git push origin "$BRANCH" && echo -e "${GREEN}✓ Pushed to GitHub!${NC}"
        else
            echo -e "${YELLOW}⚠ Skipping GitHub push. Triggering direct deploy...${NC}"
        fi
    else
        echo -e "${GREEN}✓ Pushed to GitHub!${NC}"
    fi
fi

# ─── Step 5: Trigger production deploy ──────────────────────
echo -e "\n${BOLD}🌐 Deploying to production (159.223.180.154)...${NC}"
RESPONSE=$(curl -s --max-time 30 "$WEBHOOK_URL" 2>&1)

if echo "$RESPONSE" | grep -q '"success": true'; then
    DEPLOYED=$(echo "$RESPONSE" | grep '"deployed"' | cut -d'"' -f4)
    echo -e "${GREEN}${BOLD}✓ Deployed successfully!${NC}"
    echo -e "  Live commit: ${CYAN}$DEPLOYED${NC}"
elif echo "$RESPONSE" | grep -q '"success": false'; then
    echo -e "${RED}✗ Deploy webhook returned an error:${NC}"
    echo "$RESPONSE"
else
    echo -e "${RED}✗ Could not reach production server.${NC}"
    echo -e "Response: $RESPONSE"
    echo -e "\n${YELLOW}Manual deploy: SSH into server and run:${NC}"
    echo -e "  cd /var/www/html && git fetch origin && git reset --hard origin/main"
fi

# ─── Done ────────────────────────────────────────────────────
echo -e "\n${CYAN}${BOLD}══════════════════════════════════════${NC}"
echo -e "${GREEN}${BOLD}  Done! Check: http://159.223.180.154${NC}"
echo -e "${CYAN}${BOLD}══════════════════════════════════════${NC}\n"
