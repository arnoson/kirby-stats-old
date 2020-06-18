<?php

function super_user() {
  kirby()->impersonate('kirby');  
}

/**
 * Get all logs in the specified period.
 * 
 * @param array $logs
 * @param DateTimeInterface $from - Start of the period.
 * @param DateTimeInterface $to - End of the period.
 */
function logs_in_period(array $logs, $from, $to): array {
  $fromTimestamp = $from->getTimestamp();
  $toTimestamp = $to->getTimestamp();

  // The logs are sorted chronologically, so instead of looping over the whole
  // array we first remove the redundant logs from the beginning.
  $removeCount = 0;
  $count = count($logs);
  for ($i = 0; $i < $count; $i++) {
    if ($logs[$i]['time'] < $fromTimestamp) {
      $removeCount++;
    } else {
      break;
    }
  }
  array_splice($logs, 0, $removeCount);

  // Then we remove the redundant logs from the end.
  $removeCount = 0;
  $count = count($logs);
  for ($i = $count - 1; $i >= 0; $i--) {
    if ($logs[$i]['time'] >= $toTimestamp) {
      $removeCount++;
    } else {
      break;
    }
  }
  array_splice($logs, count($logs) - $removeCount);  

  return $logs;
}