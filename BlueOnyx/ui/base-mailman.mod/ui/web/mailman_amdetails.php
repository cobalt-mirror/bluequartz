<?
// $Id: mailmanList.php, v 1.0.0-1 Tue 26 Apr 2011 05:46:40 AM CEST
// Copyright 2011 Team BlueOnyx. All rights reserved.

include_once("ServerScriptHelper.php");
include_once("base/am/am_detail.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
$page = $factory->getPage();

print($page->toHeaderHtml());

am_detail_block($factory, $cce, "MAILMAN", "[[base-mailman.amDetailsTitle]]");
am_back($factory);

print($page->toFooterHtml());

$serverScriptHelper->destructor();

?>
