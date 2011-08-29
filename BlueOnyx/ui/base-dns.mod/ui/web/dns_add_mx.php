<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2008, Stauber Multimedia Design. All rights reserved.
// Copyright 2008-2011, Team BlueOnyx. All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-dns");

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

// ui for adding/modifying many DNS record types
$iam = '/base/dns/dns_add_mx.php';
$parent = '/base/dns/records.php';

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-dns", "/base/dns/dns_add.php?TYPE=MX");
$transMethodOn="off";

$page = $factory->getPage();

if (!$_TARGET) {
    // We're creating a new MX record:
    $block = $factory->getPagedBlock("create_dns_recMX");
}
else {
    // We are modifying an existing MX record:
    $block = $factory->getPagedBlock("modify_dns_recMX");
    
    // Get the MX records CODB-object:
    $systemObj = $cceClient->get($_TARGET);
    if ($systemObj['type'] != "MX") {
	// Queried object is not an MX record. Apparently someone was
	// trying to be real smart and used a modified browser URL string:
	header("location: /error/forbidden.html");
	return;
    }
}

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

if (intval($_TARGET) > "0") {
    $target_Field = $factory->getTextField("_TARGET", $_TARGET);
    $target_Field->setOptional ('silent');
    $block->addFormField(
	$target_Field,
	$factory->getLabel("_TARGET"),
	"hidden"
    );
}

// hostname:
$mx_host_name_Field = $factory->getTextField("mx_host_name", $systemObj['hostname']);
$mx_host_name_Field->setOptional ('loud');
$block->addFormField(
    $mx_host_name_Field,
    $factory->getLabel("mx_host_name"),
    $pageID
);

// domain name:
if (intval($_TARGET) > "0") {
    // We are modifying an existing MX record, so we use the stored domain name:
    $mx_domain_name_Field = $factory->getTextField("mx_domain_name", $systemObj['domainname']);
}
else {
    // If create a new record, we fill in the domain name from the parent record:
    $mx_domain_name_Field = $factory->getTextField("mx_domain_name", $domauth);
}
$block->addFormField(
    $mx_domain_name_Field,
    $factory->getLabel("mx_domain_name"),
    $pageID
);

// Mail Server Name:
$mx_target_server_Field = $factory->getTextField("mx_target_server", $systemObj['mail_server_name']);
$block->addFormField(
    $mx_target_server_Field,
    $factory->getLabel("mx_target_server"),
    $pageID
);

// MX Priority:
$mx_priority_select = $factory->getMultiChoice("mx_priority", array_values(array("very_high", "high", "low", "very_low")));
$mx_priority_select->setSelected($systemObj['mail_server_priority'], true);
$block->addFormField($mx_priority_select, $factory->getLabel("mx_priority"), $pageID);

// Show "save" button:
$block->addButton($factory->getSaveButton($page->getSubmitAction()));

// Show "cancel" button:
if (intval($_TARGET) > "0") {
    $block->addButton($factory->getCancelButton("$parent?domauth=$domauth"));
}
else {
    $block->addButton($factory->getCancelButton("$parent"));
}

$serverScriptHelper->destructor();

print($page->toHeaderHtml()); 
print($block->toHtml());
print($page->toFooterHtml());

function br2nl($str) {
   $str = preg_replace("/(\r\n|\n|\r)/", "", $str);
   return preg_replace("=<br */?>=i", "\n", $str);
}

?>

