<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: userDiskUsage.php 201 2003-07-18 19:11:07Z will $

include("ServerScriptHelper.php");
include("uifc/PagedBlock.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-disk", "/base/disk/userDiskUsage.php");
$loginName = $serverScriptHelper->getLoginName();

// get i18n object
$i18n = $factory->getI18n();

// refresh disk information
$unique = time();
$cceClient->setObject("User", array("refresh" => $unique), "Disk", array("name" => $loginName));

// get objects
list($user_oid) = $cceClient->find('User', array('name' => $loginName));
$userDisk = $cceClient->get($user_oid, 'Disk');
$user = $cceClient->get($user_oid);

// get user disk information
$used = $userDisk["used"];
$available = $userDisk["quota"] * 1024;

$overquota = 0;
// fix to correspond to new quota scheme, negative number means no quota set
// 0 means 0, and any positive number is that number
if($available < 0)
{
    $home = $cceClient->getObject(
                'Disk', 
                array('mountPoint' => $user['volume'])
            );
    $available = $home['total'] - $home['used'];
    $free = $available;
    $percentage = 0;
} else {

  // calculate free space for user and if they are over quota
  if(($available - $used) >= 0)
    $free = $available-$used;
  else 
    {
      $overquota = 1;
      $free = 0;
    }
  
  // find out percentage used
  $percentage = round(100 * $used / $available);
  // don't show percentages greater than 100 because it 
  // could go way off the screen
  if ($percentage > 100)
    $percentage = 100;
}


// convert into MB
$used /= 1024;
$used = (round(100*$used)/100 == 0) ? 0 : sprintf("%.2f", round(100*$used)/100);
$free /= 1024;
$free = (round(100*$free)/100 == 0) ? 0 : sprintf("%.2f", round(100*$free)/100);

$page = $factory->getPage();

$block = new PagedBlock($page, "diskUsageFor", $factory->getLabel("diskUsageFor", false, array("userName" => $loginName)));

$block->addFormField(
  $factory->getNumber("userDiskUsed", $used, "", "", "r"),
  $factory->getLabel("userDiskUsed")
);

$block->addFormField(
/*  code to put unlimited when user does not have a quota
($available < 0 ?
	$factory->getTextField("userDiskFree", $i18n->interpolateHtml("[[base-disk.unlimited]]"), "r") :
*/
	$factory->getNumber("userDiskFree", $free, "", "", "r"), //),
  $factory->getLabel("userDiskFree")
);

$block->addFormField(
  $factory->getBar("userDiskPercentage", $percentage),
  $factory->getLabel("userDiskPercentage")
);

if($overquota) {
	$quotawarn = $factory->getTextBlock(
			"userOverQuota", 
			$i18n->interpolate("[[base-disk.overQuotaMsg]]"), 
			"r"
			);
	$quotawarn->setWrap(true);	

	$block->addFormField(
		$quotawarn,
		$factory->getLabel("userOverQuota")
	);
}

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>

<?php print($page->toFooterHtml()); 
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

