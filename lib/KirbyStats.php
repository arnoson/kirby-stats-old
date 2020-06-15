<?php

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/Analyzer/ReferrerAnalyzer.php';
require_once __DIR__ . '/PageStats.php';
require_once __DIR__ . '/Logger/HourlyLogger.php';

use Kirby\Toolkit\Str;
use Kirby\Data\Yaml;

use KirbyStats\PageStats;
use KirbyStats\ReferrerAnalyzer;

class KirbyStats {
  /**
   * Log the current request.
   * 
   * @param string $id
   */
  public static function log(string $id) {
    $stats = new PageStats($id);
    $stats->log((new ReferrerAnalyzer())->analyze());
  }
}