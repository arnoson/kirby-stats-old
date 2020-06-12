<?php

namespace KirbyStats;

include_once(__DIR__ . '/TimeGroupLogger.php');

class DailyLogger extends TimeGroupLogger {
  function __construct($file, $fields) {
    $secondsInDay = 60 * 60 * 24;
    parent::__construct($file, $fields, $secondsInDay);
  }  
}