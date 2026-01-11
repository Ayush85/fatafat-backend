#!/bin/bash
# Deployment Setup Script for Fatafatsewa API Server

echo "=== Fatafatsewa API Server Deployment Setup ==="
echo ""

# Step 1: Generate new SSH key for GitHub
echo "Step 1: Generating new SSH key..."
ssh-keygen -t ed25519 -C "apifatafatsewa@sarbatrainc.com" -f ~/.ssh/id_ed25519_fatafatsewa -N ""

echo ""
echo "=== SSH Public Key (Add this to GitHub) ==="
cat ~/.ssh/id_ed25519_fatafatsewa.pub
echo ""
echo "Copy the above key and add it to GitHub:"
echo "https://github.com/settings/ssh/new"
echo ""
read -p "Press Enter after adding the key to GitHub..."

# Step 2: Test SSH connection
echo ""
echo "Step 2: Testing GitHub SSH connection..."
ssh -T -i ~/.ssh/id_ed25519_fatafatsewa git@github.com

# Step 3: Configure SSH to use the new key
echo ""
echo "Step 3: Configuring SSH..."
cat >> ~/.ssh/config << 'EOF'

# Fatafatsewa GitHub
Host github.com-fatafatsewa
    HostName github.com
    User git
    IdentityFile ~/.ssh/id_ed25519_fatafatsewa
    IdentitiesOnly yes
EOF

echo "SSH configured successfully!"
echo ""
echo "=== Next Steps ==="
echo "1. SSH into your server: ssh root@103.163.182.59"
echo "2. Run the deployment commands (see deploy_server.sh)"
