<?php
# Author: Brian N. Smith
# Copyright 2007, NuOnce Networks, Inc.  All rights reserved.
# $Id: convert2passwd.php,v 1.0 2007/12/14 09:48:00 Exp $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$factory = $serverScriptHelper->getHtmlComponentFactory("base-convert2passwd", "");
$convertConfig = $cceClient->getObject("System", array(), "convert2passwd");

$page = $factory->getPage();
$block = $factory->getPagedBlock("header", array("Default"));

$errors = $serverScriptHelper->getErrors();
$block->processErrors($errors);

$i18n = $factory->getI18n();

if ( $convertConfig["convert"] == 1 ) {
    $msg = $i18n->get("finished");
    $box = $factory->getTextBlock("warning_box", $msg);
    $box->setHeight("20");
    $box->setWidth("80");

    $block->addFormField(
	$box,
	$factory->getLabel(""),
	"Default"
	);


} else {
    $msg = $i18n->get("warning");
    $box = $factory->getTextBlock("warning_box", $msg);
    $box->setHeight("20");
    $box->setWidth("80");

    $block->addFormField(
	$box,
	$factory->getLabel(""),
	"Default"
	);

    $block->addButton($factory->getButton("/base/user/convert2passwdHandler.php", "convert_button"));
}

// Don't ask why, but somehow with PHP5 we need to add a blank FormField or nothing shows on this page:
$hidden_block = $factory->getTextBlock("Nothing", "");
$hidden_block->setOptional(true);
$block->addFormField(
    $hidden_block,
    $factory->getLabel("Nothing"),
    "Hidden"
    );

$serverScriptHelper->destructor();

echo $page->toHeaderHtml();
echo $block->toHtml();
echo $page->toFooterHtml();

?>
