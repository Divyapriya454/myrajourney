<?php
require 'c:/Users/Admin/AndroidStudioProjects/myrajourney/myrajourney/backend/src/bootstrap.php';
use Src\Models\MedicationModel;
$meds = new MedicationModel();
$r = $meds->patientMedications(25, 1, 1, 10);
$item = $r['items'][0];
echo "JSON TYPE AUDIT (ID " . $item['id'] . "):\n";
foreach ($item as $k => $v) {
    echo "$k: (" . gettype($v) . ") ";
    if ($v === null) echo "NULL";
    else if ($v === "") echo "EMPTY";
    else echo $v;
    echo "\n";
}
echo "\nJSON ENCODED:\n" . json_encode($item, JSON_PRETTY_PRINT);
