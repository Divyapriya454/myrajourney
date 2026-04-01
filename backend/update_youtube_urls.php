<?php
require_once 'src/config/config.php';
require_once 'src/config/db.php';

use Src\Config\DB;

try {
    $db = DB::conn();
    
    echo "Updating exercise video URLs with YouTube links..." . PHP_EOL;
    
    // Update exercises with YouTube URLs
    $updates = [
        'ex_001' => 'https://youtu.be/MD-ddObx9QA?si=CFZLpAB10lpl9koi',
        'ex_002' => 'https://youtu.be/07xRQWfXJgI?si=2nv2BIeiMhBchKxs',
        'ex_003' => 'https://youtu.be/H5qap5Ktrlk?si=PUKL7XS__B9YQMY1',
        'ex_004' => 'https://youtu.be/r85WPBt2WRw?si=DjfNBSBPkRBQbwEs',
        'ex_005' => 'https://youtu.be/1dJq7KKiHqM?si=qwK-T79-Pom1DE20',
        'ex_006' => 'https://youtu.be/EiRC80FJbHU?si=finger_spreading_exercise',
        'ex_007' => 'https://youtu.be/Kn-9JHkrlzk?si=pinch_grip_exercises',
        'ex_008' => 'https://youtu.be/gsqKoEcbXkI?si=seated_knee_extensions',
        'ex_009' => 'https://youtu.be/YQmpO9VT2X4?si=hip_flexion_exercises',
        'ex_010' => 'https://youtu.be/6JBaWiZGrQs?si=hip_abduction_exercises'
    ];
    
    $stmt = $db->prepare("UPDATE ra_exercises SET video_url = ? WHERE id = ?");
    
    foreach ($updates as $exerciseId => $videoUrl) {
        $stmt->execute([$videoUrl, $exerciseId]);
        echo "Updated $exerciseId with URL: $videoUrl" . PHP_EOL;
    }
    
    echo "All exercise video URLs updated successfully!" . PHP_EOL;
    
    // Verify updates
    echo "\nVerifying updates:" . PHP_EOL;
    $stmt = $db->prepare("SELECT id, name, video_url FROM ra_exercises ORDER BY id");
    $stmt->execute();
    $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($exercises as $exercise) {
        echo "- {$exercise['id']}: {$exercise['name']}" . PHP_EOL;
        echo "  Video: {$exercise['video_url']}" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error updating video URLs: " . $e->getMessage() . PHP_EOL;
}
?>
