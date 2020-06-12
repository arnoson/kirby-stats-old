<?php

namespace KirbyStats;

include_once(__DIR__ . '/TimeGroupLogger.php');

class HourlyLogger extends TimeGroupLogger {
  function __construct($file, $fields) {
    $secondsInHour = 60 * 60;
    parent::__construct($file, $fields, $secondsInHour);
  }  
}