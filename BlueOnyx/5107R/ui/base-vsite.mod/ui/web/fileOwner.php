<?php

// Author: Michael Stauber <mstauber@blueonyx.it>
// Copyright 2006-2010, Team BlueOnyx. All rights reserved.

include_once('ServerScriptHelper.php');
include_once('AutoFeatures.php');
include_once('Capabilities.php');

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-vsite");
$helper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

// We must know which site we're on, or bust:
if (!$group) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/base/vsite/fileOwnerHandler.php?group=$group");
$transMethodOn="off";

// Find out which PHP version we use:
list($myplatform) = $cceClient->find('PHP');
$mysystem = $cceClient->get($myplatform);
$platform = $mysystem["PHP_version"];
if ($platform >= "5.3") {
    // We need to hide some legacy PHP settings that no longer work in PHP-5.3 or better:
    $pageID = "Hidden";
}
else {
    $pageID = "Default";
}

// Which site are we editing?
list($myvsite) = $cceClient->find('Vsite', array('name' => $group));
$vsite = $cceClient->get($myvsite);
$vsite_php = $cceClient->get($myvsite, "PHP");

$page = $factory->getPage();

$block = $factory->getPagedBlock("fileOwner_head", array("Default"));
$block->setLabel($factory->getLabel('fileOwner_head', false, array('vsite' => $vsite['fqdn'])));
$block->processErrors($serverScriptHelper->getErrors());

// Get list of all siteAdmin's for this site:
$my_siteAdmins_list = $cceClient->find('User', array('site' => $group, 'capLevels' => 'siteAdmin', 'enabled' => '1'));

// Build an array of siteAdmin names, but start sane:
if ($helper->getAllowed('adminUser')) {
    // admin users may chown to 'nobody' and 'apache':
    $my_siteAdmins = array("nobody", "apache");
}
else {
    // siteAdmin users are not allowed to chown to 'apache' for safety reasons:
    $my_siteAdmins = array("nobody");
}

// Fetch siteAdmin names and store them in array $my_siteAdmins:
foreach ($my_siteAdmins_list as $siteAdmin_Obj) {
    $user_siteAdmin = $cceClient->get($siteAdmin_Obj);
    array_push($my_siteAdmins, $user_siteAdmin{'name'});
}

// If no prefered_siteAdmin is set, set the default to 'nobody':
if ($vsite_php['prefered_siteAdmin'] == "") {
    $current_prefered_siteAdmin = "nobody";
}
else {
    $current_prefered_siteAdmin = $vsite_php['prefered_siteAdmin'];
}

// Build the MultiChoice selector:
$prefered_siteAdmin_select = $factory->getMultiChoice("prefered_siteAdmin",array_values($my_siteAdmins));
$prefered_siteAdmin_select->setSelected($current_prefered_siteAdmin, true);
$block->addFormField($prefered_siteAdmin_select,$factory->getLabel("prefered_siteAdmin"), "Default");

// Show "save" button
$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();

print($page->toHeaderHtml()); 
print($block->toHtml());
print($page->toFooterHtml());

?>

