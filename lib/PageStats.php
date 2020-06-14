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
   * The hourly logger used to store views and visits.
   * 
   * @var KirbyStats\HourlyLogger
   */
  protected $logger;

  /**
   * The daily logger used to store meta data (referrer, browser)
   * 
   * @var KirbyStats\DailyLogger
   */
  protected $metaLogger;

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

    $this->logger = new HourlyLogger(
      $this->stats()->file('log.csv')->root(),
      ['views', 'visits']
    );

    $this->metaLogger = new DailyLogger(
      $this->stats()->file('meta-log.csv')->root(),
      ['browsers', 'referrers']
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
   * Log the current request.
   * 
   * @param array $analysis
   */
  public function log(array $analysis) {
    extract($analysis);

    if (!($view || $visit)) {
      // Nothing to log.
      return;
    }

    $this->logger()->log([
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

    $this->metaLogger->log([
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

    // Add our log file. There we will store the stats for the page.
    // We could also store it in the stats page directly, but using a dedicated 
    // csv file is much more efficient.
    $statsDir = kirby()->root('content') . '/' . $stats->diruri();
    F::write($statsDir . '/log.csv', '');
    F::write($statsDir . '/meta-log.csv', '');

    return $stats;    
  }
}