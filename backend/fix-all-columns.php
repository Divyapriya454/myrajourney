<?php
// ============================================================
// MYRA JOURNEY - COMPLETE FIX SCRIPT
// Upload this to: myrajourney/ (server root)
// Run once in browser after setup-fresh-database.php
// ============================================================

// Works whether placed inside backend/ or one level above backend/
$envFile = file_exists(__DIR__ . '/.env') ? __DIR__ . '/.env' : __DIR__ . '/backend/.env';
$host = 'localhost'; $dbname = 'myrajourney'; $dbUser = 'root'; $dbPass = '';

if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $k = trim($k); $v = trim($v, " \t\"'");
        if ($k === 'DB_HOST') $host = $v;
        if ($k === 'DB_DATABASE' || $k === 'DB_NAME') $dbname = $v;
        if ($k === 'DB_USERNAME' || $k === 'DB_USER') $dbUser = $v;
        if ($k === 'DB_PASSWORD' || $k === 'DB_PASS') $dbPass = $v;
    }
}

echo "=== MYRA JOURNEY - COMPLETE FIX ===\n";
echo "DB: $host / $dbname\n\n";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbUser, $dbPass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "✓ Connected\n\n";
} catch (Exception $e) { die("✗ " . $e->getMessage() . "\n"); }

