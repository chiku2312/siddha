#!/bin/bash

echo "🧹 Cleaning up orphan KYC records..."
echo ""

# Get database credentials
DB_HOST="localhost"
DB_USER="root"
DB_PASS=""  # Enter your MySQL root password
DB_NAME="whmcs_db"  # Enter your WHMCS database name

echo "Enter MySQL root password:"
read -s DB_PASS

echo ""
echo "Checking orphan records..."

# Delete orphan KYC records
mysql -u$DB_USER -p$DB_PASS $DB_NAME << EOF
-- Show orphan records before deleting
SELECT 
    CONCAT('Found ', COUNT(*), ' orphan KYC records') as status
FROM mod_kyc_aadhar k
LEFT JOIN tblclients c ON k.client_id = c.id
WHERE c.id IS NULL;

-- Delete orphan records
DELETE k FROM mod_kyc_aadhar k
LEFT JOIN tblclients c ON k.client_id = c.id
WHERE c.id IS NULL;

-- Show clean stats
SELECT 
    (SELECT COUNT(*) FROM mod_kyc_aadhar) as total_kyc,
    (SELECT COUNT(*) FROM mod_kyc_aadhar WHERE approval_status='approved') as approved,
    (SELECT COUNT(*) FROM mod_kyc_aadhar WHERE approval_status='pending') as pending,
    (SELECT COUNT(*) FROM tblclients) as total_clients,
    (SELECT COUNT(*) FROM tblclients WHERE kyc_verified=1) as verified_clients;
EOF

echo ""
echo "✅ Cleanup complete!"
echo "Refresh admin panel to see updated stats"
