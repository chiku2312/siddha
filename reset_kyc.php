<?php
require_once __DIR__ . '/../../../init.php';

use WHMCS\Database\Capsule;

echo "Resetting KYC data...\n\n";

// Delete all KYC records
$deleted = Capsule::table('mod_kyc_aadhar')->delete();
echo "✅ Deleted $deleted KYC records\n";

// Reset all clients
$updated = Capsule::table('tblclients')->update(['kyc_verified' => 0]);
echo "✅ Reset $updated clients\n";

// Show final count
$count = Capsule::table('mod_kyc_aadhar')->count();
echo "\n📊 Total KYC records now: $count\n";

echo "\n✅ Done! Refresh admin panel.\n";
