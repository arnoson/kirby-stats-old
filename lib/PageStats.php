<?php

namespace KirbyStats; 

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/CounterList.php';
require_once __DIR__ . '/Logger/HourlyLogger.php';
require_once __DIR__ . '/Logger/DailyLogger.php';

use Kirby\Toolkit\F;
use Kirby\Toolkit\Dir;
use Kirby\Toolkit\V;
use \DateTime;
use \DatePeriod;
use \DateInterval;
use \Exception;

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

    // TODO: remove (only for debugging)
    // $visit = true;

    $time = (new DateTime)->getTimestamp();

    // If therer is neither a visit nor a view there is nothing to lock.
    if ($view || $visit) {
      $this->logHourly($time, $analysis);
    }
    // Only log the daily data (browser and referrer) for new visits. Otherwise
    // it would distort the statistics.
    if ($visit) {
      $this->logDaily($time, $analysis);
    }
  }

  /**
   * Log the hourly data (views and visits).
   * 
   * @param int $time - The timestamp.
   * @param array $analysis
   */
  protected function logHourly(int $time, array $analysis) {
    $view = $analysis['view'] ?? null;
    $visit = $analysis['visit'] ?? null;

    $date = (new DateTime)->setTimestamp($time);
    $year = $date->format('Y');
    $month = $date->format('n');

    $this->loggerHourly($year, $month)->log([
      'time' => $time,
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
   * @param int $time - The timestamp.
   * @param array $analysis
   */
  protected function logDaily(int $time, array $analysis) {
    $browser = $analysis['browser'] ?? null;
    $referrer = $analysis['referrer'] ?? null;

    $date = (new DateTime)->setTimestamp($time);
    $year = $date->format('Y');
    $month = $date->format('n'); 

    $this->loggerDaily($year, $month)->log([
      'time' => $time,
      'update' => function($data) use ($browser, $referrer) {
        if ($browser) {
          $data['browsers'] = (new CounterList($data['browsers']))
            ->increment($browser['id'])
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
          ? (new CounterList())->increment($browser['id'])->toString()
          : '',
        'referrers' => $referrer
          ? (new CounterList())->increment($referrer)->toString()
          : null
      ]
    ]);    
  }

  /**
   * Get the hourly and daily logs for the period.
   * 
   * @param string $from - The start date `Y-m-d`.
   * @param string $from - The end date `Y-m-d`.
   */
  public function logs(
    string $fromDate, string $toDate, string $resolution
  ): array {
    // Validate dates.
    if (!V::date($fromDate)) {
      throw new Exception();
    }
    if (!V::date($toDate)) {
      throw new Exception();
    }

    // Create the interval between the two dates.
    $from = new DateTime($fromDate);
    $to = new DateTime($toDate);
    $fromTimestamp = $from->getTimestamp();
    $toTimestamp = $to->getTimestamp();
    $period =  new DatePeriod(
      (clone $from)->modify('first day of this month'),
      DateInterval::createFromDateString('1 month'),
      (clone $to)->modify('first day of next month'),
    );
    
    $daily = [];
    $hourly = [];

    foreach ($period as $date) {
      $logs = $this->getMonthLogs($date->format('Y'), $date->format('n'));

      // Add daily logs to the result.
      foreach ($logs['daily'] as $time => $log) {
        if ($time >= $fromTimestamp && $time < $toTimestamp) {
          $daily[$time] = $log;
        }
      }

      // Add hourly logs.
      $secondsInDay = 60 * 60 * 24;
      foreach ($logs['hourly'] as $time => $log) {
        if ($time >= $fromTimestamp && $time < $toTimestamp) {
          if ($resolution === 'hourly') {
            // Resolution is `hourly` so we can just add them to the result.
            $hourly[$time] = $log;
          } else if ($resolution === 'daily') {
            // Resolution is `daily` so we have to reduce all hourly results
            // of one day and add them to the daily log.
            $dayTime = $time - ($time % $secondsInDay);
            $day = $daily[$dayTime] ?? [];
            // dump($log);
            $day['views'] = ($day['views'] ?? 0) + $log['views'];
            $day['visits'] = ($day['visits'] ?? 0) + $log['visits'];
            $daily[$dayTime] = $day;
          }
        }        
      }
    }

    return [
      'hourly' => $hourly,
      'daily' => $daily
    ];
  }

  protected function getMonthLogs(int $year, int $month) {
    $hourly = $this->loggerHourly($year, $month, false);
    $daily = $this->loggerDaily($year, $month, false);

    if ($hourly) {
      $hourlyLogs = $hourly->read();
    }

    if ($daily) {
      $dailyLogs = $daily->read();
      foreach ($dailyLogs as &$log) {
        $log['browsers'] = (new CounterList($log['browsers']))->toArray();
        $log['referrers'] = (new CounterList($log['referrers']))->toArray();
      }
    }

    return [
      'hourly' => $hourlyLogs ?? [],
      'daily' => $dailyLogs ?? []
    ];   
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
   * Get the hourly or daily log file for the month (or the current month if
   * none is specified).
   * 
   * @param string $type - The type of the log file (hourly or daily).
   * @param int|null $year - The year
   * @param int|null $month - The month
   * @param bool $create = true - Wether or not to create the file if it doesn't
   * exist.
   * @return string - The filename
   */
  public function logFile(
    string $type, int $year = null, int $month = null, bool $create = true
  ) {
    $now = new DateTime();
    $year = $year ?? $now->format('Y');
    $month = $month ?? $now->format('m');
    $file = $this->dir() . sprintf('/%d-%02d-%s.csv', $year, $month, $type);

    if (F::exists($file)) {
      return $file;
    } else if ($create) {
      F::write($file, '');
      return $file;
    }
  }

  /**
   * Get an hourly logger for the month (or the current month if none is
   * specified).
   * 
   * @param int|null $year - The year
   * @param int|null $month - The month
   * @param bool $create = true - Wether or not to create the logger (and it's
   * log file) if it doesn't exist.
   * @return KirbyStats\HourlyLogger
   */
  protected function loggerHourly(
    int $year = null, int $month = null, bool $create = true
  ) {
    $file = $this->logFile('hourly', $year, $month, $create);
    if ($file) {
      return new HourlyLogger($file, ['views', 'visits']);
    }
  }

  /**
   * Create a daily log file for the current month (if it doesn't already
   * exist) and return a new daily logger.
   * 
   * @return KirbyStats\HourlyLogger
   */
  protected function loggerDaily(
    int $year = null, int $month = null, bool $create = true
  ) {
    $file = $this->logFile('daily', $year, $month, $create);
    if ($file) {
      return new DailyLogger($file, ['browsers', 'referrers']);
    }
  }    
}