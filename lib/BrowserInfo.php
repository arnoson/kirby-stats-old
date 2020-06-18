<?php

namespace KirbyStats;

require __DIR__ . '/../vendor/autoload.php';

use \Browser;

/**
 * Get basic information about the user's browser.
 */
class BrowserInfo {
  /**
   * @var string
   */
  protected $userAgent;

  /**
   * The browser name.
   * 
   * @var string
   */
  protected $name;

  /**
   * Short version of the browser name.
   * 
   * @var string
   */
  protected $shortName;  

  /**
   * The browser version.
   * @var float
   */
  protected $version;

  /**
   * The browser object used to analyze the browser.
   * 
   * @var Browser
   */
  protected $browser;

  /**
   * The browser id.
   * 
   * @var string
   */
  protected $id;

  /**
   * A list of all supported browser names and their abbreviations.
   */
  protected static $names = [
    Browser::BROWSER_CHROME => 'gc',    
    Browser::BROWSER_EDGE => 'eg',
    Browser::BROWSER_FIREFOX => 'ff',
    Browser::BROWSER_IE => 'ie',
    Browser::BROWSER_OPERA => 'o',
    Browser::BROWSER_SAFARI => 'sf'
  ];
  
  /** 
   * Create a new BrowserInfo object.
   * 
   * @param string|null $userAgent
   */
  function __construct($userAgent = null) {
    $this->userAgent = $userAgent;
  }

  /**
   * Get (and create if necessary) a browser obejct.
   * 
   * @return Browser
   */
  protected function browser() {
    return $this->browser ?? $this->browser = new Browser($this->userAgent());
  }

  /**
   * Get the user agent.
   * 
   * @return string
   */
  protected function userAgent(): string {
    return $this->userAgent ?? $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
  }

  /**
   * Get the browser name.
   */
  public function name() {
    if ($this->name === null) {
      $name = $this->browser()->getBrowser();
      $this->name = $this->isBot()
        ? 'Bot'
        : (
          array_key_exists($name, self::$names)
            ? $name
            : 'Other'
        );
    }

    return $this->name;
  }

  /**
   * Get the short browser name.
   * 
   * @param string $longName
   * @return string|null
   */
  public function shortName() {
    return (
      $this->shortName ??
      $this->shortName = self::$names[$this->name()] ?? null
    );
  }  

  public function isBot() {
    return $this->browser()->isRobot();
  }

  public function isOther() {
    return $this->name === 'Other';
  }

  /**
   * Get the browser version.
   * 
   * @return string|null
   */
  public function version() {
    return (
      $this->version ??
      $this->version = $this->isOther() ? null : $this->browser()->getVersion()
    ); 
  }

  /**
   * Get the major browser version.
   * 
   * @return int|null
   */
  public function majorVersion() {
    return $this->version() ? (int) $this->version() : null;
  }

  /**
   * Get the browser id. It is made of the browser's short name followed by
   * it's major version (example: ff@78). 
   * 
   * @return string
   */
  public function id(): string {
    if ($this->isBot()) {
      return 'bot';
    }

    if ($this->isOther()) {
      return 'other';
    }

    return $this->shortName() . '@' . $this->majorVersion();
  }

  /**
   * Get all information as an array.
   * 
   * @return array
   */
  public function toArray(): array {
    return [
      'name' => $this->name(),
      'short_name' => $this->shortName(),
      'version' => $this->version(),
      'major_version' => $this->majorVersion(),
      'id' => $this->id()
    ];
  }
}