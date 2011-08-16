<?php
// Authors: Brian N. Smith & Michael Stauber
// Copyright 2007-2008, Solarspeed Ltd. and NuOnce Networks, Inc.  All rights reserved.
// $Id: tomcat-manager.php,v 2.0 Wed Nov 26 17:02:08 2008 mstauber Exp $

include_once("ServerScriptHelper.php");
include_once("Product.php");
include_once("ArrayPacker.php");
include_once("uifc/ImageButton.php");
include_once("System.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-java");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-java",
                                '/base/java/tomcat-managerHandler.php');

if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$page = $factory->getPage();
$errors = array();

// Set trigger in CCE to update CODB with status info about Tomcat:
$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$sysOID = $cceClient->find("System");
$java_status = array(
    'TomcatUITrigger' => time()
);
$cceClient->set($sysOID[0], "JavaStatus", $java_status);
$errors = array_merge($errors, $cceClient->errors());

$sysConfig = $cceClient->getObject("System", array());

$page = $factory->getPage();

if(empty($title)) {
    $title = "[[base-java.amJavaNameTag]]";
}

$scrollList = $factory->getScrollList($title, array(" ", " "));

$scrollList->setAlignments(array("left", "center"));

$scrollList->setSortEnabled(false);

// Get Status of Tomcat out of CCE:
$javaStatus = $cceClient->getObject("System", array(), "JavaStatus");

$adminURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/admin";
$managerURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/manager/html";
$hostManagerURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/host-manager/html";
$managerStatusURL = "http://" . $sysConfig["hostname"] . "." . $sysConfig["domainname"] . ":8080/manager/status";

// Admin URL:
$linkButton = $factory->getDetailButton($adminURL);
if ($javaStatus["TomcatStatus"] == "0") {
    $linkButton->setDisabled(true);
}
$namefield = $i18n->interpolate("[[base-java.TomcatAdminInterface]]");
$desc_html = $factory->getTextField("", $namefield, "r");
$scrollList->addEntry( array($desc_html, $linkButton), "", false );

// Manager URL:
$linkButton = $factory->getDetailButton($managerURL);
if ($javaStatus["TomcatStatus"] == "0") {
    $linkButton->setDisabled(true);
}
$namefield = $i18n->interpolate("[[base-java.TomcatManagerInterface]]");
$desc_html = $factory->getTextField("", $namefield, "r");
$scrollList->addEntry( array($desc_html, $linkButton), "", false );

// Tomcat Host Manager Interface:
$linkButton = $factory->getDetailButton($hostManagerURL);
if ($javaStatus["TomcatStatus"] == "0") {
    $linkButton->setDisabled(true);
}
$namefield = $i18n->interpolate("[[base-java.TomcatHostManagerInterface]]");
$desc_html = $factory->getTextField("", $namefield, "r");
$scrollList->addEntry( array($desc_html, $linkButton), "", false );

// Tomcat Manager Status:
$linkButton = $factory->getDetailButton($managerStatusURL);
if ($javaStatus["TomcatStatus"] == "0") {
    $linkButton->setDisabled(true);
}
$namefield = $i18n->interpolate("[[base-java.TomcatManagerStatus]]");
$desc_html = $factory->getTextField("", $namefield, "r");
$scrollList->addEntry( array($desc_html, $linkButton), "", false );

// Admin Password Settings:
$admin_settings =& $factory->getPagedBlock('TomcatAdminPassHeader');
$admin_settings->processErrors($errors);

$admin_settings->addDivider($factory->getLabel('AdminPassInformation', false));

$pass_field = $factory->getPassword('password');
$pass_field->setPreserveData(false);

$admin_settings->addFormField(
    $pass_field,
    $factory->getLabel('TomcatAdminPassField')
    );

// Info about Tomcat-Status:
$tomcat_statusbox = $factory->getPagedBlock("TomcatStausBox_header", array("Default"));
$tomcat_statusbox->processErrors($serverScriptHelper->getErrors());

$warning = $i18n->get("TomCatStatusBox_info");
$tomcat_statusbox->addFormField(
    $factory->getTextList("_", $warning, 'r'),
    $factory->getLabel(" "),
    "Default"
    );


$page =& $factory->getPage();
$form =& $page->getForm();

$admin_settings->addButton($factory->getSaveButton($form->getSubmitAction()));
$admin_settings->addButton($factory->getCancelButton('/base/java/tomcat-manager.php'));

print($page->toHeaderHtml());

print($admin_settings->toHtml());

if ($javaStatus["TomcatStatus"] == "0") {
    print($tomcat_statusbox->toHtml());
}

print($scrollList->toHtml());

?>


