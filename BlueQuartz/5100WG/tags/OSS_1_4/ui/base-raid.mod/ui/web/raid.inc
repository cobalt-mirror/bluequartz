<?

include("base/am/am_detail.inc");

function raid_table($factory, $cce)
{
    $i18n = $factory->i18n;

    $amobj = $cce->getObject("ActiveMonitor");
    $nspace = $cce->get($amobj["OID"], "RAID");

    $raidState = explode(",", `/usr/sausalito/swatch/bin/raidState.pl -w`);
    $state = $raidState[0];

    $name = "raid_title";

    switch ($state) {
	case 0: 
		$stateMessage = "raid_disabled";
		$stateBall = "none";
		$refresh = 900;
		break;
	case 1:
		$stateMessage = "raid_working";
		$stateBall = "normal";
		$refresh = 900;
		break;
	case 2: 
		$stateMessage = "raid_sync_in_progress";
		$stateBall = "problem";
		$refresh = 15;
		break;
	case 3:
		$stateMessage = "raid_failure_qube";
		$stateBall = "severeProblem";
		$refresh = 45;
		break;
	case 4:
		$stateMessage = "raid_failure_hda_qube";
		$stateBall = "severeProblem";
		$refresh = 45;
		break;
	case 5:
		$stateMessage = "raid_failure_hdc_qube";
		$stateBall = "severeProblem";
		$refresh = 45;
		break;
    }

    $refresh = $refresh * 1000; # convert to milliseconds
    $stateMessage = "[[base-raid.$stateMessage]]";
    $msg = "";

    if ($state == 2) {
	$sync_complete = $raidState[1]; # Sync in progress, numeric 0-100

	$eta = $raidState[2];
	# adjust for rounding in RAID.pm
	$eta = (($eta == 0) && $sync_complete < 100) ? 0.5 : $eta;
	
	$progressBar = $factory->getBar("raidSyncCompleted", $sync_complete);
	$progressBar->setLabel(" ");
	$timeMessage = $sync_complete . " % [[base-raid.raid_completed]]" .
	 		", " . $eta . 
			" [[base-raid.raid_minutes_remaining]]";
	$msg = $factory->getVerticalCompositeFormField( array(
		$factory->getTextField("one",  
			$i18n->get("$stateMessage"), "r"),
		$progressBar,
		$factory->getTextField("last", 
			$i18n->get($timeMessage), "r")
		), 
		"", "r");
    } else {
	$msg = $factory->getTextField("one", $i18n->get($stateMessage), "r");
    }

    // get the last changed time
    if ($nspace["lastChange"]) {
	$date = $nspace["lastChange"];
    } else {
	$date = "";
    }

    $icon = $factory->getStatusSignal($stateBall);
    am_detail_block_core($factory, $name, $icon, $msg, $date);

    print("<SCRIPT LANGUAGE=\"javascript\">	
       setTimeout(\"location.reload(true)\", $refresh); </SCRIPT>");
}

?>

