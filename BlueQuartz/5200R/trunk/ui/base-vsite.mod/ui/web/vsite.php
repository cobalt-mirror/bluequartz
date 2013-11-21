<?php
// Author: Hiaso SHIBUYA
// Copyright 2009, Project BlueQuartz.  All rights reserved.
// $Id: $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only serverVsite should be here
if (!$serverScriptHelper->getAllowed('serverVsite')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/base/vsite/vsiteHandler.php");

// get Vsite information
$vsite = $cceClient->getObject("System", array(), "Vsite");

$page = $factory->getPage();

$block = $factory->getPagedBlock("vsiteSettings");
$block->processErrors($serverScriptHelper->getErrors());

$max_vsite = $factory->getInteger("maxVsiteField", $vsite["maxVsite"], 1, $vsite["maxVsiteUpper"]);
$max_vsite->setWidth(5);
$max_vsite->showBounds(1);

$block->addFormField(
  $max_vsite,
  $factory->getLabel("maxVsiteField")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
print ($page->toHeaderHtml());
print ($block->toHtml());
print ($page->toFooterHtml());
?>
