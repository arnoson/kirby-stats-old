<?php

namespace KirbyStats;

include_once(__DIR__ . '/Analyzer.php');

/**
 * The ReferrerAnalyzer analyzes the current request based on the referrer.
 */
class ReferrerAnalyzer extends Analyzer {
  /**
   * Check if the user is a new visitor by checking if he*she comes from
   * an external site.
   * 
   * @return boolean
   */
  function isVisit(): boolean {
    return (
      !$this->refreshed() &&
      $this->host() != $this->referrerHost()
    );
  }

  /**
   * Check if the current request counts as a view. For now all request that
   * aren't reloads do. In the future we could filter bots here.
   * 
   * @return  boolean
   */
  function isview(): boolean {
    return !$this->refreshed();
  }
}