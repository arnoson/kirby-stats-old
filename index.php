<?php

Kirby::plugin('arnoson/kirby-stats', [
  'options' => [
    'dir' => 'stats'
  ],

  'snippets' => [
    'stats' => __DIR__ . '/snippets/stats.php'
  ],

  'blueprints' => [
    'pages/stats' => __DIR__ . '/blueprints/pages/stats.yml',
    'pages/page-stats' => __DIR__ . '/blueprints/pages/page-stats.yml',
  ],

  'templates' => [
    'stats' => __DIR__ . '/templates/stats.php',
    'root-stats' => __DIR__ . '/templates/root-stats.php',
  ],

  'api' => include __DIR__ . '/api/api.php'
]);