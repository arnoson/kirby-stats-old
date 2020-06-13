<?php

namespace KirbyStats;

include_once(__DIR__ . '/IntervalLogger.php');

class HourlyLogger extends IntervalLogger {
  function __construct($file, $fields) {
    $secondsInHour = 60 * 60;
    parent::__construct($file, $fields, $secondsInHour);
  }  
}