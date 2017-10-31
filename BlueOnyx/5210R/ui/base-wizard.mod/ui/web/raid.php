<?php
// Author: Patrick Bose
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: raid.php 1050 2008-01-23 11:45:43Z mstauber $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/base/wizard/raidHandler.php");
$i18n = $serverScriptHelper->getI18n("base-wizard");

// get settings
$raid = $cceClient->getObject("System", array(), "RAID");
$page = $factory->getPage();
$page->setOnLoad('top.code.flow_setPageLoaded(true);');
$form = $page->getForm();
$formId = $form->getId();

if ( $raid['level'] != '' && $raid['level'] >= 0 ) {
	print($page->toHeaderHtml());
	print($i18n->getHtml("raidAlreadyConfigured")); 
	print($page->toFooterHtml());
	$serverScriptHelper->destructor();
	return;
} 

$block = $factory->getPagedBlock("raidSettings");

$raidOptions = $factory->getMultiChoice("raidChoice");
$raidOptions->addOption($factory->getOption("raid0", false));
if ( $raid["disks"] == 2 )
	$raidOptions->addOption($factory->getOption("raid1", true));
if ( $raid["disks"] >= 3 )
	$raidOptions->addOption($factory->getOption("raid5", true));
$raidOptions->setFullSize(true);
$block->addFormField(
  $raidOptions,
  $factory->getLabel("raidOption")
);

$serverScriptHelper->destructor();
print($page->toHeaderHtml());
print($i18n->getHtml("raidMessage", "", array("numdisk" => $raid["disks"]))); ?>
<BR><BR>
<?php print($block->toHtml()); ?>
<INPUT TYPE="HIDDEN" NAME="confirmation" VALUE="">
<SCRIPT LANGUAGE="javascript">

function confirmRaidChoice(element) {
	if ( document.form.raidChoice[0].value == "raid0" && 
             document.form.raidChoice[0].checked == "1" &&
             confirm("<?php print($i18n->getJs("confirmRaid0", "base-raid")) ?>"))
		return true;
	if ( document.form.raidChoice[1].value == "raid1" && 
             document.form.raidChoice[1].checked == "1" &&
             confirm("<?php print($i18n->getJs("confirmRaid1", "base-raid")) ?>"))
		return true;
	if ( document.form.raidChoice[1].value == "raid5" && 
             document.form.raidChoice[1].checked == "1" &&
             confirm("<?php print($i18n->getJs("confirmRaid5", "base-raid")) ?>"))
		return true;
	return false;
}

var element = document.form.confirmation;
element.childFields = new Array();
element.submitHandler = confirmRaidChoice;
</SCRIPT>
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
