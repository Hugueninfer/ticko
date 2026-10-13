<?php

declare(strict_types=1);

/**
 * Builds a shields-style SVG badge from a Clover coverage report.
 *
 * Usage: php coverage-badge.php <clover.xml> <output.svg>
 */
[$script, $clover, $output] = $argv + [null, null, null];

if (! is_string($clover) || ! is_string($output) || ! is_file($clover)) {
    fwrite(STDERR, "Usage: php {$script} <clover.xml> <output.svg>\n");
    exit(1);
}

$xml = simplexml_load_file($clover);
$metrics = $xml?->project->metrics;

if ($metrics === null) {
    fwrite(STDERR, "Invalid Clover report.\n");
    exit(1);
}

$total = (int) $metrics['statements'];
$covered = (int) $metrics['coveredstatements'];
$percent = $total > 0 ? floor($covered / $total * 1000) / 10 : 0.0;

$color = match (true) {
    $percent >= 90 => '#4c1',
    $percent >= 80 => '#97ca00',
    $percent >= 70 => '#dfb317',
    default => '#e05d44',
};
$label = 'coverage';
$value = sprintf('%.1f%%', $percent);

$svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="104" height="20" role="img" aria-label="{$label}: {$value}">
  <title>{$label}: {$value}</title>
  <linearGradient id="s" x2="0" y2="100%"><stop offset="0" stop-color="#bbb" stop-opacity=".1"/><stop offset="1" stop-opacity=".1"/></linearGradient>
  <clipPath id="r"><rect width="104" height="20" rx="3" fill="#fff"/></clipPath>
  <g clip-path="url(#r)">
    <rect width="61" height="20" fill="#555"/>
    <rect x="61" width="43" height="20" fill="{$color}"/>
    <rect width="104" height="20" fill="url(#s)"/>
  </g>
  <g fill="#fff" text-anchor="middle" font-family="Verdana,Geneva,DejaVu Sans,sans-serif" font-size="11">
    <text x="31.5" y="14">{$label}</text>
    <text x="81.5" y="14">{$value}</text>
  </g>
</svg>
SVG;

file_put_contents($output, $svg.PHP_EOL);
echo "Coverage: {$value}\n";
