<?

include_once("base/am/am_detail.inc");

function raid_table($factory, $cce, $helper)
{
    $i18n = $factory->i18n;

    // this info is always up to date, 
    // since we're get it directly from raid_amdetails.pl
    $date = time();

    $name = "raid_title";

    // get RAID config
    $oid = $cce->find('System');
    if (!$oid) {
        // not found
    }
    $raid = $cce->get($oid[0], 'RAID');
    $level = $raid['level'];
    $disks = $raid['disks'];

    // if raid is not setup, then try to figure out what the default is
    if ($level === '') {
        if ($disks == 1) {
                $level = 0;
        }
        elseif ($disks == 2) {
                $level = 1;
        }
        elseif ($disks == 3) {
                $level = 1;
        }
        elseif ($disks == 4) {
                $level = 10;
        }
        else {
                $level = 0;
        }
    }

    if ($disks == 0) {
      $config_string = "[[base-raid.raid_disabled]]";
    } else {
      $config_string = "[[base-raid.config_raid$level,numdisks=$disks,plural=\"" . 
      ($disks == 1 ? '' : 's') . "\"]]";  
    }

    $ret = $helper->shell("/usr/sausalito/swatch/bin/raid_amdetails.pl", $raidState);
    $raidState = explode("\n", $raidState);
    $state = array_shift($raidState);

    switch ($state) {
	case "noraid": 
		$stateMessage = "raid_disabled";
		$stateBall = "none";
		$refresh = 900;
		break;
	case "raidOK":
		$stateMessage = "raid_working";
		$stateBall = "normal";
		$refresh = 900;
		break;
	case "syncing": 
		$stateMessage = "raid_sync_in_progress";
		$stateBall = "problem";
		$refresh = 30;
		break;
        case "incomplete":
	        $stateMessage = "raid_sync_incomplete";
		$stateBall = "problem";
		$refresh = 30;
		break;
	case "fail":
		if ($level == '0') {
			$stateMessage = "raid0_failure";
		}
		else {
			$stateMessage = "raid_failure";
		}
		$stateBall = "severeProblem";
		$refresh = 45;
		break;
    	default:
	  	$stateMessage = " ";
		$stateBall = "none";
		$refresh = 60;
		break;
    }

    $refresh = $refresh * 1000; # convert to milliseconds
    $stateMessage = ($stateMessage)? "[[base-raid.$stateMessage]]": "";

    $msg = "";

    if ($state == "syncing") {
      foreach ($raidState as $drive_info) {
	$pieces = explode(",", $drive_info);
	foreach ($pieces as $piece) {
	  $regs = array();
	  if (ereg('part=(.*)', $piece, $regs)) {
	    $part = $regs[1];
	  } else if (ereg('percent=(.*)', $piece, $regs)) {
	    // if we have more than one syncing drive,
	    // take the minimum percentage
	    $percent = $percent ? min($percent, $regs[1]) : $regs[1];
	  } else if (ereg('eta=(.*)', $piece, $regs)) {
	    // if we have more than one syncing drive,
	    // take the MAX eta
	    $eta = $eta ? max($eta, $regs[1]) : $regs[1];
	  }
	}
      }

      $sync_complete = $percent; 
      if ($eta == "delayed") {
	$timeMessage = $sync_complete . " % [[base-raid.raid_completed]]" .
	  ", [[base-raid.raid_sync_delayed]]";
      } else if ($eta < 1) {
	$timeMessage = $sync_complete . " % [[base-raid.raid_completed]]" .
	  ", [[base-raid.raid_less_than_one_remaining]]";
      } else {
	$eta = floor($eta);
	$timeMessage = $sync_complete . " % [[base-raid.raid_completed]]" .
	  ", " . $eta . 
	  " [[base-raid.raid_minutes_remaining]]";
      }
      
      $progressBar = $factory->getBar("raidSyncCompleted", $sync_complete);
      $progressBar->setLabel(" ");
      $msg = $factory->getVerticalCompositeFormField(array(), "", "r");
      $msg->addFormField($factory->getTextField("config",
				  $i18n->get($config_string), "r"));
      $msg->addFormField($factory->getTextField("one",  
				  $i18n->get($stateMessage), "r"));
      $msg->addFormField($progressBar);
      $msg->addFormField($factory->getTextField("last", 
				  $i18n->get($timeMessage), "r"));


			 

//        $msg = $factory->getVerticalCompositeFormField( 
//  	     array(
//  		   $factory->getTextField("one",  
//  			     $i18n->get("$stateMessage"), "r"),
//  		   $progressBar,
//  		   $factory->getTextField("last", 
//  			     $i18n->get($timeMessage), "r")), 
//  	     "", "r");

      $msg->setAlignment("left");
    } else {
      $msg = $factory->getVerticalCompositeFormField(array(), "", "r");
      $msg->addFormField($factory->getTextField("config",
						$i18n->get($config_string), "r"));
      $msg->addFormField($factory->getTextField("one", $i18n->get($stateMessage), "r"));
      $msg->setAlignment("left");
    }
    $icon = $factory->getStatusSignal($stateBall);
    am_detail_block_core($factory, $name, $icon, $msg, $date);

    print("<SCRIPT LANGUAGE=\"javascript\">	
       setTimeout(\"location.reload(true)\", $refresh); </SCRIPT>");
}
