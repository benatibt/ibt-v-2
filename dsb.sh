#!/bin/bash
set -e

echo "🚀 Deploying IBT v2 to Hostinger sandbox..."

# Push theme
git subtree push --prefix=themes/ibt hostinger-theme main

# Push plugin
git subtree push --prefix=plugins/ibt_customisation hostinger-plugin main

echo "✅ Deployment complete."
