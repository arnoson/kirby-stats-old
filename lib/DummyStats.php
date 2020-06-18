<?php

namespace KirbyStats;

use \DateTime;
use \DateInterval;
use \DatePeriod;

require_once __DIR__ . '/PageStats.php';

/**
 * A version of PageStats where it is possible to specify the log time to create
 * dummy logs. This isn't possible with the PageStats, because the logs have to 
 * be in chronological order. Allowing to log a different time than the current
 * could result in broken log files!
 */
class DummyStats extends PageStats {
  public function dummyLog(int $time, array $analysis) {
    extract($analysis);

    if ($view || $visit) {
      $this->logHourly($time, $analysis);
    }

    if ($visit) {
      $this->logDaily($time, $analysis);
    }    
  }

  public function createDummyLogs(string $fromDate, string $toDate) {
    // Create the interval between the two dates.
    $period =  new DatePeriod(
      new DateTime($fromDate),
      DateInterval::createFromDateString('1 hour'),
      new DateTime($toDate)
    );
  
    $browsers = ['ff@78', 'gc@84', 'ie@8'];
    
    foreach ($period as $date) {
      $count = rand(0, 100);
  
      for ($i = 0; $i < $count; $i++) {
        $this->dummyLog(
          $date->getTimestamp(),
          [
            'browser' => [
              'id' => $browsers[array_rand($browsers)]
            ],
            'visit' => rand(0,1),
            'view' => true
          ]
        );
      }
    }     
  }
}