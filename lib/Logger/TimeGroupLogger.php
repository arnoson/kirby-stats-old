<?php

namespace KirbyStats;
use \Datetime;

class TimeGroupLogger {
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

    [
      'line' => $lastLine, 
      'length' => $lineLength
    ] = $this->getLastLine($this->handle);

    $lastLog = $this->decode($lastLine);
    $newLog;

    if ($lastLog && $time < $lastLog['time'] + $this->interval) {
      // The last log covers the current time, so we update it.
      $newLog = is_callable($update) ? $update($lastLog) : $lastLog;
      $newLog['time'] = $lastLog['time'];
      // Delete the old log in the file.
      $size = fstat($this->handle)['size'] - ($lineLength - 1);
      ftruncate($this->handle, $size);
    } else {
      // Create a new log.
      $newLog = is_callable($new) ? $new() : $new;
      // Round the current time to the commenced interval.
      $newLog['time'] = $time - ($time % $this->interval);
    }

    // Append the new log to the file.
    fwrite($this->handle, $this->encode($newLog) . "\n");
    $this->close();
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
      foreach ($this->fields as $index => $field) {
        $data[$field] = $values[$index] ?? null;
      }
      return $data;
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