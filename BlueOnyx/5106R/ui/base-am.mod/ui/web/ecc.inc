<?php
// $Id: ecc.inc 266 2004-01-10 15:35:23Z shibuya $

function ecc_status($cce) {

  // Get the number of RAM slots
  $numchips = `cat /proc/cobalt/raminfo | wc -l`;

  // Figure out which chips we have.
  for ($i=0; $i<$numchips; $i++) {
    if (`/bin/egrep ^$i /proc/cobalt/raminfo | grep -v Empty`) {
      $chip[$i] = 1;
    } else {
      $chip[$i] = 0;
    }
  }

  list($am_oid) = $cce->find('ActiveMonitor');
  $ecc = $cce->get($am_oid, 'ECC');

  // Set all chips we have to green by default
  for ($i=0; $i<$numchips; $i++) {
    if ($chip[$i]) {
      $status["dimm$i"] = array('state' => 'G',
	       'msgs' => array('[[base-am.no_ecc_errors]]'));
    // And the empty slots to empty by default
    } else {
      $status["dimm$i"] = array('state' => 'N',
	       'msgs' => array('[[base-am.slotempty]]'));
    }
  }
  
  // Nongreen state... set all bad chips to the necesary color.
  if ($ecc['currentState'] != 'G') {
    if (preg_match('/bad=\"(.+?)\"/', $ecc['currentMessage'], $regs)) {
      $badchips = split("([[:space:]]|,)+", $regs[1]);
      foreach (array_values($badchips) as $bad) {
         if ($ecc['currentState'] == 'Y') {
           $status["dimm$bad"] = array('state' => 'Y',
	       'msgs' => array('[[base-am.corr_ecc_errors]]'));
         } else {
           $status["dimm$bad"] = array('state' => 'R',
	       'msgs' => array('[[base-am.bad_ecc_errors]]'));
         }
      }
    }
  }
  return $status;
}  
