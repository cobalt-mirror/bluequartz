<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * BlueOnyx Drives Helper
 *
 * This provides some core functions for the RAID Active Monitor
 *
 */

function drive_status($cce, $helper) {

  $CI =& get_instance();

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
	$drives = preg_split("/([[:space:]]|,)+/", $regs[1]);
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
	$drives = preg_split("/([[:space:]]|,)+/", $regs[1]);
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
  $sessionId = $CI->input->cookie('sessionId');
  $currentState = $helper->shell("/usr/sausalito/swatch/bin/raidState.pl", $currentMessage, "root", $sessionId);

  $raidobj['currentMessage'] = $currentMessage;
  $raidobj['currentState'] = $severity[$currentState];
  $state = $raidobj['currentState'];
  if ($severity[$state] > $severity['G']) {
    if (preg_match('/drives=\"(.+?)\"/', $raidobj['currentMessage'], $regs)) {
      $drives = preg_split("/([[:space:]]|,)+/", $regs[1]);
      foreach ($drives as $drive) {
	if (eregi("raid_failure", $raidobj['currentMessage'])) {
	  $msg = '[[base-raid.drive_failed]]';
	} else if (eregi("raid0_failure", $raidobj['currentMessage'])) {
	  $msg = '[[base-raid.drive_failed_in_raid0]]';
	} else if (eregi("sync", $raidobj['currentMessage'])) {
	  $msg = '[[base-raid.drive_syncing]]';
	}
	$drive = preg_replace("/[[:digit:]]+$/", '', $drive);

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
  if (! file_exists('/etc/raidtab')) {
    return $status;
  }
  $raid = array();
  $raidtabfile = fopen('/etc/raidtab', 'r');
  if (!$raidtabfile) {
    error_log("couldn't open /etc/raidtab");
  }
  $header = array();
  $data = array();
  while (!feof($raidtabfile)) {
    $line = trim(fgets($raidtabfile, 1024));
    @list($header, $data) = preg_split("/[[:space:]]+/", $line);
    if ($header == "raiddev") {
      $device = $data;
      $raid[$device] = array();
    } else if ($header == "device") {
      array_push($raid[$device], $data);
    }
  }
  fclose($raidtabfile);
  
  $alldrives = array();

  if ($cce->find('Disk')) {
  // generate list of all physical drives installed in system by
  // cross referencing /etc/raidtab with cce Disk objects
  $disk_oids = $cce->findx('Disk', array(), array( 'device' => '^/dev/'));
  foreach ($disk_oids as $oid) {
    $disk = $cce->get($oid);
    if (isset($raid[$disk['device']])) {
      foreach ($raid[$disk['device']] as $device) {
      	$device = preg_replace("/[[:digit:]]+$/", '', $device);
      	$alldrives[$device] = 1;
      }
    } else {
      $device = preg_replace("/[[:digit:]]+$/", '', $disk['device']);
      $alldrives[$device] = 1;
    }
  }
  } else {
    foreach (array_keys($raid) as $mddev) {
      foreach ($raid[$mddev] as $dev) {
        $storage = preg_replace("/[[:digit:]]+$/", '', $dev);
        $alldrives[$storage] = 1;
      }
    }
  }
  
  //using a hash to remove duplicates
  $alldrives = array_keys($alldrives);
  
  // combine. if not bad, it's good
  foreach ($alldrives as $drive) {
    if (isset($baddrives[$drive])) {
      $status[$drive] = $baddrives[$drive];
    } else {
      $status[$drive]['state'] = 'G';
      $status[$drive]['msgs'] = array("[[base-raid.drive_ok]]");
    }
  }

  return $status;
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>