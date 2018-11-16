<?php
// Auther: Hisao SHIBUYA
// Copyright 2010, Project BlueQuartz. All rights reserved.
// $Id: $

include_once("ArrayPacker.php");
include_once("Product.php");
include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only serveriStat should be here
if (!$serverScriptHelper->getAllowed('serveriStat')) {
  header("location: /error/forbidden.html");
  return;
}


$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);
$factory = $serverScriptHelper->getHtmlComponentFactory("base-istat", "/base/istat/istatHandler.php");

$istat = $cceClient->getObject("System", array(), "iStat");

$page = $factory->getPage();

$block = $factory->getPagedBlock("iStatSettings");

$block->processErrors($serverScriptHelper->getErrors());

$block->addFormField(
  $factory->getBoolean("enableiStatField", $istat["enabled"]),
  $factory->getLabel("enableiStatField")
);

$code = $factory->getInteger("serverCodeField", $istat["serverCode"], 1);
$code->setWidth(5);

$block->addFormField(
  $code,
  $factory->getLabel("serverCodeField")
);

$port = $factory->getInteger("networkPortField", $istat["networkPort"], 1);
$port->setWidth(5);

$block->addFormField(
  $port,
  $factory->getLabel("networkPortField")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();

print($page->toHeaderHtml());
print($block->toHtml());
print($page->toFooterHtml());
?>
