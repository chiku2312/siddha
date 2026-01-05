<?php
if (!defined("WHMCS")) die("Access denied");

use WHMCS\Database\Capsule;

function kycverification_config() {
    return [
        "name" => "Cashfree KYC",
        "description" => "DigiLocker & Aadhaar KYC Verification",
        "version" => "1.0.3",
        "author" => "VyomCloud",
        "fields" => [
            "country" => [
                "FriendlyName" => "Primary Country",
                "Type" => "dropdown",
                "Options" => "Afghanistan,Albania,Algeria,Andorra,Angola,Argentina,Armenia,Australia,Austria,Azerbaijan,Bahamas,Bahrain,Bangladesh,Barbados,Belarus,Belgium,Belize,Benin,Bhutan,Bolivia,Bosnia,Botswana,Brazil,Brunei,Bulgaria,Burkina Faso,Burundi,Cambodia,Cameroon,Canada,Cape Verde,Chad,Chile,China,Colombia,Comoros,Congo,Costa Rica,Croatia,Cuba,Cyprus,Czech Republic,Denmark,Djibouti,Dominica,Dominican Republic,Ecuador,Egypt,El Salvador,Eritrea,Estonia,Ethiopia,Fiji,Finland,France,Gabon,Gambia,Georgia,Germany,Ghana,Greece,Grenada,Guatemala,Guinea,Guyana,Haiti,Honduras,Hungary,Iceland,India,Indonesia,Iran,Iraq,Ireland,Israel,Italy,Jamaica,Japan,Jordan,Kazakhstan,Kenya,Kuwait,Kyrgyzstan,Laos,Latvia,Lebanon,Lesotho,Liberia,Libya,Lithuania,Luxembourg,Madagascar,Malawi,Malaysia,Maldives,Mali,Malta,Mauritania,Mauritius,Mexico,Moldova,Monaco,Mongolia,Montenegro,Morocco,Mozambique,Myanmar,Namibia,Nauru,Nepal,Netherlands,New Zealand,Nicaragua,Niger,Nigeria,North Korea,North Macedonia,Norway,Oman,Pakistan,Palau,Palestine,Panama,Papua New Guinea,Paraguay,Peru,Philippines,Poland,Portugal,Qatar,Romania,Russia,Rwanda,Saint Lucia,Samoa,San Marino,Saudi Arabia,Senegal,Serbia,Seychelles,Sierra Leone,Singapore,Slovakia,Slovenia,Solomon Islands,Somalia,South Africa,South Korea,South Sudan,Spain,Sri Lanka,Sudan,Suriname,Sweden,Switzerland,Syria,Taiwan,Tajikistan,Tanzania,Thailand,Togo,Tonga,Trinidad and Tobago,Tunisia,Turkey,Turkmenistan,Tuvalu,Uganda,Ukraine,United Arab Emirates,United Kingdom,United States,Uruguay,Uzbekistan,Vanuatu,Vatican City,Venezuela,Vietnam,Yemen,Zambia,Zimbabwe",
                "Default" => "India",
            ],
            "logo_url" => [
                "FriendlyName" => "Company Logo URL",
                "Type" => "text",
                "Size" => "100",
                "Default" => "https://panel.vyomcloud.com/assets/img/logo.png",
            ],
            "company_name" => [
                "FriendlyName" => "Company Name",
                "Type" => "text",
                "Size" => "50",
                "Default" => "VyomCloud",
            ],
        ]
    ];
}

