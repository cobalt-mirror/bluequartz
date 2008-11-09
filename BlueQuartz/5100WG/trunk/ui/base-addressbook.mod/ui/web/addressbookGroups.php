<?
include ("ServerScriptHelper.php");
include ("ArrayPacker.php");
include ("uifc/ImageButton.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-addressbook");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-addressbook");
$page = $factory->getPage();
$scrollList = $factory->getScrollList("addressbookgroupsScrollList", array("groupname", "groupmembers", "groupdesc", "listAction"), array(0));
$scrollList->setEntryCountTags("[[base-addressbook.groupCountSingular]]", "[[base-addressbook.groupCountPlural]]");

$cce = $serverScriptHelper->getCceClient();

// get hostname
$system = $cce->getObject("System");
$hostname = $system["hostname"] . "." . $system["domainname"];

// add speed ups, copied from Kevin's changes in workgroupList.php
// disable sorting
$scrollList->setSortEnabled(false);

// find page length
$pageLength = $scrollList->getLength();

// find start point
$start = $scrollList->getPageIndex()*$pageLength;

$oids = $cce->findSorted("Workgroup", "name");

// sort in the right direction
if($scrollList->getSortOrder() == "descending")
  $oids = array_reverse($oids);

// number of groups
$scrollList->setEntryNum(count($oids));

for($i = $start; $i < count($oids) && $i < $start+$pageLength;$i++) {
	$oid = $oids[$i];
	$group = $cce->get($oid);
	$actions = $factory->getCompositeFormField();
	$actions->addFormField(new ImageButton($page, "/base/webmail/compose.php?toAddy=".rawurlencode($i18n->interpolate("[[base-addressbook.groupEmailFormat]]", array("name" => $group["name"], "email" => $group["name"] . "@" . $hostname))), "/libImage/composeEmail.gif", "groupMail", "groupMail_help"));
	$actions->addFormField(new ImageButton($page, "javascript: window.open('http://$hostname/~" . $group["name"] . "/'); void(0)", "/libImage/visitWebsite.gif", "groupWeb", "groupWeb_help"));

	// this sequence needs to get pushed to a common place
	$desc = $factory->getTextField("", $i18n->interpolate($group["description"]), "r");
	$desc->setMaxLength(1000);
	$scrollList->addEntry( array(
		$factory->getTextField("name".$oid, $group["name"], "r"),
		$factory->getTextField("members".$oid, implode(", ", stringToArray($group["members"])), "r"),
		$desc,
		$actions
	), "", false, $i);
}

print $page->toHeaderHtml();
print $scrollList->toHtml();
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

