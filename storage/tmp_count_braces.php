<?php
$s = file_get_contents(__DIR__ . '/../app/Services/ReportingService.php');
echo 'opens:'.substr_count($s,'{').' closes:'.substr_count($s,'}')."\n";
$lines = explode("\n", $s);
foreach ($lines as $i => $line) {
    if (strpos($line, 'protected function getCoursesByCategoryData') !== false) {
        echo "Found method at line: " . ($i+1) . "\n";
    }
}
?>