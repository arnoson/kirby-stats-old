<?php

return [
  [
    'pattern' => 'stats/(:any)/(:any)/(:any)/(:any?)',
    'action' => include __DIR__ . '/stats.php'
  ]  
];