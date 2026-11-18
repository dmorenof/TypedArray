<?php

declare(strict_types=1);

if ($argc !== 3 || !is_file($argv[1]) || !is_numeric($argv[2])) {
    fwrite(STDERR, "Usage: php tools/check-coverage.php <coverage.xml> <minimum-percent>\n");
    exit(2);
}

$report = simplexml_load_file($argv[1]);
if ($report === false || !isset($report->project->metrics)) {
    fwrite(STDERR, "Could not read Clover project metrics.\n");
    exit(2);
}

$metrics = $report->project->metrics;
$total = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];

if ($total < 1 || $covered > $total || $covered < 0) {
    fwrite(STDERR, "No valid executable line coverage was recorded.\n");
    exit(2);
}

$percent = 100 * $covered / $total;
$minimum = (float) $argv[2];

printf("Covered %d/%d executable lines (%.2f%%); minimum %.2f%%\n", $covered, $total, $percent, $minimum);

if ($percent + 0.00001 < $minimum) {
    fwrite(STDERR, "Coverage is below the required minimum.\n");
    exit(1);
}
