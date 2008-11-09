<?php

include("ServerScriptHelper.php");
 
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-printserver");

$page = $factory->getPage();

$backButton = $factory->getBackButton("/base/printserver/printserver.php");

$scrollList = $factory->getScrollList("printerList", array("name", "status",
"listAction"), array(1));
$scrollList->setAlignments(array("left", "left", "center"));
$scrollList->setColumnWidths(array("", "30%", "1%"));
$scrollList->addButton(
        $factory->getAddButton(
                "javascript: location='/base/printserver/printerAdd.php';"
                . " top.code.flow_showNavigation(false)",
                "[[base-printserver.addPrinter_help]]"));

$printers = $cceClient->find("Printer");

for($i=0; $i<count($printers); $i++){
	$printer=$cceClient->get($printers[$i]);
	$name=$printer["name"];

	/* TODO: Should we even need to do a status if the print server has 
	 * been turned off ?? (it's slow!) */
	$txt = `lpc -P$name status`;
	$txt = explode("\n",$txt);
	$status="";

	$matches = array();
	preg_match("/^\s*\S+\s+(\S+)\s+(\S+)\s+(\S+)/",$txt[1], $matches);
	//printerName\s+printingEnabled\s+spoolingEnabled\s+jobs

	if($matches[1] == "enabled"){
		$status = $i18n->get("printing", "base-printserver") . ": "  . $i18n->get("enabled", "base-printserver");
	}else{
		$status = $i18n->get("printing", "base-printserver") . ": " . $i18n->get("disabled", "base-printserver");
	}

	$status .= "\n";

	if($matches[2] == "enabled"){
                $status .= $i18n->get("spooling", "base-printserver") . ": " . $i18n->get("enabled", "base-printserver");
        }else{
                $status .= $i18n->get("spooling", "base-printserver") . ": " . $i18n->get("disabled", "base-printserver");
        } 

	$status .= "\n";

	$jobs=0;
	if(is_numeric($matches[3])){
		$jobs=$matches[3];
	}

	$status .= $i18n->get("jobs", "base-printserver") . ": ". $jobs;

	//$matches = array();
	//preg_match("/\nStatus\s*:\s*(.+)/i",$txt, $matches);
	$scrollList->addEntry(array(
		$factory->getTextField("", $name, "r"),
		$factory->getTextBlock("", $status, "r"),
		$factory->getCompositeFormField(array(
			$factory->getModifyButton(
				"javascript: location='/base/printserver/printerMod.php?printerName=".$printer["name"]."'; top.code.flow_showNavigation(false)"
			),
			$factory->getDetailButton(
				"/base/printserver/jobList.php?printerName=".$printer["name"]
			),
			$factory->getRemoveButton(
				"javascript: confirmRemove('".$printer["name"]."', '".urlencode($printer["name"])."')"
			)
		))
	), "", false, $i);
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>
 
<SCRIPT LANGUAGE="javascript">
function confirmRemove(printerName, sendName) {
  var message = "<?php print($i18n->interpolate("[[base-printserver.removePrinterConfirm]]"))?>";
  message = top.code.string_substitute(message, "[[VAR.printerName]]", printerName);
  //message = printerName;
 
  if(confirm(message))
    location = "/base/printserver/printerDelete.php?printerName="+sendName;
}
</SCRIPT>
 
<?php print($scrollList->toHtml()); ?>
<BR>
<?php print($backButton->toHtml()); ?> 

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

