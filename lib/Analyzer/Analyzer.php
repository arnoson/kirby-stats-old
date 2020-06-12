<?php

namespace KirbyStats;

class Analyzer {
  public $host;
  public $referrerHost;
  public $refreshed;
  
  function __construct() {
    $this->host = $this->getHost();
    $this->referrerHost = $this->getReferrerHost();
    $this->refreshed = $this->isPageRefreshed();
  }

  function analyze() {
    return [
      'visit' => $this->isVisit(),
      'view' => $this->isView(),
      'referrer' => $this->referrerHost
    ];
  }

  /**
   * Get the host name (without port).
   * 
   * @return String
   */
  function getHost() {
    return strtok($_SERVER['HTTP_HOST'], ':');
  }

  /**
   * Get the referrer's host name (seems to omit the port automatically).
   * 
   * @return String
   */
  function getReferrerHost() {
    if (isset($_SERVER['HTTP_REFERER'])) {
      return parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    }
  }

  /**
   * Check if the page has been refreshed.
   * 
   * @return Boolean
   */
  function isPageRefreshed() {
    return (
      isset($_SERVER['HTTP_CACHE_CONTROL']) &&
      (
        $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' ||  
        $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache'
      )
    );  
  }
}