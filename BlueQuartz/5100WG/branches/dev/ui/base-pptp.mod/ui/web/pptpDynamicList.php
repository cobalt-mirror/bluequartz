<?

/* This page lists the dynamic IP ranges associated with PPTP */

include_once("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-pptp");
$cce = $serverScriptHelper->getCceClient();
$i18n = $serverScriptHelper->getI18n("base-pptp");


$page = $factory->getPage();
$scrollList = $factory->getScrollList("dynamic_ranges", array(
	"list_ipaddr_range_from",
	"list_ipaddr_range_to",
	"list_action"), array(0,1));
$scrollList->setAlignments(array("left", "left", "center"));
$scrollList->setColumnWidths(array("", "", "10%"));


// get the list of ranges and print them out..
$oids = $cce->find("PptpDynamic");

foreach ($oids as $oid) {
  $obj = $cce->get($oid);
  $ipaddrlo = $obj["ipaddrlo"];
  $ipaddrhi = $obj["ipaddrhi"];
  $scrollList->addEntry(array(
  	$factory->getIpAddress("from_$oid", $ipaddrlo, "r"),
	$factory->getIpAddress("to_$oid", $ipaddrhi, "r"),
	$factory->getCompositeFormField(array(
		$factory->getModifyButton("/base/pptp/pptpDynamicMod.php?oid=$oid"),
		$factory->getRemoveButton("javascript: confirmDynamicRemove('$ipaddrlo', '$ipaddrhi', $oid)")
	))
  ));
}

$scrollList->addButton($factory->getAddButton("/base/pptp/pptpDynamicMod.php"));
$backButton = $factory->getBackButton("/base/pptp/pptp.php");

print $page->toHeaderHtml();
?>
<SCRIPT language="javascript">
<!--
function confirmDynamicRemove(ipaddrlo, ipaddrhi, oid) {
  var message = "<? print($i18n->get("removeDynamicConfirm")) ?>";
  message = top.code.string_substitute(message, "[[VAR.ipaddrlo]]", ipaddrlo, "[[VAR.ipaddrhi]]", ipaddrhi);

  if (confirm(message))
    location = "/base/pptp/pptpDynamicRemove.php?oid=" + oid;
}
// -->
</SCRIPT>
<?
print $scrollList->toHtml();
print "<BR>";
print $backButton->toHtml();
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

