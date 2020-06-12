<?php

namespace KirbyStats;
use \Datetime;

include_once(__DIR__ . '/Log.php');

class Logger {
  private $stats;
  private $logFile;

  function __construct($stats) {
    $this->stats = $stats;
    $this->logFile = $stats->file('log.csv')->root();
  }

  function log($view, $visit, $referrer) {
    if (!$view && !$visit) {
      // Nothing to log.
      return;
    }

    $handle = fopen($this->logFile, 'a+');

    [
      'line' => $lastLine,
      'length' => $lineLength
    ] = $this->getLastLine($handle);
    $lastLog = Log::decode($lastLine);

    $log;
    $time = (new DateTime())->getTimestamp();
    if ($lastLog && $time < $lastLog['time'] + 3600) {
      // The last log covers the current time, so we update it.
      $log = $lastLog;
      $log['views'] += $view ? 1 : 0;
      $log['visits'] += $visit ? 1 : 0;
      // Delete the old log.
      $size = fstat($handle)['size'] - ($lineLength - 1);
      ftruncate($handle, $size);
    } else {
      // Create a new log.
      $log = [
        // Round to last hour.
        'time' => $time - ($time % 3600),
        'views' => $view ? 1 : 0,
        'visits' => $visit ? 1 : 0
      ];
    }

    fwrite($handle, Log::encode($log) . "\n");
    fclose($handle);
  }

  private function encodeLog($array) {
    return implode(',', $array);
  }

  private function decodeLog($string) {
    return !empty($string) ? explode(',', $string) : null;
  }

  private function getLastLine($handle) {
    $line = '';
    $cursor = -1;
    $lenght = 0;
    
    fseek($handle, $cursor, SEEK_END);
    $char = fgetc($handle);
  
    // Trim trailing newline chars of the file.
    while ($char === "\n" || $char === "\r") {
      $lenght++;
      fseek($handle, $cursor--, SEEK_END);
      $char = fgetc($handle);
    }
  
    // Read until the start of file or first newline char.
    while ($char !== false && $char !== "\n" && $char !== "\r") {
      $lenght++;
      // Prepend the new char.
      $line = $char . $line;
      fseek($handle, $cursor--, SEEK_END);
      $char = fgetc($handle);
    }
    
    return [
      'line' => $line,
      'length' => $lenght
    ];    
  }
}