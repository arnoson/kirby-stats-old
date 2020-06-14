<?php

namespace KirbyStats; 

/**
 * The Analyzer base class. All inherited classes must implement the isView()
 * and isVisit() methods.
 */
abstract class Analyzer {
  protected $host;
  protected $referrerHost;
  protected $refreshed;

  /** 
   * Analyze the current request.
   * 
   * @return array
   */
  public function analyze(): array {
    return [
      'visit' => $this->isVisit(),
      'view' => $this->isView(),
      'referrer' => $this->referrerHost
    ];
  }

  /**
   * Determin if the the request counst as a visit.
   * 
   * @return bool
   */
  abstract protected function isVisit();

  /**
   * Determin if the the request counst as a view.
   * 
   * @return bool
   */  
  abstract protected function isView();

  /**
   * Get the host name (without port).
   * 
   * @return string
   */
  protected function host(): string {
    return $this->host ?? $this->host = strtok($_SERVER['HTTP_HOST'], ':');
  }

  /**
   * Get the referrer's host name (seems to omit the port automatically).
   * 
   * @return string
   */
  protected function referrerHost(): string {
    if (isset($_SERVER['HTTP_REFERER'])) {
      return parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    }
  }

  /**
   * Check if the page has been refreshed.
   * 
   * @return bool
   */
  protected function refreshed(): bool {
    return $this->refreshed ?? $this->refreshed = (
      isset($_SERVER['HTTP_CACHE_CONTROL']) &&
      (
        $_SERVER['HTTP_CACHE_CONTROL'] === 'max-age=0' ||  
        $_SERVER['HTTP_CACHE_CONTROL'] == 'no-cache'
      )
    );
  }
}