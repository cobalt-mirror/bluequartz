<?
function drive_status($cce, $helper) {

  list ($am_oid) = $cce->find('ActiveMonitor');
  
  $severity = array('N' => 0, 'G' => 1, 'Y' => 2, 'R' => 3,
		    0 => 'N', 1 => 'G', 2 => 'Y', 3 => 'R');
  $baddrives = array();

  $worst = 'N';
  // generate list of bad drives
  // get dma drives from parsing currentMessage entry of DMA namespace of AM
  // status = currentState
  $dmaobj = $cce->get($am_oid, 'DMA');
  $state = $dmaobj['currentState'];
  $lines = explode("\n", $dmaobj['currentMessage']);
  if ($severity[$state] > $severity['G']) { 
    foreach ($lines as $line) {
      if (preg_match('/drives=\"(.+?)\"/', $dmaobj['currentMessage'], $regs)) {
	$drives = split("([[:space:]]|,)+", $regs[1]);
	foreach ($drives as $drive) {
	  if (eregi("off", $line)) {
	    $msg = '[[base-dma.disk_dma_off]]';
	  } else if (eregi("fixed", $line)) {
	    $msg = '[[base-dma.disk_dma_fixed]]';
	  }
	  
	  // get worst state
	  $laststate = $baddrives[$drive]['state'];
	  if (!$laststate) {
	    $baddrives[$drive]['state'] = $state;
	  } else if ($severity[$state] > $severity[$laststate]) {
	    $baddrives[$drive]['state'] = $state;
	  }
	  
	  //add msg
	  $lastmsgs = $baddrives[$drive]['msgs'];
	  if (!$lastmsgs) {
	    $baddrives[$drive]['msgs'] = array($msg);
	  } else {
	    array_push($baddrives[$drive]['msgs'], $msg);
	  }
	}
      }
    }
  }

  //get smart drives from parsing currentMessage entry of SMART namespace of AM
  //status = currentState
  $smartobj = $cce->get($am_oid, 'SMART');
  $state = $smartobj['currentState'];
  $lines = explode("\n", $smartobj['currentMessage']);
  if ($severity[$state] > $severity['G']) {
    foreach ($lines as $line) {
      if (preg_match('/drives=\"(.+?)\"/', $line, $regs)) {
	$drives = split("([[:space:]]|,)+", $regs[1]);
	foreach ($drives as $drive) {
	  if (eregi("turned_off", $line)) {
	    $msg = '[[base-smart.drive_off]]';
	  } else if (eregi("unsafe", $line)) {
	    $msg = '[[base-smart.unsafe_drive]]';
	  } else if (eregi("standalone", $line)) {
	    $msg = '[[base-smart.unsafe_drive_standalone]]';
	  } else if (eregi("smartdeclining", $line)) {
	    $msg = '[[base-smart.drive_declining]]';
	  }
	  
	  // get worst state
	  $laststate = $baddrives[$drive]['state'];
	  if (!$laststate) {
	    $baddrives[$drive]['state'] = $state;
	  } else if ($severity[$state] > $severity[$laststate]) {
	    $baddrives[$drive]['state'] = $state;
	  }
	  
	  //add msg
	  $lastmsgs = $baddrives[$drive]['msgs'];
	  if (!$lastmsgs) {
	    $baddrives[$drive]['msgs'] = array($msg);
	  } else {
	    array_push($baddrives[$drive]['msgs'], $msg);
	  }
	}
      }
    }
  }

  //RAID state is relatively realtime, especially in a syncing situation
  //so we get the data directly from the AM script
  $currentMessage = '';
  $currentState = $helper->shell("/usr/sausalito/swatch/bin/raidState.pl", $currentMessage);

  $raidobj['currentMessage'] = $currentMessage;
  $raidobj['currentState'] = $severity[$currentState];
  $state = $raidobj['currentState'];
  if ($severity[$state] > $severity['G']) {
    if (preg_match('/drives=\"(.+?)\"/', $raidobj['currentMessage'], $regs)) {
      $drives = split("([[:space:]]|,)+", $regs[1]);
      foreach ($drives as $drive) {
	if (eregi("raid_failure", $raidobj['currentMessage'])) {
	  $msg = '[[base-raid.drive_failed]]';
	} else if (eregi("raid0_failure", $raidobj['currentMessage'])) {
	  $msg = '[[base-raid.drive_failed_in_raid0]]';
	} else if (eregi("sync", $raidobj['currentMessage'])) {
	  $msg = '[[base-raid.drive_syncing]]';
	}
	$drive = ereg_replace("[[:digit:]]+$", '', $drive);

	// get worst state
	$laststate = $baddrives[$drive]['state'];
	if (!$laststate) {
	  $baddrives[$drive]['state'] = $state;
	} else if ($severity[$state] > $severity[$laststate]) {
	  $baddrives[$drive]['state'] = $state;
	}
	
	// yuck.
	// raid error messages override all others
	$baddrives[$drive]['msgs'] = array($msg);
      }
    }
  }
  
  // get raid device configuration from /etc/raidtab
  $status = array();
  $raid = array();
  $raidtabfile = fopen('/etc/raidtab', 'r');
  if (!$raidtabfile) {
    error_log("couldn't open /etc/raidtab");
  }
  while (!feof($raidtabfile)) {
    $line = fgets($raidtabfile, 1024);
    list($header, $data) = split("[[:space:]]+", $line);
    if ($header == "raiddev") {
      $device = $data;
      $raid[$device] = array();
    } else if ($header == "device") {
      array_push($raid[$device], $data);
    }
  }
  fclose($raidtabfile);
  
  // generate list of all physical drives installed in system by
  // cross referencing /etc/raidtab with cce Disk objects
  $disk_oids = $cce->findx('Disk', array(), array( 'device' => '^/dev/'));
  foreach ($disk_oids as $oid) {
    $disk = $cce->get($oid);
    if ($raid[$disk['device']]) {
      foreach ($raid[$disk['device']] as $device) {
	$device = ereg_replace("[[:digit:]]+$", '', $device);
	$alldrives[$device] = 1;
      }
    } else {
      $device = ereg_replace("[[:digit:]]+$", '', $disk['device']);
      $alldrives[$device] = 1;
    }
  }
  //using a hash to remove duplicates
  $alldrives = array_keys($alldrives);
  
  // combine. if not bad, it's good
  foreach ($alldrives as $drive) {
    if ($baddrives[$drive]) {
      $status[$drive] = $baddrives[$drive];
    } else {
      $status[$drive]['state'] = 'G';
      $status[$drive]['msgs'] = array("[[base-raid.drive_ok]]");
    }
  }

  return $status;
}
?>
