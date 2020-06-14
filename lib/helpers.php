<?php

function super_user() {
  kirby()->impersonate('kirby');  
}

/**
 * Get the browser name.
 * @see http://stackoverflow.com/a/20934782/4255615
 * @return string
 */
function browser_name(string $userAgent = null): string {
  $userAgent = $userAgent ?? $_SERVER['HTTP_USER_AGENT'];

  if (
    strpos(strtolower($userAgent), 'safari/') &&
    strpos(strtolower($userAgent), 'opr/')
  ) {
    // Opera
    $result = 'Opera';
  } elseif (
    strpos(strtolower($userAgent), 'safari/') &&
    strpos(strtolower($userAgent), 'chrome/')
  ) {
    // Chrome
    $result = 'Chrome';
  } elseif (
    strpos(strtolower($userAgent), 'msie') ||
    strpos(strtolower($userAgent), 'trident/')
  ) {
    // Internet Explorer
    $result = 'Internet Explorer';
  } elseif (strpos(strtolower($userAgent), 'firefox/')) {
    // Firefox
    $result = 'Firefox';
  } elseif (
    strpos(strtolower($userAgent), 'safari/') &&
    (strpos(strtolower($userAgent), 'opr/') === false) &&
    (strpos(strtolower($userAgent), 'chrome/') === false)
  ) {
    // Safari
    $result = 'Safari';
  } else {
    // Out of data
    $result = false;
  }

  return $result;
}