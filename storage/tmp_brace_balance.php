<?php
$file = __DIR__ . '/../app/Services/ReportingService.php';
$s = file($file);
$balance = 0;
foreach ($s as $i => $line) {
    $opens = substr_count($line, '{');
    $closes = substr_count($line, '}');
    $balance += $opens - $closes;
    if ($i < 200 || $i > count($s) - 20) {
        // print head and tail areas for context
        echo str_pad($i+1,4,' ', STR_PAD_LEFT) . " opens:" . $opens . " closes:" . $closes . " balance:" . $balance . " | " . rtrim($line) . "\n";
    } elseif ($i % 50 == 0) {
        echo str_pad($i+1,4,' ', STR_PAD_LEFT) . " balance:" . $balance . "\n";
    }
}
echo "FINAL BALANCE: $balance\n";
?>