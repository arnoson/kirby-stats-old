<?php

namespace KirbyStats;
use \Datetime;

class IntervalLogger {
  private $file;
  private $handle;
  private $interval;

  /**
   * @param String $file - The log file.
   * @param Array $fields - The field names.
   * @param Integer $interval - The time interval in which the logs are grouped.
   */
  function __construct($file, $fields, $interval) {
    $this->file = $file;
    $this->fields = $fields;
    // Time will always be stored as the first field.
    array_unshift($this->fields, 'time');
    $this->interval = $interval;
  }

  /**
   * Update the last log or add a new one.
   * 
   * @param Array $params
   */
  function log($params) {
    $time = $params['time'] ?? (new DateTime())->getTimestamp();
    $update = $params['update'] ?? [];
    $new = $params['new'] ?? [];

    $this->open();
    $handle = $this->handle;

    [
      'line' => $lastLine, 
      'length' => $lineLength
    ] = $this->getLastLine($handle);

    $lastLog = $this->decode($lastLine);
    $newLog;

    if ($lastLog && $time < $lastLog['time'] + $this->interval) {
      // The last log covers the current time, so we update it.
      $newLog = is_callable($update) ? $update($lastLog) : $lastLog;
      $newLog['time'] = $lastLog['time'];
      // Delete the old log in the file.
      $size = fstat($handle)['size'] - ($lineLength - 1);
      ftruncate($handle, $size);
    } else {
      // Create a new log.
      $newLog = is_callable($new) ? $new() : $new;
      // Round the current time to the commenced interval.
      $newLog['time'] = $time - ($time % $this->interval);
    }

    // Make sure the file is ending with a line break.
    fseek($handle, -1, SEEK_END);
    $lastChar = fgetc($handle);
    if ($lastChar && $lastChar !== "\n") {
      fwrite($handle, "\n");
    }
    // Append the new log to the file.  
    fwrite($handle, $this->encode($newLog) . "\n");
    $this->close();
  }

  /**
   * Read and return the log entries. Note: this reads the whole file, which
   * might no be a good solution for large log files.
   * 
   * @param bool convertNumerics - Wether or not to convert numeric values to 
   * (float) numbers.
   * @return array
   */
  public function read(bool $convertNumerics = true): array {
    $fields = $this->fields;
    $logs = array_map('str_getcsv', file($this->file));
    array_walk($logs, function(&$log) use ($fields, $convertNumerics) {
      if ($convertNumerics) {
        foreach ($log as &$value) {
          if (is_numeric($value)) {
            $value = (float) $value;
          }
        }
      }
      $log = array_combine($fields, $log);
    });
    return $logs;
  }

  /**
   * Open the log file.
   */
  private function open() {
    $this->handle = fopen($this->file, 'a+');
  }

  /**
   * Close the log file, if its still open.
   */
  function close() {
    $handle = $this->handle;
    if ($handle) {
      // We have to check the resource. Otherwise there might be an error if
      // the destructor tries to close the file and it has already been closed
      // (which should be the case if nothing went wrong).
      $type = get_resource_type($handle);
      if ($type === 'stream' || $type === 'file' ) {
        fclose($handle);
      }
    }
  }

  /**
   * Encode data to an csv string.
   * 
   * @param Array $data
   * @return String
   */
  private function encode($data) {
    $values = [];
    foreach ($this->fields as $field) {
      array_push($values, $data[$field] ?? '');
    }
    return implode(',', $values);
  }

  /**
   * Decode a csv string.
   * 
   * @param String $string
   * @return Array
   */
  private function decode($string) {
    if (!empty($string)) {
      $data = [];
      $values = explode(',', $string);
      return array_combine($this->fields, $values);
    }
  }

  /**
   * Get the last line – and it's length – of a file.
   * 
   * @return Array
   */
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

  /**
   * Make sure to always close the file.
   */
  function __destruct() {
    $this->close();
  }
}