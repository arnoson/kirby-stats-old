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
  /**
   * The page in the CMS.
   * 
   * @var Kirby\Cms\Page
   */
  protected static $rootStats;

  /**
   * Create the root stats.
   * 
   * @return Kirby\Cms\Page
   */
  protected static function createRootStats() {
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
   * Get the root stats.
   * 
   * @return Kirby\Cms\Page
   */
  protected static function rootStats() {
    return self::$rootStats ?? (
      self::$rootStats = page('kirby-stats') ?? self::createRootStats()
    );
  }

  public static function init() {
    self::$rootStats = self::rootStats();
  }

  /**
   * Log the current request.
   * 
   * @param string $id
   */
  public static function log(string $id): string {
    $stats = new PageStats($id);
    $stats->log((new ReferrerAnalyzer())->analyze());
  }
}