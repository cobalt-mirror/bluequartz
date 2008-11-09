<?php
// $Id: fans.inc 266 2004-01-10 15:35:23Z shibuya $
function fans_status() {

  $faninfo = @fopen("/proc/cobalt/faninfo", "r");
  if ($faninfo) {
    // get fan status
    while (!feof($faninfo)) {
      $line = fgets($faninfo, 1024);
      if (ereg('^[[:space:]]*$', $line)) {
	//blank line
	continue;
      }
      list ($null, $fanNumber, $null, $speed) = split ("[[:space:]]+", $line);
      $id = 'fan' . $fanNumber;
      $fans_status[$id]['state'] = $speed;
    }
  }
  return $fans_status;
}

function fans_conf() {
  //parse fans.conf
  //return
  $conf_file = fopen('/usr/sausalito/swatch/fans.conf', 'r');
  if (!$conf_file) {
    return;
  }
  while (!feof($conf_file)) {
    $line = fgets($conf_file, 4096);
    $line = trim($line);
    if (ereg('^#', $line) || ereg('^[[:space:]]*$', $line)) {
      //comment or blank line
      continue;
    } 
    if (preg_match('/^(.*?):/', $line, $info)) {
      $fan = $info[1];
      continue;
    }
    list ($key, $val) = split('=', $line);
    $conf[$fan][$key] = $val;
  }
  return $conf;
}
