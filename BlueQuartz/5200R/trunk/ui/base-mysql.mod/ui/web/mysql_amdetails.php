<?
// Author: Brian N. Smith
// Copyright 2006, NuOnce Networks, Inc.  All rights reserved.

include_once("ServerScriptHelper.php");
include_once("base/am/am_detail.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
$page = $factory->getPage();

print($page->toHeaderHtml());

am_detail_block($factory, $cce, "mysql", "[[base-mysql.amDetailsTitle]]");
am_back($factory);

print($page->toFooterHtml());

$serverScriptHelper->destructor();
?>