function kycverification_activate() {
    try {
        if (!Capsule::schema()->hasColumn('tblclients', 'kyc_verified')) {
            Capsule::schema()->table('tblclients', function ($table) {
                $table->tinyInteger('kyc_verified')->default(0);
            });
        }
        
        Capsule::schema()->dropIfExists('mod_kyc_aadhar');
        Capsule::schema()->create('mod_kyc_aadhar', function ($table) {
            $table->increments('id');
            $table->integer('client_id');
            $table->text('ref_id');
            $table->text('status');
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('name')->nullable();
            $table->text('dob')->nullable();
            $table->text('address')->nullable();
            $table->text('email')->nullable();
            $table->text('gender')->nullable();
            $table->text('photo_link')->nullable();
            $table->longText('encrypted_data')->nullable();
            $table->text('admin_notes')->nullable();
            $table->integer('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
        
        $secureSettings = [
            'test_mode' => 'on',
            'encryption_key' => 'V835tUOZBFnXRzvRUiWmYa7wc0RE8xaa',
            'digilocker_client_id' => '',
            'digilocker_client_secret' => '',
        ];
        
        foreach ($secureSettings as $setting => $defaultValue) {
            $exists = Capsule::table('tbladdonmodules')
                ->where('module', 'kycverification')
                ->where('setting', $setting)
                ->exists();
            
            if (!$exists) {
                Capsule::table('tbladdonmodules')->insert([
                    'module' => 'kycverification',
                    'setting' => $setting,
                    'value' => $defaultValue
                ]);
            }
        }
        
        return ['status' => 'success', 'description' => 'Module activated!'];
    } catch (Exception $e) {
        return ['status' => 'error', 'description' => $e->getMessage()];
    }
}

function kycverification_deactivate() {
    return ['status' => 'success', 'description' => 'Deactivated'];
}

function kycverification_output($vars) {
    $settings = Capsule::table('tbladdonmodules')
        ->where('module', 'kycverification')
        ->pluck('value', 'setting')->toArray();
    
    $testMode = isset($settings['test_mode']) && $settings['test_mode'] == 'on';
    
    // Handle approve/reject
    if (isset($_GET['action']) && isset($_GET['id'])) {
        $action = $_GET['action'];
        $id = (int)$_GET['id'];
        
        if ($action == 'approve' || $action == 'reject') {
            try {
                $kyc = Capsule::table('mod_kyc_aadhar')->find($id);
                
                if ($kyc) {
                    $newStatus = ($action == 'approve') ? 'approved' : 'rejected';
                    
                    Capsule::table('mod_kyc_aadhar')
                        ->where('id', $id)
                        ->update([
                            'approval_status' => $newStatus,
                            'approved_by' => $_SESSION['adminid'] ?? 1,
                            'approved_at' => date('Y-m-d H:i:s'),
                            'admin_notes' => 'Processed by admin',
                        ]);
                    
                    if ($action == 'approve') {
                        Capsule::table('tblclients')
                            ->where('id', $kyc->client_id)
                            ->update(['kyc_verified' => 1]);
                    }
                    
                    echo '<div class="alert alert-success"><i class="fas fa-check"></i> KYC ' . ucfirst($newStatus) . '!</div>';
                }
            } catch (Exception $e) {
                echo '<div class="alert alert-danger">Error: ' . $e->getMessage() . '</div>';
            }
        }
    }
    
    // Stats
    $totalSubmitted = Capsule::table('mod_kyc_aadhar')->count();
    $verified = Capsule::table('mod_kyc_aadhar')->where('approval_status', 'approved')->count();
    $pending = Capsule::table('mod_kyc_aadhar')->where('approval_status', 'pending')->count();
    $rate = $totalSubmitted > 0 ? round(($verified / $totalSubmitted) * 100, 1) : 0;
    
    echo '<style>
        @import url("https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css");
        .kyc-dashboard { background: #f8f9fa; padding: 30px; font-family: "Segoe UI", sans-serif; }
        .kyc-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin-bottom: 30px; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .stat-value { font-size: 32px; font-weight: 700; margin: 10px 0; }
        .section-card { background: white; border-radius: 15px; padding: 30px; margin-bottom: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); }
        .kyc-table { width: 100%; border-collapse: collapse; }
        .kyc-table thead { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .kyc-table th { padding: 12px; text-align: left; font-size: 13px; }
        .kyc-table td { padding: 12px; border-bottom: 1px solid #e9ecef; }
        .kyc-table tr:hover { background: #f8f9fa; }
        .btn { padding: 8px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; border: none; cursor: pointer; text-decoration: none; display: inline-block; margin-right: 5px; }
        .btn-view { background: linear-gradient(135deg, #667eea, #764ba2); color: white; }
        .btn-approve { background: linear-gradient(135deg, #56ab2f, #a8e063); color: white; }
        .btn-reject { background: linear-gradient(135deg, #dc3545, #c82333); color: white; }
        .kyc-modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 9999; }
        .kyc-modal-content { background: white; margin: 50px auto; padding: 0; width: 90%; max-width: 700px; border-radius: 15px; }
        .modal-header { background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 20px 30px; border-radius: 15px 15px 0 0; display: flex; justify-content: space-between; }
        .modal-body { padding: 30px; max-height: 60vh; overflow-y: auto; }
        .kyc-detail-row { padding: 12px 0; border-bottom: 1px solid #f1f3f5; display: flex; }
        .kyc-detail-label { font-weight: 600; width: 180px; }
        .kyc-detail-value { flex: 1; }
        .modal-footer { padding: 20px 30px; background: #f8f9fa; border-radius: 0 0 15px 15px; }
        .kyc-modal-close { background: rgba(255,255,255,0.2); border: none; color: white; font-size: 24px; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; }
    </style>';
    
    echo '<div class="kyc-dashboard">';
    echo '<div class="kyc-header"><h2><i class="fas fa-shield-alt"></i> KYC Management</h2></div>';
    
    // Stats
    echo '<div class="stats-grid">';
    echo '<div class="stat-card"><div class="stat-value">' . $totalSubmitted . '</div><div>Total</div></div>';
    echo '<div class="stat-card"><div class="stat-value" style="color:#56ab2f;">' . $verified . '</div><div>Verified</div></div>';
    echo '<div class="stat-card"><div class="stat-value" style="color:#f2994a;">' . $pending . '</div><div>Pending</div></div>';
    echo '<div class="stat-card"><div class="stat-value" style="color:#00c6ff;">' . $rate . '%</div><div>Rate</div></div>';
    echo '</div>';
    
    // All KYCs
    echo '<div class="section-card">';
    echo '<h3><i class="fas fa-list"></i> All KYC Verifications</h3><br>';
    
    $allKyc = Capsule::table('mod_kyc_aadhar')
        ->join('tblclients', 'mod_kyc_aadhar.client_id', '=', 'tblclients.id')
        ->select('mod_kyc_aadhar.*', 'tblclients.firstname', 'tblclients.lastname', 'tblclients.email as client_email')
        ->orderBy('mod_kyc_aadhar.verified_at', 'desc')
        ->get();
    
    if (count($allKyc) > 0) {
        echo '<table class="kyc-table">';
        echo '<thead><tr><th>ID</th><th>Client</th><th>Email</th><th>Name</th><th>DOB</th><th>Status</th><th>Date</th><th>Actions</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($allKyc as $kyc) {
            echo '<tr>';
            echo '<td>' . $kyc->id . '</td>';
            echo '<td>' . htmlspecialchars($kyc->firstname . ' ' . $kyc->lastname) . '</td>';
            echo '<td>' . htmlspecialchars($kyc->client_email) . '</td>';
            echo '<td>' . htmlspecialchars($kyc->name) . '</td>';
            echo '<td>' . htmlspecialchars($kyc->dob) . '</td>';
            
            // Status Badge
            echo '<td>';
            if ($kyc->approval_status == 'approved') {
                echo '<span style="background:#28a745; color:white; padding:4px 12px; border-radius:15px; font-size:12px;">✓ Approved</span>';
            } elseif ($kyc->approval_status == 'pending') {
                echo '<span style="background:#ffc107; color:#000; padding:4px 12px; border-radius:15px; font-size:12px;">⏳ Pending</span>';
            } else {
                echo '<span style="background:#dc3545; color:white; padding:4px 12px; border-radius:15px; font-size:12px;">✗ Rejected</span>';
            }
            echo '</td>';
            
            echo '<td>' . date('d M Y, H:i', strtotime($kyc->verified_at)) . '</td>';
            
            // Actions
            echo '<td>';
            echo '<button class="btn btn-view" onclick="viewKyc(' . $kyc->id . ')"><i class="fas fa-eye"></i> View</button>';
            
            if ($kyc->approval_status == 'pending') {
                echo '<a href="?module=kycverification&action=approve&id=' . $kyc->id . '" class="btn btn-approve" onclick="return confirm(\'Approve?\')"><i class="fas fa-check"></i></a>';
                echo '<a href="?module=kycverification&action=reject&id=' . $kyc->id . '" class="btn btn-reject" onclick="return confirm(\'Reject?\')"><i class="fas fa-times"></i></a>';
            }
            echo '</td>';
            echo '</tr>';
            
            // Modal
            echo '<div id="kycModal' . $kyc->id . '" class="kyc-modal" onclick="if(event.target==this) this.style.display=\'none\'">';
            echo '<div class="kyc-modal-content">';
            echo '<div class="modal-header"><h3>KYC Details - #' . $kyc->id . '</h3>';
            echo '<button class="kyc-modal-close" onclick="document.getElementById(\'kycModal' . $kyc->id . '\').style.display=\'none\'">&times;</button></div>';
            echo '<div class="modal-body">';
            
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Client:</span><span class="kyc-detail-value">' . htmlspecialchars($kyc->firstname . ' ' . $kyc->lastname) . '</span></div>';
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Email:</span><span class="kyc-detail-value">' . htmlspecialchars($kyc->client_email) . '</span></div>';
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">KYC Name:</span><span class="kyc-detail-value"><strong>' . htmlspecialchars($kyc->name) . '</strong></span></div>';
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">DOB:</span><span class="kyc-detail-value">' . htmlspecialchars($kyc->dob) . '</span></div>';
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Gender:</span><span class="kyc-detail-value">' . htmlspecialchars($kyc->gender ?? 'N/A') . '</span></div>';
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Address:</span><span class="kyc-detail-value">' . nl2br(htmlspecialchars($kyc->address)) . '</span></div>';
            
            $encrypted = json_decode($kyc->encrypted_data, true);
            if ($encrypted && isset($encrypted['aadhaar_masked'])) {
                echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Aadhaar:</span><span class="kyc-detail-value"><code style="background:#f8f9fa; padding:5px 10px; border-radius:5px;">' . htmlspecialchars($encrypted['aadhaar_masked']) . '</code></span></div>';
            }
            
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Ref ID:</span><span class="kyc-detail-value"><code>' . htmlspecialchars($kyc->ref_id) . '</code></span></div>';
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Status:</span><span class="kyc-detail-value"><strong>' . strtoupper($kyc->approval_status) . '</strong></span></div>';
            echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Verified:</span><span class="kyc-detail-value">' . date('d M Y, H:i:s', strtotime($kyc->verified_at)) . '</span></div>';
            
            if ($encrypted && isset($encrypted['test_mode']) && $encrypted['test_mode']) {
                echo '<div class="kyc-detail-row"><span class="kyc-detail-label">Note:</span><span class="kyc-detail-value" style="color:#f2994a;"><strong>🧪 TEST MODE DATA</strong></span></div>';
            }
            
            echo '</div>';
            echo '<div class="modal-footer">';
            if ($kyc->approval_status == 'pending') {
                echo '<a href="?module=kycverification&action=approve&id=' . $kyc->id . '" class="btn btn-approve" onclick="return confirm(\'Approve?\')">Approve</a>';
                echo '<a href="?module=kycverification&action=reject&id=' . $kyc->id . '" class="btn btn-reject" onclick="return confirm(\'Reject?\')">Reject</a>';
            }
            echo '<button class="btn" style="background:#6c757d; color:white;" onclick="document.getElementById(\'kycModal' . $kyc->id . '\').style.display=\'none\'">Close</button>';
            echo '</div>';
            echo '</div></div>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p style="text-align:center; color:#666; padding:40px;">No KYC submissions yet</p>';
    }
    
    echo '</div></div>';
    
    echo '<script>function viewKyc(id) { document.getElementById("kycModal"+id).style.display="block"; }</script>';
}
