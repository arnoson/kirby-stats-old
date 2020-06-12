<?php

namespace KirbyStats; 

include_once(__DIR__ . '/helpers.php');
include_once(__DIR__ . '/Logger/HourlyLogger.php');

use Kirby\Toolkit\F;
use \DateTime;

class PageStats {
  private $page;
  private $id;
  private $rootStats;
  private $stats;
  private $logger;

  function __construct($page) {
    $this->page = $page;
    $this->id = $page->id();
    $this->rootStats = page('kirby-stats');
    $this->stats = $this->getStats();
    $this->logger = new HourlyLogger(
      $this->stats->file('log.csv')->root(),
      ['visits', 'views']
    );
  }  

  function log($analysis) {
    $this->logger->log([
      'update' => function($data) use ($analysis) {
        $data['visits'] += $analysis['view'];
        $data['views'] += $analysis['visit'];
        return $data;
      },
      'new' => function() use ($analysis) {
        return [
          'views' => $analysis['view'],
          'visits' => $analysis['visit']
        ];
      }
    ]);
  }  

  /**
   * Create a new stats page in the CMS. We use the page as a container for
   * our log files, where the actual data is stored.
   * 
   * @param Kirby\Cms\Page $page - The page for which we want to create a stats page
   * @return Kirby\Cms\Page
   */
  private function createStats($page) {
    // Get the parent.
    $parentId = $page->parentId();
    $parentStats = $parentId
      ? $this->rootStats->find($parentId)
      : $this->rootStats;

    // Create the stats page and publish.
    try {
      super_user();
      $stats = $parentStats->createChild([
        'content' => [
          'title' => $page->slug()
        ],        
        'slug' => $page->slug(),
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

  private function getStats() {
    // First look if stats aready exist.
    $stats = $this->findStats($this->id);

    // If stats doesn't exist we create it, but first we make sure that all
    // parent's stats also exist.
    if (!$stats) {
      $parents = $this->page->parents()->flip();
      foreach ($parents as $parent) {
        if (!$this->findStats($parent->id())) {
          $this->createStats($parent);
        }
      }
      $stats = $this->createStats($this->page); 
    }     
  
    return $stats;
  }
}