<?php

class ReferrerAnalyzer {
  /**
   * Get the host name (without port).
   * 
   * @return String
   */
  private static function getHost() {
    return strtok($_SERVER['HTTP_HOST'], ':');
  }

  /**
   * Get the referrer's host name (seems to omit the port automatically).
   * 
   * @return String
   */
  private static function getReferrerHost() {
    if (isset($_SERVER['HTTP_REFERER'])) {
      return parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    }
  }

  /**
   * Check if the page has been refreshed.
   * 
   * @return Boolean
   */
  private static function isPageRefreshed() {
    return (
      isset($_SERVER['HTTP_CACHE_CONTROL']) &&
      (
        $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' ||  
        $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache'
      )
    );  
  }

  /**
   * Check if the user is a new visitor by checking if he*she comes from
   * an external site.
   * 
   * @return Boolean
   */
  private static function isNewVisit() {
    return (
      !self::isPageRefreshed() &&
      self::getHost() != self::getReferrerHost()
    );
  }  

  /**
   * Analyze the current request.
   * 
   * @return Array - An containing the result [view, visit].
   */
  public static function analyze() {
    return [
      // Don't count page refresh as a new visit.
      'view' => !self::isPageRefreshed(),
      'visit' => self::isNewVisit()
    ];
  }
}