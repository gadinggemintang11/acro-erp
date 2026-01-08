#!/bin/bash

# Deploy Batch Import Feature to Production
# Run this script to upload necessary files for batch_import feature

echo "======================================"
echo "Deploy Batch Import to Production"
echo "======================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}Files to upload to production:${NC}"
echo ""

echo "1. application/controllers/JurnalUmum.php"
echo "2. application/models/JurnalUmum_model.php"
echo "3. application/views/accounting/jurnal_umum/batch_import.php"
echo "4. application/views/accounting/jurnal_umum/index.php (updated with filters)"
echo "5. application/config/routes.php (updated with explicit routes)"
echo "6. .htaccess (copy from .htaccess.production)"
echo "7. uploads/temp/.htaccess (security)"
echo "8. uploads/temp/.gitignore"
echo ""

echo -e "${GREEN}Checklist after upload:${NC}"
echo ""
echo "[ ] 1. Verify .htaccess has RewriteBase /"
echo "[ ] 2. Check base_url in application/config/config.php"
echo "[ ] 3. Ensure index_page = '' (empty)"
echo "[ ] 4. Create folder: uploads/temp with permission 777"
echo "[ ] 5. Test URL: https://mingbaolianchuangint.com/jurnalumum/batch_import"
echo "[ ] 6. Clear OpCache if enabled"
echo ""

echo -e "${YELLOW}Quick Fix Commands (via SSH):${NC}"
echo ""
echo "# Create uploads folder"
echo "mkdir -p uploads/temp"
echo "chmod 777 uploads/temp"
echo ""
echo "# Clear OpCache"
echo "echo '<?php opcache_reset(); echo \"Cleared\"; ?>' > clear.php"
echo "curl https://mingbaolianchuangint.com/clear.php"
echo "rm clear.php"
echo ""

echo -e "${RED}IMPORTANT:${NC}"
echo "- Make sure mod_rewrite is enabled on production server"
echo "- Check .htaccess has correct RewriteBase"
echo "- Verify all routes in routes.php are uploaded"
echo ""

read -p "Press Enter to see production .htaccess content..."

echo ""
echo "======================================"
echo "Production .htaccess Content:"
echo "======================================"
cat .htaccess.production
echo ""

echo -e "${GREEN}Deployment checklist created!${NC}"
echo "Read: FIX_404_BATCH_IMPORT.md for detailed troubleshooting"
