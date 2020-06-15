<?php

namespace KirbyStats; 

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/CounterList.php';
require_once __DIR__ . '/Logger/HourlyLogger.php';
require_once __DIR__ . '/Logger/DailyLogger.php';

use Kirby\Toolkit\F;
use Kirby\Toolkit\Dir;
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
   * The directory where the stats are stored.
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

    // if ($view || $visit) {
      $this->logHourly($analysis);
    // }
    // if ($visit) {
      $this->logDaily($analysis);
    // }
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
   * Get the contents's directory.
   * 
   * @return string
   */
  protected function dir() {
    if ($this->dir) {
      return $this->dir;
    }

    $rootDir = option('arnoson.kirby-stats.dir');
    $dir = kirby()->root('index') . "/$rootDir/pages/" . $this->id();
    if (!Dir::exists($dir)) {
      Dir::make($dir);
    }

    return $this->dir = $dir;
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