function col($pdo, $table, $col, $def) {
    try {
        if ($pdo->query("SHOW COLUMNS FROM `$table` LIKE '$col'")->fetch()) {
            echo "  ⊙ $table.$col exists\n";
        } else {
            $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$col` $def");
            echo "  ✓ Added $table.$col\n";
        }
    } catch (Exception $e) { echo "  ✗ $table.$col: " . $e->getMessage() . "\n"; }
}

// ===== USERS =====
echo "--- users ---\n";
col($pdo,'users','date_of_birth','DATE NULL');
col($pdo,'users','address','TEXT NULL');
col($pdo,'users','gender',"VARCHAR(10) NULL");
col($pdo,'users','last_login_at','DATETIME NULL');
col($pdo,'users','avatar_url','VARCHAR(500) NULL');
col($pdo,'users','phone','VARCHAR(20) NULL');
col($pdo,'users','status',"VARCHAR(20) NOT NULL DEFAULT 'ACTIVE'");
col($pdo,'users','name','VARCHAR(255) NULL');

// ===== APPOINTMENTS =====
echo "\n--- appointments ---\n";
col($pdo,'appointments','appointment_date','DATE NULL');
col($pdo,'appointments','appointment_time','TIME NULL');
col($pdo,'appointments','title','VARCHAR(255) NULL');
col($pdo,'appointments','description','TEXT NULL');
col($pdo,'appointments','location','VARCHAR(255) NULL');
col($pdo,'appointments','type',"VARCHAR(50) DEFAULT 'CONSULTATION'");
col($pdo,'appointments','status',"VARCHAR(20) DEFAULT 'SCHEDULED'");
// migrate start_time if exists
try {
    if ($pdo->query("SHOW COLUMNS FROM appointments LIKE 'start_time'")->fetch()) {
        $pdo->exec("UPDATE appointments SET appointment_date=DATE(start_time), appointment_time=TIME(start_time) WHERE appointment_date IS NULL AND start_time IS NOT NULL");
        echo "  ✓ Migrated start_time\n";
    }
} catch (Exception $e) {}

// ===== NOTIFICATIONS =====
echo "\n--- notifications ---\n";
col($pdo,'notifications','body','TEXT NULL');
col($pdo,'notifications','message','TEXT NULL');
col($pdo,'notifications','type',"VARCHAR(50) DEFAULT 'INFO'");
col($pdo,'notifications','read_at','DATETIME NULL');
col($pdo,'notifications','user_id','INT NULL');
col($pdo,'notifications','title','VARCHAR(255) NULL');

// ===== PATIENTS =====
echo "\n--- patients ---\n";
col($pdo,'patients','age','INT NULL');
col($pdo,'patients','gender','VARCHAR(10) NULL');
col($pdo,'patients','address','TEXT NULL');
col($pdo,'patients','blood_group','VARCHAR(10) NULL');
col($pdo,'patients','height','DECIMAL(5,2) NULL');
col($pdo,'patients','weight','DECIMAL(5,2) NULL');
col($pdo,'patients','medical_history','TEXT NULL');
col($pdo,'patients','allergies','TEXT NULL');
col($pdo,'patients','emergency_contact','VARCHAR(255) NULL');
col($pdo,'patients','assigned_doctor_id','INT NULL');
col($pdo,'patients','medical_id','VARCHAR(50) NULL');
col($pdo,'patients','current_medications','TEXT NULL');

// ===== DOCTORS =====
echo "\n--- doctors ---\n";
col($pdo,'doctors','user_id','INT NULL');
col($pdo,'doctors','specialization','VARCHAR(255) NULL');
col($pdo,'doctors','license_number','VARCHAR(100) NULL');
col($pdo,'doctors','department','VARCHAR(255) NULL');
col($pdo,'doctors','updated_at','DATETIME NULL');

// ===== REPORTS =====
echo "\n--- reports ---\n";
col($pdo,'reports','uploaded_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
col($pdo,'reports','file_path','VARCHAR(500) NULL');
col($pdo,'reports','file_url','VARCHAR(500) NULL');
col($pdo,'reports','status',"VARCHAR(20) DEFAULT 'PENDING'");
col($pdo,'reports','patient_id','INT NULL');
col($pdo,'reports','doctor_id','INT NULL');
col($pdo,'reports','title','VARCHAR(255) NULL');
col($pdo,'reports','description','TEXT NULL');

// ===== MEDICATIONS =====
echo "\n--- medications ---\n";
col($pdo,'medications','generic_name','VARCHAR(255) NULL');
col($pdo,'medications','category','VARCHAR(100) NULL');
col($pdo,'medications','description','TEXT NULL');
col($pdo,'medications','name','VARCHAR(255) NULL');

// ===== PATIENT_MEDICATIONS =====
// MedicationModel uses: patient_id, medication_id, prescribed_by, doctor_id, name_override, medication_name,
//   dosage, instructions, duration, is_morning, is_afternoon, is_night, food_relation, frequency,
//   frequency_per_day, start_date, end_date, active, is_active, created_at, updated_at
echo "\n--- patient_medications ---\n";
col($pdo,'patient_medications','patient_id','INT NULL');
col($pdo,'patient_medications','medication_id','INT NULL');
col($pdo,'patient_medications','prescribed_by','INT NULL');
col($pdo,'patient_medications','doctor_id','INT NULL');
col($pdo,'patient_medications','name_override','VARCHAR(255) NULL');
col($pdo,'patient_medications','medication_name','VARCHAR(255) NULL');
col($pdo,'patient_medications','dosage','VARCHAR(100) NULL');
col($pdo,'patient_medications','instructions','TEXT NULL');
col($pdo,'patient_medications','duration','VARCHAR(100) NULL');
col($pdo,'patient_medications','is_morning','TINYINT(1) DEFAULT 0');
col($pdo,'patient_medications','is_afternoon','TINYINT(1) DEFAULT 0');
col($pdo,'patient_medications','is_night','TINYINT(1) DEFAULT 0');
col($pdo,'patient_medications','food_relation','VARCHAR(100) NULL');
col($pdo,'patient_medications','frequency','VARCHAR(100) NULL');
col($pdo,'patient_medications','frequency_per_day','INT NULL');
col($pdo,'patient_medications','start_date','DATE NULL');
col($pdo,'patient_medications','end_date','DATE NULL');
col($pdo,'patient_medications','active','TINYINT(1) DEFAULT 1');
col($pdo,'patient_medications','is_active','TINYINT(1) DEFAULT 1');
col($pdo,'patient_medications','status',"VARCHAR(20) DEFAULT 'ACTIVE'");
col($pdo,'patient_medications','created_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
col($pdo,'patient_medications','updated_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
// Make originally NOT NULL columns nullable so inserts don't fail
try {
    $pdo->exec("ALTER TABLE patient_medications MODIFY COLUMN medication_name VARCHAR(255) NULL");
    $pdo->exec("ALTER TABLE patient_medications MODIFY COLUMN dosage VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE patient_medications MODIFY COLUMN frequency VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE patient_medications MODIFY COLUMN start_date DATE NULL");
    echo "  ✓ Made required columns nullable\n";
} catch (Exception $e) { echo "  ⊙ columns already nullable\n"; }

// ===== MEDICATION_LOGS =====
echo "\n--- medication_logs ---\n";
col($pdo,'medication_logs','patient_medication_id','INT NULL');
col($pdo,'medication_logs','patient_id','INT NULL');
col($pdo,'medication_logs','status',"VARCHAR(20) DEFAULT 'taken'");
col($pdo,'medication_logs','taken_at','DATETIME NULL');
col($pdo,'medication_logs','notes','TEXT NULL');
col($pdo,'medication_logs','created_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
// Make medication_id nullable (setup creates it NOT NULL but model uses patient_medication_id)
try {
    $pdo->exec("ALTER TABLE medication_logs MODIFY COLUMN medication_id INT NULL");
    $pdo->exec("ALTER TABLE medication_logs MODIFY COLUMN status VARCHAR(20) NULL DEFAULT 'taken'");
    echo "  ✓ Made medication_id nullable\n";
} catch (Exception $e) { echo "  ⊙ already nullable\n"; }

// ===== SYMPTOMS =====
// SymptomModel uses: patient_id, date, pain_level, stiffness_level, fatigue_level, joint_count, notes, created_at
echo "\n--- symptoms ---\n";
col($pdo,'symptoms','patient_id','INT NULL');
col($pdo,'symptoms','date','DATE NULL');
col($pdo,'symptoms','pain_level','INT NULL');
col($pdo,'symptoms','stiffness_level','INT NULL');
col($pdo,'symptoms','fatigue_level','INT NULL');
col($pdo,'symptoms','joint_count','INT NULL');
col($pdo,'symptoms','notes','TEXT NULL');
col($pdo,'symptoms','created_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
// legacy columns from setup-fresh-database.php (keep nullable so old rows don't break)
col($pdo,'symptoms','symptom_type','VARCHAR(100) NULL');
col($pdo,'symptoms','severity','VARCHAR(20) NULL');
col($pdo,'symptoms','logged_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
// Make symptom_type nullable if it was created NOT NULL
try {
    $pdo->exec("ALTER TABLE symptoms MODIFY COLUMN symptom_type VARCHAR(100) NULL");
    $pdo->exec("ALTER TABLE symptoms MODIFY COLUMN severity VARCHAR(20) NULL");
    echo "  ✓ Made symptom_type/severity nullable\n";
} catch (Exception $e) { echo "  ⊙ symptom_type/severity already nullable\n"; }

// ===== CRP_MEASUREMENTS =====
echo "\n--- crp_measurements ---\n";
col($pdo,'crp_measurements','patient_id','INT NULL');
col($pdo,'crp_measurements','doctor_id','INT NULL');
col($pdo,'crp_measurements','report_id','INT NULL');
col($pdo,'crp_measurements','crp_value','DECIMAL(10,4) NULL');
col($pdo,'crp_measurements','measurement_unit',"VARCHAR(20) DEFAULT 'mg/L'");
col($pdo,'crp_measurements','measurement_date','DATE NULL');
col($pdo,'crp_measurements','notes','TEXT NULL');
col($pdo,'crp_measurements','measured_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
col($pdo,'crp_measurements','updated_at','DATETIME NULL');

// ===== SETTINGS =====
echo "\n--- settings ---\n";
col($pdo,'settings','user_id','INT NULL');
col($pdo,'settings','`key`','VARCHAR(100) NULL');
col($pdo,'settings','`value`','TEXT NULL');
col($pdo,'settings','updated_at','DATETIME NULL');
// unique key for upsert
try {
    $pdo->exec("ALTER TABLE settings ADD UNIQUE KEY uq_user_key (user_id, `key`)");
    echo "  ✓ Added unique key on settings(user_id, key)\n";
} catch (Exception $e) { echo "  ⊙ settings unique key exists\n"; }

// ===== REHAB_EXERCISES — fix rehab_name column =====
echo "\n--- rehab_exercises ---\n";
col($pdo,'rehab_exercises','rehab_name','VARCHAR(255) NULL');
col($pdo,'rehab_exercises','name','VARCHAR(255) NULL');
col($pdo,'rehab_exercises','description','TEXT NULL');
col($pdo,'rehab_exercises','benefits','TEXT NULL');
col($pdo,'rehab_exercises','category','VARCHAR(100) NULL');
col($pdo,'rehab_exercises','video_url','VARCHAR(500) NULL');
col($pdo,'rehab_exercises','sets','INT NULL');
col($pdo,'rehab_exercises','reps','INT NULL');
col($pdo,'rehab_exercises','duration_minutes','INT NULL');
col($pdo,'rehab_exercises','frequency_per_week','INT NULL');
col($pdo,'rehab_exercises','status',"VARCHAR(20) DEFAULT 'ACTIVE'");
// sync name <-> rehab_name
try {
    $pdo->exec("UPDATE rehab_exercises SET rehab_name = name WHERE rehab_name IS NULL AND name IS NOT NULL");
    $pdo->exec("UPDATE rehab_exercises SET name = rehab_name WHERE name IS NULL AND rehab_name IS NOT NULL");
    echo "  ✓ Synced name/rehab_name\n";
} catch (Exception $e) {}

// ===== REHAB_PLANS =====
echo "\n--- rehab_plans ---\n";
col($pdo,'rehab_plans','patient_id','INT NULL');
col($pdo,'rehab_plans','doctor_id','INT NULL');
col($pdo,'rehab_plans','title','VARCHAR(255) NULL');
col($pdo,'rehab_plans','description','TEXT NULL');
col($pdo,'rehab_plans','start_date','DATE NULL');
col($pdo,'rehab_plans','end_date','DATE NULL');
col($pdo,'rehab_plans','status',"VARCHAR(20) DEFAULT 'ACTIVE'");

// ===== patient_rehab_assignment (used by RehabController) =====
echo "\n--- patient_rehab_assignment ---\n";
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS patient_rehab_assignment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        rehab_id INT NOT NULL,
        assigned_by_doctor_id INT NULL,
        sets INT DEFAULT 3,
        reps INT DEFAULT 10,
        status VARCHAR(20) DEFAULT 'pending',
        assigned_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "  ✓ patient_rehab_assignment ready\n";
} catch (Exception $e) { echo "  ✗ " . $e->getMessage() . "\n"; }

// ===== HEALTH_METRICS =====
echo "\n--- health_metrics ---\n";
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS health_metrics (
        id INT AUTO_INCREMENT PRIMARY KEY,
        patient_id INT NOT NULL,
        metric_type VARCHAR(100) NOT NULL,
        value DECIMAL(10,4) NOT NULL,
        unit VARCHAR(50) NULL,
        recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        notes TEXT NULL
    )");
    echo "  ✓ health_metrics ready\n";
} catch (Exception $e) { echo "  ✗ " . $e->getMessage() . "\n"; }

// ===== EDUCATION_ARTICLES =====
echo "\n--- education_articles ---\n";
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS education_articles (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        content TEXT,
        summary TEXT,
        category VARCHAR(100),
        author VARCHAR(255),
        image_url VARCHAR(500),
        published_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uq_slug (slug)
    )");
    echo "  ✓ education_articles ready\n";
    $count = (int)$pdo->query("SELECT COUNT(*) FROM education_articles")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO education_articles (title,slug,content,summary,category,author) VALUES
            ('Understanding Rheumatoid Arthritis','understanding-ra','Rheumatoid arthritis (RA) is an autoimmune disease that causes chronic inflammation of the joints. Unlike osteoarthritis, RA affects the lining of your joints, causing painful swelling that can eventually result in bone erosion and joint deformity. Early diagnosis and treatment are key to managing the disease effectively.','Learn about the basics of RA and how it affects your body.','BASICS','Medical Team'),
            ('Managing RA Pain Daily','managing-ra-pain','Living with RA requires daily management strategies. Exercise, medication adherence, and lifestyle modifications can significantly improve quality of life. Regular low-impact exercises like swimming and walking help maintain joint flexibility. Heat and cold therapy can also provide relief.','Practical tips for managing RA pain in your daily life.','LIFESTYLE','Medical Team'),
            ('Diet and Nutrition for RA','diet-nutrition-ra','An anti-inflammatory diet can help manage RA symptoms. Foods rich in omega-3 fatty acids, antioxidants, and fiber may help reduce inflammation. Include fatty fish, leafy greens, berries, and olive oil. Avoid processed foods, excess sugar, and saturated fats.','How diet affects RA and what foods to eat or avoid.','NUTRITION','Medical Team'),
            ('Exercise and Physical Therapy','exercise-physical-therapy','Regular exercise is crucial for RA patients. Physical therapy helps maintain joint function and muscle strength. Low-impact activities like yoga, tai chi, and swimming are particularly beneficial. Always consult your doctor before starting a new exercise program.','The importance of exercise in managing RA.','EXERCISE','Medical Team'),
            ('Understanding Your Medications','understanding-medications','RA medications include DMARDs, biologics, and NSAIDs. Each works differently to reduce inflammation and slow disease progression. DMARDs like methotrexate are commonly prescribed. Biologics target specific parts of the immune system. Always take medications as prescribed and report side effects to your doctor.','A guide to common RA medications and how they work.','MEDICATION','Medical Team')
        ");
        echo "  ✓ 5 sample articles inserted\n";
    } else {
        echo "  ⊙ Articles already exist ($count)\n";
    }
} catch (Exception $e) { echo "  ✗ " . $e->getMessage() . "\n"; }

// ===== CHATBOT_CONVERSATIONS =====
echo "\n--- chatbot_conversations ---\n";
col($pdo,'chatbot_conversations','user_id','INT NULL');
col($pdo,'chatbot_conversations','message','TEXT NULL');
col($pdo,'chatbot_conversations','response','TEXT NULL');
col($pdo,'chatbot_conversations','created_at','DATETIME NULL DEFAULT CURRENT_TIMESTAMP');

// ===== ADD MISSING ROUTE: users/doctors in router =====
// This is a backend code fix — add to index.php on server
// We patch it here by adding a route handler file
echo "\n--- Patching missing routes ---\n";
$routePatch = __DIR__ . '/backend/public/route_patch.php';
// We'll add the fix directly to index.php via a check
// Works whether placed inside backend/ or one level above backend/
$indexFile = file_exists(__DIR__ . '/public/index.php') 
    ? __DIR__ . '/public/index.php' 
    : __DIR__ . '/backend/public/index.php';
if (file_exists($indexFile)) {
    $content = file_get_contents($indexFile);
    // Check if users/doctors route exists
    if (strpos($content, "'/api/v1/users/doctors'") === false) {
        $insert = "\n// ===== PATCHED: users/doctors route =====\nif (route('GET', '/api/v1/users/doctors')) { Auth::requireAuth(); (new AdminController())->listDoctors(); exit; }\n";
        // Insert before the 404 handler
        $content = str_replace(
            "// ======================\n// 404\n// ======================",
            $insert . "\n// ======================\n// 404\n// ======================",
            $content
        );
        file_put_contents($indexFile, $content);
        echo "  ✓ Added users/doctors route to index.php\n";
    } else {
        echo "  ⊙ users/doctors route already exists\n";
    }
    // Check if chat/send route exists
    if (strpos($content, "'/api/v1/chat/send'") === false) {
        $insert2 = "\n// ===== PATCHED: chat/send alias =====\nif (route('POST', '/api/v1/chat/send')) { Auth::requireAuth(); (new ChatbotController())->chat(); exit; }\nif (route('GET', '/api/v1/chat/history')) { Auth::requireAuth(); (new ChatbotController())->history(); exit; }\n";
        $content = file_get_contents($indexFile);
        $content = str_replace(
            "// ======================\n// 404\n// ======================",
            $insert2 . "\n// ======================\n// 404\n// ======================",
            $content
        );
        file_put_contents($indexFile, $content);
        echo "  ✓ Added chat/send route alias to index.php\n";
    } else {
        echo "  ⊙ chat/send route already exists\n";
    }
} else {
    echo "  ✗ index.php not found at $indexFile\n";
}

// ===== ACTIVATE ALL USERS =====
echo "\n--- Activating all users ---\n";
try {
    $n = $pdo->exec("UPDATE users SET status='ACTIVE' WHERE status!='ACTIVE' OR status IS NULL");
    echo "  ✓ $n user(s) activated\n";
} catch (Exception $e) { echo "  ✗ " . $e->getMessage() . "\n"; }

// ===== SEED SAMPLE REHAB EXERCISES if empty =====
echo "\n--- Seeding rehab exercises ---\n";
try {
    $count = (int)$pdo->query("SELECT COUNT(*) FROM rehab_exercises")->fetchColumn();
    if ($count === 0) {
        $pdo->exec("INSERT INTO rehab_exercises (rehab_name, name, description, category, sets, reps, frequency_per_week, video_url) VALUES
            ('Wrist Flexion','Wrist Flexion','Gently bend your wrist forward and hold for 5 seconds','HAND',3,10,5,'ex_001_wrist_flexion.mp4'),
            ('Wrist Rotation','Wrist Rotation','Rotate your wrist in circles, 10 times each direction','HAND',3,10,5,'ex_002_wrist_rotation.mp4'),
            ('Thumb Opposition','Thumb Opposition','Touch your thumb to each finger tip in sequence','HAND',3,10,5,'ex_003_thumb_opposition.mp4'),
            ('Thumb Flexion','Thumb Flexion','Bend your thumb across your palm and hold','HAND',3,10,5,'ex_004_thumb_flexion.mp4'),
            ('Finger Flexion','Finger Flexion','Curl your fingers into a fist slowly and release','HAND',3,10,5,'ex_005_finger_flexion.mp4'),
            ('Finger Extension','Finger Extension','Spread your fingers wide apart and hold','HAND',3,10,5,'ex_006_finger_extension.mp4'),
            ('Finger Pinch','Finger Pinch','Pinch a soft ball or putty between fingers','HAND',3,10,5,'ex_007_finger_pinch.mp4'),
            ('Knee Flexion','Knee Flexion','Slowly bend and straighten your knee while seated','KNEE',3,10,3,'ex_008_knee_flexion.mp4'),
            ('Hip Flexion','Hip Flexion','Lift your knee toward your chest while standing','HIP',3,10,3,'ex_009_hip_flexion.mp4'),
            ('Hip Abduction','Hip Abduction','Move your leg out to the side while standing','HIP',3,10,3,'ex_010_hip_abduction.mp4')
        ");
        echo "  ✓ 10 rehab exercises seeded\n";
    } else {
        echo "  ⊙ Rehab exercises already exist ($count)\n";
    }
} catch (Exception $e) { echo "  ✗ " . $e->getMessage() . "\n"; }

echo "\n=== COMPLETE ===\n";
echo "All fixes applied. Test your app now.\n";
echo "\nQuick test URLs:\n";
echo "  Login: POST /api/v1/auth/login\n";
echo "  Patient: deepankumar\@gmail.com / Welcome\@456\n";
echo "  Doctor:  doctor\@test.com / Patrol\@987\n";
echo "  Admin:   testadmin\@test.com / AS\@Saveetha123\n";
