<?php

include("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-ipsec");

$page = $factory->getPage();

$localInfo = $factory->getButton("/base/ipsec/localInfo.php", "localInfo");
$editProfile = $factory->getButton("/base/ipsec/profileList.php", "manageProfile");

$scrollList = $factory->getScrollList("tunnelList", array("tname", "status",
"listAction"), array(1));
$scrollList->setAlignments(array("left", "left", "center"));
$scrollList->setColumnWidths(array("30%", "", "1%"));
$scrollList->addButton(
        $factory->getAddButton(
                "javascript: location='/base/ipsec/tunnelAdd.php';"
                . " top.code.flow_showNavigation(false)",
                "[[base-ipsec.addTunnel_help]]"));

$tunnels = $cceClient->find("IpsecTunnel");

for($i=0; $i<count($tunnels); $i++){
	$tunnel=$cceClient->get($tunnels[$i]);

	$status="normal";
	if($tunnel["enabled"] == "ignore"){
		$status="disabled";
		$status_string = $i18n->get("[[base-ipsec.disabled]]");
	}else {
		$out="";
		$serverScriptHelper->shell(
			"/usr/sausalito/sbin/getTunnelStatus.pl ".$tunnel["name"]." ".$tunnel["enabled"]." ".$tunnel["keying"], 
			$out, 
			"root");
		$out_array = explode("\n",$out);
		$status_string="";
		$status = $out_array[0];
		$info = $out_array[1];
		if($status == "normal"){
			if($info != ""){
				$status_string=$i18n->get("[[base-ipsec.establishedTunnels]]")."\n";
				foreach(explode(" ",$info) as $e){
					$status_string = $status_string . $i18n->get("[[base-ipsec.$e]]") . "\n";
				}
			}else{
				$status_string = $i18n->get("[[base-ipsec.noConnections]]")."\n";
			}
		}else{
			if($info != ""){
				$status_string= $i18n->get("$info");
			}else{
				$status_string = $i18n->get("[[base-ipsec.tunnelProblem]]");
			}
		}
		
	}		
	

	$scrollList->addEntry(array(
		$factory->getTextField("", $tunnel["name"], "r"),
		$factory->getCompositeFormField(array(
			$factory->getStatusSignal($status),
			$factory->getTextBlock("", $status_string, "r")),
			"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"
		),
		$factory->getCompositeFormField(array(
			$factory->getModifyButton(
				"javascript: location='/base/ipsec/tunnelMod.php?tunnelName=".urlencode($tunnel["name"])."'; top.code.flow_showNavigation(false)"
			),
			$factory->getRemoveButton(
				"javascript: confirmRemove('".$tunnel["name"]."', '".urlencode($tunnel["name"])."')"
			)
		))
	), "", false, $i);
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
 
<SCRIPT LANGUAGE="javascript">
function confirmRemove(displayName, sendName) {
  var message = "<?php print($i18n->interpolate("[[base-ipsec.removeTunnelConfirm]]"))?>";
  message = top.code.string_substitute(message, "[[VAR.tunnelName]]", displayName);
 
  if(confirm(message))
    location = "/base/ipsec/tunnelDelete.php?tunnelName="+sendName;
}
</SCRIPT>

<TABLE CELLSPACING="3" CELLPADDING="3">
        <TR>
                <TD><?  print($localInfo->toHtml()); ?></TD>
                <TD><?  print($editProfile->toHtml());  ?></TD>
        </TR>
</TABLE>

<BR>
<?php print($scrollList->toHtml()); ?>

<?php print($page->toFooterHtml());                                        
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

