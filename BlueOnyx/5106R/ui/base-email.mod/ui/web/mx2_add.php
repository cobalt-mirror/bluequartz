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
$factory = $serverScriptHelper->getHtmlComponentFactory("base-email", "/base/email/mx2_addHandler.php");
$i18n = $serverScriptHelper->getI18n("base-email");

$page = $factory->getPage();

if(isset($_TARGET)) {
  $oid = $cceClient->get($_TARGET);
  $domain = $oid["domain"];
  $mapto = $oid["mapto"];
 }


$block = $factory->getPagedBlock("secondarySettings");
$block->processErrors($serverScriptHelper->getErrors());

$block->addFormField(
		     $factory->getDomainName("domainField", $domain),
		     $factory->getLabel("domainField"),
		     ""
		     );

$mapto_field = $factory->getTextField("maptoField", $mapto);
$mapto_field->setOptional('silent');
$block->addFormField(
		     $mapto_field,
		     $factory->getLabel("maptoField"),
		     ""
		     );

$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton(
	$factory->getCancelButton('/base/email/email.php?view=mx'));

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