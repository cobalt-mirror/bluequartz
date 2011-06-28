<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2011, Stauber Multimedia Design. All rights reserved.
// Copyright Team BlueOnyx 2009-2011. All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-console");

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$cceClient = $serverScriptHelper->getCceClient();

// Update CODB with the latest info gathered from our config file:
$helper = new ServerScriptHelper($sessionId);
$cceHelper =& $helper->getCceClient();
$ourOID = $cceHelper->find("pam_abl_settings");
$cceHelper->set($ourOID[0], "", array('reload_config' => time()));
$errors = $cceHelper->errors();

// get settings
$systemObj = $cceClient->getObject("pam_abl_settings");

$factory = $serverScriptHelper->getHtmlComponentFactory("base-console", "/base/console/pam_ablHandler.php");
$transMethodOn="off";
$page = $factory->getPage();

$block = $factory->getPagedBlock("pam_abl_head", array("pam_abl_config_location"));
$block->processErrors($serverScriptHelper->getErrors());

// Force Update of CODB:
mt_srand((double)microtime() * 1000000);
$zufall = mt_rand();
$force_update_Field = $factory->getTextField("force_update", $zufall);
$force_update_Field->setOptional ('silent');
$block->addFormField(
    $force_update_Field,
    $factory->getLabel("force_update"),
    "hidden"
);

// pam_abl.conf location:
$pam_abl_location_Field = $factory->getTextField("pam_abl_location", "/etc/security/pam_abl.conf", "r");
$pam_abl_location_Field->setOptional ('silent');
$block->addFormField(
    $pam_abl_location_Field,
    $factory->getLabel("pam_abl_location"),
    "pam_abl_config_location"
);

// host_purge:
$host_purge_choices=array(
    "1h" => "1h", 
    "2h" => "2h", 
    "3h" => "3h", 
    "4h" => "4h", 
    "6h" => "6h", 
    "8h" => "8h", 
    "10h" => "10h", 
    "12h" => "12h", 
    "18h" => "18h", 
    "1d" => "1d", 
    "2d" => "2d", 
    "3d" => "3d", 
    "4d" => "4d", 
    "8d" => "8d" 
    );

// user_purge:
$user_purge_choices=array(
    "1h" => "1h", 
    "2h" => "2h", 
    "3h" => "3h", 
    "4h" => "4h", 
    "6h" => "6h", 
    "8h" => "8h", 
    "10h" => "10h", 
    "12h" => "12h", 
    "18h" => "18h", 
    "1d" => "1d", 
    "2d" => "2d", 
    "3d" => "3d", 
    "4d" => "4d", 
    "8d" => "8d" 
    );

$host_purge = $systemObj['host_purge'];
$user_purge = $systemObj['user_purge'];

// user_purge Input:
$user_purge_select = $factory->getMultiChoice("user_purge",array_values($user_purge_choices));
$user_purge_select->setSelected($user_purge_choices[$user_purge], true);
$block->addFormField($user_purge_select,$factory->getLabel("user_purge"), "pam_abl_config_location");

// user_purge Input:
$host_purge_select = $factory->getMultiChoice("host_purge",array_values($host_purge_choices));
$host_purge_select->setSelected($host_purge_choices[$host_purge], true);
$block->addFormField($host_purge_select,$factory->getLabel("host_purge"), "pam_abl_config_location");

// user_rule:
$user_rule_raw = $systemObj['user_rule'];
if (preg_match('/!admin\/cced/', $user_rule_raw)) {
    $ur_diss = explode(',', $user_rule_raw);
    $user_rule = $ur_diss[1];
}
else {
    // assume default because someone manually messed with the rules and removed the safeguard for admin access to the GUI:
    $user_rule = "30/1h";
}

// build array:
$user_rule_choices=array(
    "1/1h" => "1/1h", 
    "3/1h" => "3/1h", 
    "5/1h" => "5/1h", 
    "10/1h" => "10/1h", 
    "20/1h" => "20/1h", 
    "30/1h" => "30/1h", 
    "40/1h" => "40/1h", 
    "50/1h" => "50/1h", 
    "60/1h" => "60/1h", 
    "100/1h" => "100/1h",
    "50000/1m" => "disabled"
    );

// user_rule Input:
$user_rule_select = $factory->getMultiChoice("user_rule",array_values($user_rule_choices));
$user_rule_select->setSelected($user_rule_choices[$user_rule], true);
$block->addFormField($user_rule_select,$factory->getLabel("user_rule"), "pam_abl_config_location");


// host_rule:
$host_rule_raw = $systemObj['host_rule'];
$hr_diss = explode('=', $host_rule_raw);
$host_rule = $hr_diss[1];

// build array:
$host_rule_choices=array(
    "1/1h" => "1/1h", 
    "3/1h" => "3/1h", 
    "5/1h" => "5/1h", 
    "10/1h" => "10/1h", 
    "20/1h" => "20/1h", 
    "30/1h" => "30/1h", 
    "40/1h" => "40/1h", 
    "50/1h" => "50/1h", 
    "60/1h" => "60/1h", 
    "100/1h" => "100/1h",
    "50000/1m"=> "disabled"
    );

// Check if our returned result is one of the available choices:
if (!in_array($host_rule, $host_rule_choices)) {
    // It is not, so assume a safe default:
    $host_rule = "30/1h";
}

// host_rule Input:
$host_rule_select = $factory->getMultiChoice("host_rule",array_values($host_rule_choices));
$host_rule_select->setSelected($host_rule_choices[$host_rule], true);
$block->addFormField($host_rule_select,$factory->getLabel("host_rule"), "pam_abl_config_location");

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();

print($page->toHeaderHtml()); 
print($block->toHtml());
print($page->toFooterHtml());

?>

