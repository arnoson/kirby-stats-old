<?php

namespace KirbyStats;

include_once(__DIR__ . '/IntervalLogger.php');

class DailyLogger extends IntervalLogger {
  function __construct($file, $fields) {
    $secondsInDay = 60 * 60 * 24;
    parent::__construct($file, $fields, $secondsInDay);
  }  
}