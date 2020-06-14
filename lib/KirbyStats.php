<?php
include_once(__DIR__ . '/helpers.php');
include_once(__DIR__ . '/Analyzer/ReferrerAnalyzer.php');
include_once(__DIR__ . '/PageStats.php');
include_once(__DIR__ . '/Logger/HourlyLogger.php');

use Kirby\Toolkit\Str;
use Kirby\Data\Yaml;

use KirbyStats\PageStats;
use KirbyStats\ReferrerAnalyzer;

class KirbyStats {
  private static $rootStats;

  /**
   * Create the root stats page.
   * 
   * @return Kirby\Cms\Page
   */
  private static function createRootStats() {
    try {
      super_user();
      $stats = site()->createChild([
        'content' => [
          'title' => 'Kirby Stats'
        ],
        'slug' => 'kirby-stats',
        'template' => 'root-stats'
      ]);
    } catch (Exception $error) {
      throw new Exception($error);
    }
    
    super_user();
    return $stats->publish();
  }

  /**
   * Get the root stats page and create it if it doesn't already exist.
   * 
   * @return Kirby\Cms\Page
   */
  private static function getRootStats() {
    return page('kirby-stats') ?? self::createRootStats();
  }

  /**
   * Create the root stats page if the plugin is called for the first time.
   */
  public static function init() {
    self::$rootStats = self::getRootStats();
  }

  public static function log($id) {
    $stats = new PageStats($id);
    $stats->log((new ReferrerAnalyzer())->analyze());
  }
}