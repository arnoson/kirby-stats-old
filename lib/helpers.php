<?php

function super_user() {
  kirby()->impersonate('kirby');  
}