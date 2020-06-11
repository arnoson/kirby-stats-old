<?php

include_once(__DIR__ . '/ReferrerAnalyzer.php');

use Kirby\Toolkit\Str;
use Kirby\Data\Yaml;

class KirbyStats {
  private static $stats;

  /**
   * Impersonate kirby super user for the next request.
   */
  private static function impersonate() {
    kirby()->impersonate('kirby');
  }

  /**
   * Create the stats page.
   * 
   * @return Kirby\Cms\Page
   */
  private static function createStats() {
    try {
      self::impersonate();
      $stats = site()->createChild([
        'content' => [
          'title' => 'Kirby Stats'
        ],
        'slug' => 'kirby-stats',
        'template' => 'stats'
      ]);
    } catch (Exception $error) {
      throw new Exception($error);
    }
    
    self::impersonate();
    return $stats->publish();
  }

  /**
   * Create a page's stats page.
   * 
   * @return Kirby\Cms\Page - The created stats page.
   */
  private static function createPageStats($page) {
    $parentId = $page->parentId();
    $parentStats = $parentId ? self::$stats->find($parentId) : self::$stats;

    try {
      self::impersonate();
      $pageStats = $parentStats->createChild([
        'content' => [
          'title' => $page->slug()
        ],        
        'slug' => $page->slug(),
        'template' => 'page-stats'
      ]);
    } catch (Exception $error) {
      throw new Exception($error);
    }
    
    self::impersonate();
    return $pageStats->publish(); 
  }

  /**
   * Create a page's stats page and all parents' stats pages if they don't
   * already exist.
   * 
   * @param Kirby\Cms\Page $page - The original page.
   * @return Kirby\Cms\Page - The created stats page.
   */
  private static function createNestedPageStats($page) {
    $parents = $page->parents()->flip();
    foreach ($parents as $parent) {
      if (!self::$stats->find($parent->id())) {
        self::createPageStats($parent);
      }
    }
    return self::createPageStats($page);   
  }

  /**
   * Get the stats page and create it if it doesn't already exist.
   * 
   * @return Kirby\Cms\Page
   */
  private static function getStats() {
    return page('kirby-stats') ?? self::createStats();
  }

  /**
   * Get a page's stats page and create it if it doesn't already exist.
   * 
   * @param Kirby\Cms\Page - The original page.
   * @return Kirby\Cms\Page
   */
  private static function getPageStats($page) {
    return self::$stats->find($page->id()) ?? self::createNestedPageStats($page);
  }

  /**
   * Update a kirby page and return the updated page.
   * 
   * @return Kirby\Cms\Page
   */
  private static function updatePage($page, $data) {
    try {
      self::impersonate();
      $page = $page->update($data);
    } catch(Exception $error) {
      throw new Exception($error);
    }
    return $page;
  }

  /**
   * Create the stats page if the plugin is called for the first time.
   */
  public static function init() {
    self::$stats = self::getStats();
  }

  /**
   * Log a page visit.
   */
  public static function log($page) {    
    $pageStats = self::getPageStats($page);

    ['view' => $view, 'visit' => $visit] = ReferrerAnalyzer::analyze();

    // Increase total views.
    if ($view) {
      $pageStats = $pageStats->increment('views_total');
    }

    // Increase visitors.
    if ($visit) {
      $pageStats = $pageStats->increment('visitors_total');
    }

    // Get the latest date entry. If latest entry is today, increase it's views,
    // otherwise add a new entry with the current date.
    $dateEntries = $pageStats->dates()->yaml();
    $lastDateEntry = end($dateEntries);
    $lastIndex = key($dateEntries);
    $currentDate = date("Y-m-d");
    if (
      !$lastDateEntry || 
      (new DateTime($lastDateEntry['date']) != new DateTime($currentDate))
    ) {
      array_push($dateEntries, [
        'date' => $currentDate,
        'views' => 1,
        'visitors' => $visit ? 1 : 0
      ]);
    } else {
      if ($view) {
        $dateEntries[$lastIndex]['views'] += 1;
      }
      if ($visit) {
        $dateEntries[$lastIndex]['visitors'] += 1;
      }
    }

    self::updatePage($pageStats, [
      'dates' => Yaml::encode($dateEntries)
    ]);
  } 
}