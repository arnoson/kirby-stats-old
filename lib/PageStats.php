<?php

namespace KirbyStats; 

include_once(__DIR__ . '/helpers.php');
include_once(__DIR__ . '/Logger/HourlyLogger.php');

use Kirby\Toolkit\F;
use \DateTime;

class PageStats {
  private $id;
  private $rootStats;
  private $stats;
  private $logger;

  function __construct($id) {
    $this->id = $id;
    $this->rootStats = page('kirby-stats');
    $this->stats = $this->getStats();
    $this->logger = new HourlyLogger(
      $this->stats->file('log.csv')->root(),
      ['views', 'visits']
    );
  }  

  function log($analysis) {
    ['view' => $view, 'visit' => $visit] = $analysis;

    if (!($view || $visit)) {
      // Nothing to log.
      return;
    }

    $this->logger->log([
      'update' => function($data) use ($view, $visit) {
        $data['views'] = (int)$data['views'] + (int)$view;
        $data['visits'] = (int)$data['visits'] + (int)$visit;
        return $data;
      },
      'new' => [
        'views' => (int)$view,
        'visits' => (int)$visit
      ]
    ]);
  }  

  /**
   * Create a new stats page in the CMS. We use the page as a container for
   * our log files, where the actual data is stored.
   * 
   * @param Kirby\Cms\Page $page - The page for which we want to create a stats page
   * @return Kirby\Cms\Page
   */
  private function createStats($id) {
    $parent = $this->getParentStats($id);
    $slug = $this->getSlug($id);

    // Create the stats page and publish.
    try {
      super_user();
      $stats = $parent->createChild([
        'content' => [
          'title' => $slug
        ],        
        'slug' => $slug,
        'template' => 'stats'
      ]);
    } catch (Exception $error) {
      throw new Exception($error);
    }
    super_user();
    $stats = $stats->publish();

    // Add our log file. There we will store the stats for the page.
    // We could also store it in the stats page directly, but using a dedicated 
    // csv file is much more efficient.
    $statsDir = kirby()->root('content') . '/' . $stats->diruri();
    F::write($statsDir . '/log.csv', '');
    F::write($statsDir . '/meta-log.csv', '');

    return $stats;    
  }

  private function findStats($id) {
    return $this->rootStats->find($id);
  }

  function getSlug($id) {
    $parts = explode('/', $id);
    return array_pop($parts);
  }

  function getParentStats($id) {
    $parts = explode('/', $id);
    array_pop($parts);

    if (count($parts)) {
      $parentId = implode('/', $parts);
      return $this->findStats($parentId) ?? $this->createStats($parentId); 
    } else {
      return $this->rootStats;
    }
  }

  private function getStats() {
    return $this->findStats($this->id) ?? $this->createStats($this->id);
  }
}