<?php

require_once __DIR__ . '/../../lib/KirbyStats.php';

return function($id, $from, $to, $resolution = 'hourly') {
  $stats = KirbyStats::stats($id);
  return [
    'status' => 'ok',
    'data' => $stats->logs($from, $to, $resolution)
  ];
};