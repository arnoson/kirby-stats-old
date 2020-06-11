<?php

require_once(__DIR__ . '/../lib/KirbyStats.php');

// KirbyStats gets initialized in the plugins index.php already, but this
// snippet might be called before that so we have to make sure that it is
// initialized  before we log.
KirbyStats::init();
KirbyStats::log($page);