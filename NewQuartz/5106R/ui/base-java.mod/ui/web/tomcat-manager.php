<?php
// Author: Brian N. Smith
// Copyright 2007-2008, Solarspeed Ltd. and NuOnce Networks, Inc.  All rights reserved.
// $Id: tomcat-manager.php,v 2.0 Wed Nov 26 17:02:08 2008 mstauber Exp $

include_once("ServerScriptHelper.php");
include_once("Product.php");
include_once("ArrayPacker.php");
include_once("uifc/ImageButton.php");
include_once("System.php");


$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-java");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-java");

if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$page = $factory->getPage();

$cceClient = $serverScriptHelper->getCceClient();
$sysConfig = $cceClient->getObject("System", array());

$page = $factory->getPage();

if(empty($title)) {
    $title = "[[base-java.amJavaNameTag]]";
}

$scrollList = $factory->getScrollList($title, array(" ", " "));

$scrollList->setAlignments(array("left", "center"));

$scrollList->setSortEnabled(false);

$adminURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/admin";
$managerURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/manager/html";
$hostManagerURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/host-manager/html";
$managerStatusURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/manager/status";

// Admin URL:
$linkButton = $factory->getDetailButton($adminURL);
$namefield = $i18n->interpolate("[[base-java.TomcatAdminInterface]]");
$desc_html = $factory->getTextField("", $namefield, "r");
$scrollList->addEntry( array($desc_html, $linkButton), "", false );

// Manager URL:
$linkButton = $factory->getDetailButton($managerURL);
$namefield = $i18n->interpolate("[[base-java.TomcatManagerInterface]]");
$desc_html = $factory->getTextField("", $namefield, "r");
$scrollList->addEntry( array($desc_html, $linkButton), "", false );

// Tomcat Host Manager Interface:
$linkButton = $factory->getDetailButton($hostManagerURL);
$namefield = $i18n->interpolate("[[base-java.TomcatHostManagerInterface]]");
$desc_html = $factory->getTextField("", $namefield, "r");
$scrollList->addEntry( array($desc_html, $linkButton), "", false );

// Tomcat Manager Status:
$linkButton = $factory->getDetailButton($managerStatusURL);
$namefield = $i18n->interpolate("[[base-java.TomcatManagerStatus]]");
$desc_html = $factory->getTextField("", $namefield, "r");
$scrollList->addEntry( array($desc_html, $linkButton), "", false );

print($page->toHeaderHtml());

print($scrollList->toHtml());

?>


