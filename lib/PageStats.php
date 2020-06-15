<?php

namespace KirbyStats; 

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/CounterList.php';
require_once __DIR__ . '/Logger/HourlyLogger.php';
require_once __DIR__ . '/Logger/DailyLogger.php';

use Kirby\Toolkit\F;
use \DateTime;

/**
 * A PageStats instance represents the statistics of a single page. It creates
 * a new page in the CMS ans stores the log files in it.
 */
class PageStats {
  /**
   * The id (aka path, e.g.: home/page/child ).
   * 
   * @var string
   */
  protected $id;

  /**
   * The plugin's root page in the CMS.
   * 
   * @var Kibry\Cms\Page
   */
  protected $rootStats;

  /**
   * The page in the CMS.
   * 
   * @var Kirby\Cms\Page
   */
  protected $stats;


  /**
   * The page's directory.
   * 
   * @var string
   */
  protected $dir;

  /**
   * Get property.
   * 
   * @param string property name
   * @return mixed
   */
  public function __call(string $property, array  $arguments) {
    return $this->$property ?? null;
  }

  /**
   * Create a new PageStats object.
   * 
   * @param string $id
   */
  function __construct(string $id) {
    $this->id = $id;  
  }

  /**
   * Log the current request.
   * 
   * @param array $analysis
   */
  public function log(array $analysis) {
    extract($analysis);
    if ($view || $visit) {
      $this->logHourly($analysis);
      $this->logDaily($analysis);
    }
  }

  /**
   * Log the hourly data (views and visits).
   * 
   * @param array $analysis
   */
  protected function logHourly(array $analysis) {
    extract($analysis);

    $this->loggerHourly()->log([
      'update' => function($data) use ($view, $visit) {
        $data['views'] = (int)$data['views'] + (int)$view;
        $data['visits'] = (int)$data['visits'] + (int)$visit;
        return $data;
      },
      'new' => [
        'views' => (int)$view,
        'visits' => (int)$visit
      ]
    ]);    
  }

  /**
   * Log the daily data (browser and referrers).
   * 
   * @param array $analysis
   */
  protected function logDaily(array $analysis) {
    extract($analysis);
    
    $this->loggerDaily()->log([
      'update' => function($data) use ($browser, $referrer) {
        if ($browser) {
          $data['browsers'] = (new CounterList($data['browsers']))
            ->increment($browser)
            ->toString();
        }

        if ($referrer) {
          $data['referrers'] = (new CounterList($data['referrers']))
            ->increment($referrer)
            ->toString();
        }

        return $data;
      },
      'new' => [
        'browsers' => $browser
          ? (new CounterList())->increment($browser)->toString()
          : '',
        'referrers' => $referrer
          ? (new CounterList())->increment($referrer)->toString()
          : null
      ]
    ]);    
  }

  /**
   * Get the page's directory.
   * 
   * @return string
   */
  protected function dir() {
    return (
      $this->dir ??
      $this->dir = kirby()->root('content') . '/' . $this->stats()->diruri()
    );
  }

  /**
   * Get the rootStats page.
   * 
   * @return Kirby\Cms\Page
   */
  public function rootStats() {
    return $this->rootStats ?? $this->rootStats = page('kirby-stats');
  }

  /**
   * If provided with an id get the id's slug. Otherwise get the slug. 
   * 
   * @param string $id
   * @return string
   */
  private function slug(string $id = null): string {
    $parts = explode('/', $id ?? $this->id);
    return array_pop($parts);
  }

  /**
   * Find stats for the id.
   * 
   * @param string $id
   * @return Kirby\Cms\Page|null
   */
  private function findStats(string $id) {
    return $this->rootStats()->find($id);
  }  

  /**
   * If provided with an id get the id's stats. Otherwise get the stats.
   * 
   * @param string $id
   * @return Kirby\Cms\Page
   */
  public function stats(string $id = null) {
    if ($id !== null) {
      return $this->findStats($id) ?? $this->createStats($id);
    }

    if ($this->stats !== null) {
      return $this->stats;
    }

    $id = $this->id;
    return $this->stats = $this->findStats($id) ?? $this->createStats($id);
  }

  /**
   * If provided with an id get the id's parent's stats. Otherwise get the
   * parent's stats.
   * 
   * @param string $id
   * @return Kirby\Cms\Page 
   */
  public function parentStats(string $id = null) {
    $parts = explode('/', $id ?? $this->id);
    array_pop($parts);

    if (count($parts)) {
      $parentId = implode('/', $parts);
      return $this->findStats($parentId) ?? $this->createStats($parentId); 
    } else {
      return $this->rootStats;
    }    
  }

  /**
   * Create a new stats page in the CMS. We use the page as a container for
   * our log files, where the actual data is stored.
   * 
   * @param string $id
   * @return Kirby\Cms\Page
   */
  protected function createStats(string $id) {
    $parent = $this->parentStats($id);
    $slug = $this->slug($id);

    // Create the stats page and publish.
    try {
      super_user();
      $stats = $parent->createChild([
        'content' => [
          'title' => $slug
        ],        
        'slug' => $slug,
        'template' => 'stats'
      ]);
    } catch (Exception $error) {
      throw new Exception($error);
    }
    super_user();
    $stats = $stats->publish();

    return $stats;    
  }

  /**
   * Create an hourly log file for the current month (if it doesn't already
   * exist) and return a new hourly logger.
   * 
   * @return KirbyStats\HourlyLogger
   */
  protected function loggerHourly() {
    $month = (new DateTime())->format('Y-m');
    $file = "{$this->dir()}/$month-hourly.csv";

    if (!F::exists($file)) {
      F::write($file, '');
    }

    return new HourlyLogger($file, ['views', 'visits']);
  }

  /**
   * Create a daily log file for the current month (if it doesn't already
   * exist) and return a new daily logger.
   * 
   * @return KirbyStats\HourlyLogger
   */
  protected function loggerDaily() {
    $month = (new DateTime())->format('Y-m');
    $file = "{$this->dir()}/$month-daily.csv";

    if (!F::exists($file)) {
      F::write($file, '');
    }

    return new DailyLogger($file, ['browsers', 'referrers']);
  }    
}