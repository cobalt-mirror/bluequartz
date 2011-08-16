<?php

// Author: Rickard Osser
// Copyright 2009, Bluapp AB.  All rights reserved.
// $Id: mx2_add.php,v 1.0 2009/03/17 <rickard.osser@bluapp.com>

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-email", "/base/email/blacklist_addHandler.php");
$i18n = $serverScriptHelper->getI18n("base-email");

$page = $factory->getPage();

if(isset($_TARGET)) {
  $oid = $cceClient->get($_TARGET);
  $blacklistHost = $oid["blacklistHost"];
  $deferTemporary = $oid["deferTemporary"];
  $active = $oid["active"];
 } else {
  $deferTemporary = 1;
 }


$block = $factory->getPagedBlock("secondarySettings");
$block->processErrors($serverScriptHelper->getErrors());

$block->addFormField(
		     $factory->getTextField("blacklistHostField", $blacklistHost),
		     $factory->getLabel("blacklistHostField"),
		     ""
		     );

$block->addFormField(
		     $factory->getBoolean("deferField", $deferTemporary),
		     $factory->getLabel("deferField"),
		     ""
		     );

$block->addFormField(
		     $factory->getBoolean("activeField", $active),
		     $factory->getLabel("activeField"),
		     ""
		     );

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton(
	$factory->getCancelButton('/base/email/email.php?view=blacklist'));

$serverScriptHelper->destructor();

print($page->toHeaderHtml());

print($block->toHtml());
if (isset($_TARGET))
{
	$target = $factory->getTextField('_TARGET', $_TARGET, '');
	print $target->toHtml();
}
print($page->toFooterHtml());

?>