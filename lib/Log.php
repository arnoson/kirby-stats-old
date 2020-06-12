<?php

namespace KirbyStats;

class Log {
  public static function encode($array) {
    $data = [];
    // Omit the keys.
    foreach ($array as $value) {
      array_push($data, $value);
    }
    return implode(',', $data);
  }

  public static function decode($string) {
    if (!empty($string)) {
      [$time, $views, $visits] = explode(',', $string);
      return [
        'time' => (int)$time,
        'views' => (int)$views,
        'visits' => (int)$visits
      ]; 
    }
  }
}