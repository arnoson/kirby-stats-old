<?php

namespace KirbyStats;

include_once(__DIR__ . '/Analyzer.php');

class ReferrerAnalyzer extends Analyzer {
  /**
   * Check if the user is a new visitor by checking if he*she comes from
   * an external site.
   * 
   * @return Boolean
   */
  function isVisit() {
    return (
      !$this->refreshed &&
      $this->host != $this->referrerHost
    );
  }

  function isview() {
    return !$this->refreshed;
  }
}