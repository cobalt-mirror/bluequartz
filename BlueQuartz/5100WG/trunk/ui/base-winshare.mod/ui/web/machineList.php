<?

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-winshare");
$cce = $serverScriptHelper->getCceClient();

$factory = $serverScriptHelper->getHtmlComponentFactory("base-winshare");
$page = $factory->getPage();

$scrollList = $factory->getScrollList("windowsMachinesList",array("wm_name", "wm_remarks", "wm_actions"));
$scrollList->setAlignments(array("left", "left", "center"));
$scrollList->setColumnWidths(array("","", "1%"));

$oids = $cce->find("WindowsMachine");
foreach ($oids as $oid) {
	$obj = $cce->get($oid);
	$scrollList->addEntry(array(
		$factory->getTextField("", $obj[name], "r"),
		$factory->getTextField("", $obj[description], "r"),
		$factory->getCompositeFormField(array(
			$factory->getModifyButton("modMachine.php?oid=$oid"),
			$factory->getRemoveButton("javascript: confirmRemove('$obj[name]', '".rawurlencode($obj["name"])."')")
		))
	));
}

$scrollList->addButton(
	$factory->getAddButton("modMachine.php", "[[base-winshare.wm_addMachine_help]]")
);


print $page->toHeaderHtml();
?>
<script language="javascript">
function confirmRemove(machineName, sendName) {
  var message = "<?php print($i18n->get("removeMachineConfirm"))?>";
  message = top.code.string_substitute(message, "[[VAR.machineName]]", machineName);

while(sendName.indexOf("&") != -1){
  sendName=top.code.string_substitute(sendName, "&", "%26");
}

while(sendName.indexOf("+") != -1){
  sendName=top.code.string_substitute(sendName, "+", "%2B");
}

  if(confirm(message))
    location = "machineRemove.php?name="+sendName;
}
</SCRIPT>
<?

$backButton = $factory->getBackButton("/base/winshare/winshare.php");
print $scrollList->toHtml();
print "<BR>" . $backButton->toHtml();
print $page->toFooterHtml();
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

