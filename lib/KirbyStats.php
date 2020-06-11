<?php

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
   * Get the host name (without port).
   * 
   * @return String
   */
  private static function getHost() {
    return strtok($_SERVER['HTTP_HOST'], ':');
  }

  /**
   * Get the referrer's host name (seems to omit the port automatically).
   * 
   * @return String
   */
  private static function getReferrerHost() {
    if (isset($_SERVER['HTTP_REFERER'])) {
      return parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    }
  }

  /**
   * Check if the page has been refreshed.
   * 
   * @return Boolean
   */
  private static function isPageRefreshed() {
    return (
      isset($_SERVER['HTTP_CACHE_CONTROL']) &&
      (
        $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' ||  
        $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache'
      )
    );  
  }

  /**
   * Check if the user is a new visitor by checking if he*she comes from
   * an external site.
   * 
   * @return Boolean
   */
  private static function isNewVisitor() {
    return (
      !self::isPageRefreshed() &&
      self::getHost() != self::getReferrerHost()
    );
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
    $isNewVisitor = self::isNewVisitor();

    // Increase total views.
    $viewsTotal = $pageStats->views_total()->value() + 1;

    // Increase visitors.
    $visitorsTotal = $pageStats->visitors_total()->value();
    if ($isNewVisitor) {
      $visitorsTotal++;
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
        'visitors' => $isNewVisitor ? 1 : 0
      ]);
    } else {
      $dateEntries[$lastIndex]['views'] += 1;
      if ($isNewVisitor) {
        $dateEntries[$lastIndex]['visitors'] += 1;
      }
    }

    self::updatePage($pageStats, [
      'views_total' => $viewsTotal,
      'visitors_total' => $visitorsTotal,
      'dates' => Yaml::encode($dateEntries)
    ]);
  } 
}