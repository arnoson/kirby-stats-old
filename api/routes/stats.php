<?php

require_once __DIR__ . '/../../lib/KirbyStats.php';

return function($id, $from, $to = null) {
  $stats = KirbyStats::stats($id);
  // If no to-date is passed, we use the next day.
  $to = $to ?? (new DateTime($from))->modify('+1 day')->format('Y-m-d');
  return [
    'status' => 'ok',
    'data' => $stats->logs($from, $to)
  ];
};