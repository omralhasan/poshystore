#!/bin/bash
# ============================================================
# Poshy Store - Deploy Setup (Run Once)
# Configures GitHub PAT so ./deploy.sh works automatically
# ============================================================

CYAN='\033[0;36m'; GREEN='\033[0;32m'; RED='\033[0;31m'
YELLOW='\033[1;33m'; BOLD='\033[1m'; NC='\033[0m'

echo -e "${CYAN}${BOLD}══ Poshy Store Deploy Setup ══${NC}\n"
echo -e "This configures automatic GitHub push."
echo -e "You only need to run this ${BOLD}once${NC}.\n"

echo -e "${YELLOW}To create a GitHub PAT (if you don't have one):${NC}"
echo -e "  1. Go to: ${CYAN}https://github.com/settings/tokens${NC}"
echo -e "  2. Click 'Generate new token (classic)'"
echo -e "  3. Check the ${BOLD}repo${NC} scope"
echo -e "  4. Copy the token (starts with ghp_)\n"

read -rp "Paste your GitHub Personal Access Token: " PAT
if [ -z "$PAT" ]; then
    echo -e "${RED}No token provided. Aborting.${NC}"
    exit 1
fi

GITHUB_USER="omralhasan"
NEW_URL="https://${GITHUB_USER}:${PAT}@github.com/omralhasan/poshystore.git"
cd /home/omar/poshystore

# Store PAT in remote URL
git remote set-url origin "$NEW_URL"
echo -e "${GREEN}✓ Remote URL configured with PAT${NC}"

# Test push
echo -e "\n${BOLD}Testing GitHub connection...${NC}"
if git ls-remote origin HEAD > /dev/null 2>&1; then
    echo -e "${GREEN}✓ GitHub connection works!${NC}"
else
    echo -e "${RED}✗ Connection failed. Check your PAT.${NC}"
    # Reset to clean URL
    git remote set-url origin "https://github.com/omralhasan/poshystore.git"
    exit 1
fi

echo -e "\n${GREEN}${BOLD}Setup complete! Now just run:${NC}"
echo -e "  ${CYAN}./deploy.sh \"your change description\"${NC}\n"
