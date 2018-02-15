<?

// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.
// Use this to create a generic details page:
//   declare the variables 'name' (namespace) and 'title' (i18n tag or str), 
//   then just include this file.

include_once("ServerScriptHelper.php");
include_once("base/am/am_detail.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-network");
$page = $factory->getPage();
$i18n = $factory->i18n;

print($page->toHeaderHtml());

am_detail_block($factory, $cce, 'Network', '[[base-network.amNetDetails]]');

$page = $factory->getPage();
$i18n = $factory->i18n;

if (file_exists( "/proc/user_beancounters" )) {
	// OpenVZ Network Interfaces:
	$list = $factory->getScrollList("amNetStats", array(' ', 'venet0', 'venet1')); 
}
else {
	// Regular Network Interfaces:
	$list = $factory->getScrollList("amNetStats", array(' ', 'eth0', 'eth1'));
}

$list->setEntryCountHidden(true);
// FIXME make column widths match the paged block
$list->setColumnWidths(array('40%', '30%', '30%'));

if (file_exists( "/proc/user_beancounters" )) {
        // OpenVZ Network Interfaces:
	$eth0_obj = $cce->getObject('Network', array('device' => 'venet0'));
	$eth1_obj = $cce->getObject('Network', array('device' => 'venet1'));
}
else {
	// Regular Network Interfaces:
	$eth0_obj = $cce->getObject('Network', array('device' => 'eth0'));
	$eth1_obj = $cce->getObject('Network', array('device' => 'eth1'));
}
					   
// Get eth0 info
if ($eth0_obj['enabled']) {
	if (file_exists( "/proc/user_beancounters" )) {
        	// OpenVZ Network Interfaces:
		$eth0 = `grep venet0 /proc/net/dev`;
	}
	else {
		// Regular Network Interfaces:
		$eth0 = `grep eth0 /proc/net/dev`;
	}
  $eth0 = chop(ltrim($eth0));
  $eth0 = preg_split("/[^[:alnum:]]+/", $eth0);
  $eth0['recv_bytes'] = $eth0[1];
  $eth0['recv_packets'] = $eth0[2];
  $eth0['sent_bytes'] = $eth0[9];
  $eth0['sent_packets'] = $eth0[10];
  $eth0['errors'] = $eth0[3] + $eth0[11];
  $eth0['collisions'] = $eth0[14];
} else {
  $eth0['recv_bytes'] = $eth0['recv_packets'] = $eth0['sent_bytes']  =
    $eth0['sent_packets'] = $eth0['errors'] = $eth0['collisions'] = $i18n->interpolate('stats_disabled');
}

// Get eth1 info
if ($eth1_obj['enabled']) {
  $eth1 = `grep eth1 /proc/net/dev`;
  $eth1 = chop(ltrim($eth1));
  $eth1 = preg_split("/[^[:alnum:]]+/", $eth1);
  $eth1['recv_bytes'] = $eth1[1];
  $eth1['recv_packets'] = $eth1[2];
  $eth1['sent_bytes'] = $eth1[9];
  $eth1['sent_packets'] = $eth1[10];
  $eth1['errors'] = $eth1[3] + $eth1[11];
  $eth1['collisions'] = $eth1[14];
} else {
  $eth1['recv_bytes'] = $eth1['recv_packets'] = $eth1['sent_bytes']  =
    $eth1['sent_packets'] = $eth1['errors'] = $eth1['collisions'] = $i18n->get('stats_disabled');
}  

$stylist = $serverScriptHelper->getStylist();
$style = $stylist->getStyle("PagedBlock");

$props = array('recv_bytes', 'recv_packets', 
	       'sent_bytes', 'sent_packets',
	       'errors', 'collisions');

// add statistics to scroll list
// need to set the style for each header so that
// it shows up in the "Label" color
foreach ($props as $prop) {
  $label = $factory->getLabel($prop);
  $label->setStyleTarget("labelLabel");
  $label->setStyle($style);
  $list->addEntry( array(
	 $label,
	 $factory->getTextField($prop, $eth0[$prop], "r"),   // eth0
	 $factory->getTextField($prop, $eth1[$prop], "r"))); // eth1
}

print '<BR>';
print ($list->toHtml());

am_back($factory);

print($page->toFooterHtml());

$serverScriptHelper->destructor();
/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>
