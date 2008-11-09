<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userDefaults.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");
include("Product.php");
include("AutoFeatures.php");

$serverScriptHelper = new ServerScriptHelper();
$autoFeatures = new AutoFeatures( $serverScriptHelper );
$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "/base/user/userDefaultsHandler.php");
$i18n = $factory->getI18n();

// get defaults
if (isset($group) && $group != "") 
  $defaults = $cceClient->getObject("Vsite", array("name" => $group), "UserDefaults");
else  
  $defaults = $cceClient->getObject("System", array(), "UserDefaults");

$page = $factory->getPage();

$basic_tab = 'basicDefaults';
$service_tab = 'serviceDefaults';

$block = $factory->getPagedBlock("userDefaults", 
                array($basic_tab, $service_tab));
$block->setHideEmptyPages(array($service_tab));
$block->processErrors($serverScriptHelper->getErrors());

list($site_oid) = $cceClient->find('Vsite', array('name' => $group)); 
$vsite = $cceClient->get($site_oid);
$block->setLabel($factory->getLabel('userDefaultsTitle', false, array('fqdn' => $vsite['fqdn'])));

$userNameGenMode = $factory->getMultiChoice("userNameGenMode", array("firstInitLast", "firstLastInit", "first", "last"));
if($defaults["userNameGenMode"] == "firstInitLast")
  $userNameGenMode->setSelected(0, true);
if($defaults["userNameGenMode"] == "firstLastInit")
  $userNameGenMode->setSelected(1, true);
else if($defaults["userNameGenMode"] == "first")
  $userNameGenMode->setSelected(2, true);
else if($defaults["userNameGenMode"] == "last")
  $userNameGenMode->setSelected(3, true);

if($i18n->getProperty("genUsername") == "yes"){
	$block->addFormField(
        $userNameGenMode, 
        $factory->getLabel("userNameGenField"),
        $basic_tab
        );
}

// Load site quota
if ($group)
{
    list($vsite_oid) = $cceClient->find('Vsite', array("name" => $group));
    $disk = $cceClient->get($vsite_oid, 'Disk');
    $max_quota = $disk['quota'];
}

$site_quota = ($max_quota == -1 ? 499999999 : $max_quota);

$quota = $factory->getInteger(
			"maxDiskSpaceField", 
			($defaults["quota"] != -1 ? $defaults["quota"] : ""), 
			1, $site_quota);
$quota->setOptional('silent');

if ($max_quota && $max_quota != -1)
    $quota->showBounds(1);

$block->addFormField(
  $quota,
  $factory->getLabel("maxDiskSpaceFieldDefault"),
  $basic_tab
);


if (isset($group) && $group != "") {

  list($userServices) = $cceClient->find("UserServices", array("site" => $group));
  list($vsite) = $cceClient->find("Vsite", array("name" => $group));
  if(!$autoFeatures->display($block, "defaults.User", 
        array(
            "CCE_SERVICES_OID" => $userServices, 
            "VSITE_OID" => $vsite,
            'PAGED_BLOCK_DEFAULT_PAGE' => $basic_tab
            )))
  {
        error_log(__FILE__ . '.' . __LINE__ . ": autoFeatures->display failed");
  }
 
} else {
  list($userServices) = $cceClient->find("UserServices");
  $autoFeatures->display($block, "defaults.User", 
        array(
            "CCE_SERVICES_OID" => $userServices,
            'PAGED_BLOCK_DEFAULT_PAGE' => $basic_tab
            ));
}


$block->addButton($factory->getSaveButton($page->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/user/userList.php?group=$group"));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php 
print($block->toHtml()); 
if ($group) 
{
	$vsite = $factory->getTextField("group", $group, "");
	print($vsite->toHtml());
}
print($page->toFooterHtml()); 
/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>

