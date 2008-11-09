<?

// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.

function am_detail_block($factory, $cce, $nsname, $name)
{
    $i18n = $factory->i18n;

    $amobj = $cce->getObject("ActiveMonitor");
    if ($amobj == null) {
	$msg = $factory->getTextBlock("", 
		$i18n->interpolate("[[base-am.amObjNotFound]]"), "r");
	print($msg->toHtml());
	print("<BR>");
    } else {
	$amenabled = $amobj["enabled"];

	$stmap = array(
		"N" => "none", 
		"G" => "normal", 
		"Y" => "problem", 
		"R" => "severeProblem"
	);

	$nspace = $cce->get($amobj["OID"], $nsname);

	// make sure we have a good block name
	if ($name == "") {
		$name = $nspace["nameTag"];
	}

	// get the status icon
	if (!$amenabled) {
		$icon = $factory->getStatusSignal("noMonitor");
	} else if (!$nspace["enabled"]) {
		$icon = $factory->getStatusSignal("disabled");
	} else if (!$nspace["monitor"]) {
		$icon = $factory->getStatusSignal("noMonitor");
	} else {
		$icon = $factory->getStatusSignal(
			$stmap[$nspace["currentState"]]);
	}

	// get the current message for this service
	if (!$amenabled) {
		$imsg = $i18n->interpolate("[[base-am.amNotMonitored]]");
	} else if (!$nspace["enabled"]) {
		$imsg = $i18n->interpolate("[[base-am.amNotEnabled]]");
	} else if (!$nspace["monitor"]) {
		$imsg = $i18n->interpolate("[[base-am.amNotMonitored]]");
	} else {
		if ($nspace["currentMessage"]) {
			$imsg = $i18n->interpolate($nspace["currentMessage"]);
		} else {
			$imsg = $i18n->interpolate("[[base-am.amNoMessage]]");
		}
	}

	if ($nspace["lastChange"]) {
		$date = $nspace["lastChange"]; 
	} else {
		$date = "";
	}

	$msgfield = $factory->getTextField("msg", $imsg, "r");
	am_detail_block_core($factory, $name, $icon, $msgfield, $date);
    }
}

function am_detail_block_core($factory, $name, $icon, $msgfield, $date)
{
    $i18n = $factory->i18n;

    // get the main block 
    $mainblock = $factory->getPagedBlock($i18n->interpolate($name));

    // add status to the block
    $statline = $factory->getCompositeFormField(array($icon, $msgfield), 
	"&nbsp&nbsp", "r");

    $mainblock->addFormField($statline, $factory->getLabel("amClientStatus"));

    // add date to the block
    if ($date != "") {
        $datefield = $factory->getTimeStamp("lastchange", 
            $date, "datetime", "r");
    } else {
        $datefield = $factory->getTextField("lastchange", 
            $i18n->interpolate("[[base-am.amNever]]"), "r");
    }
    $mainblock->addFormField($datefield,
	$factory->getLabel("amClientLastChange"));
		
    // print it 
    print($mainblock->toHtml());
}

function am_back($factory)
{
    $back = $factory->getBackButton("/base/am/amStatus.php");

    print("<br>");
    print($back->toHtml());
}

?>
