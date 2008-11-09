<?php

// $id:$

include("ServerScriptHelper.php");

// my back button url
$pf_BackURL = "/base/network/ethernet.php";

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-portforward");
$i18n = $serverScriptHelper->getI18n("base-portforward");

$page = $factory->getPage();
$scrollList = $factory->getScrollList("portforward_list",
		array("sourcePort", "sourceIP", "protocol",
			"targetPort", "targetIP", "action"),
		array(1,4)
);
$scrollList->setSortEnabled(true);

// Get hash of devicename => ipaddr.  (i.e. eth0 => 10.9.25.1)
$netDevice = array();
$networkOids = $cce->find("Network");
foreach ($networkOids as $networkOid) {
	$device = $cce->get($networkOid);
	$netDevice[$device["device"]] = $device["ipaddr"];
}


$oids = $cce->find("PortForward");

for ($i=0; $i<count($oids);$i++) {
	$oid = $oids[$i];
	$obj = $cce->get($oid);
	$scrollList->addEntry( array(
		$factory->getTextField("sp".$oid, $obj["sourcePort"], "r"),
		$factory->getTextField("si".$oid,
#				$obj["sourceIP"],
				$i18n->interpolate(
					"[[base-portforward.iface]]",
					array(
					"ifacename" => $obj['sourceIP'],
					"ipaddr" => $netDevice[$obj['sourceIP']]
					)
				),
				"r"
		),
		$factory->getTextField("pr".$oid, $obj["protocol"], "r"),
		$factory->getTextField("tp".$oid, $obj["targetPort"], "r"),
		$factory->getTextField("ti".$oid, $obj["targetIP"], "r"),
		$factory->getCompositeFormField( array(
			$factory->getModifyButton("detail.php?OID=$oid"),
			$factory->getRemoveButton("javascript: checkRemove($oid)")
		))
	));
}

// This message is used in Client side javascript to corfirm the
//   deletion of a port forward rule.
$confirmMessage = $i18n->getJS("confirmRemoveMessage");

// Add the 'create' button:
$scrollList->addButton($factory->getAddButton("/base/portforward/detail.php"));
print $page->toHeaderHtml();

// Create the 'back' button:
$backButton = $factory->getBackButton($pf_BackURL);

?>
<SCRIPT language="javascript">
<!--
function checkRemove(oid) {
	if (confirm("<?php print $confirmMessage ?>")) {
		document.form.action = "/base/portforward/detail.php?OID=" + oid + "&todo=remove";
		if(document.form.onsubmit()) document.form.submit();
	}
}
// -->
</SCRIPT>
<?php

print $scrollList->toHtml();
print	'<br><table border=0 cellspacing=2 cellpadding=2><tr><td nowrap>' .
	$backButton->toHtml() . '</td></tr></table>';
print $page->toFooterHtml();;
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

