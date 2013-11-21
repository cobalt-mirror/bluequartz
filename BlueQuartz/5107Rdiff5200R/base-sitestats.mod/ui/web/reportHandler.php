<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: reportHandler.php,v 1.3 2001/12/07 02:51:33 pbaltz Exp $

include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("Product.php");
include_once('Error.php');

$serverScriptHelper = new ServerScriptHelper();

// Only menuServerServerStats and siteAdmin should be here
if (!$serverScriptHelper->getAllowed('menuServerServerStats') &&
    !$helper->getAllowed('manageSite') &&
    !($serverScriptHelper->getAllowed('siteAdmin') &&
      $group == $serverScriptHelper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$product = new Product($cceClient);

// check if cce is suspended, so reports don't get generated.  
// there is most likely a backup going on
if ($cceClient->suspended() !== false)
{
	$msg = $cceClient->suspended() ? $cceClient->suspended() : '[[base-cce.suspended]]';
	print $serverScriptHelper->toHandlerHtml(
			"/base/sitestats/reportForm.php?group=$group&type=$type&noback=$noback", 
			array(new Error($msg)));
	$serverScriptHelper->destructor();
	exit;
}

$oids = $cceClient->find("System");

// Cheesily strip leading zeroes.  
$_endDate_month  = ereg_replace('^0', '', $_endDate_month);
$_startDate_month  = ereg_replace('^0', '', $_startDate_month);
$_endDate_day  = ereg_replace('^0', '', $_endDate_day);
$_startDate_day  = ereg_replace('^0', '', $_startDate_day);

$config = array(
	"startDay" => $_startDate_day,
	"startMonth" => $_startDate_month,
	"startYear" => $_startDate_year,
	"endDay" => $_endDate_day,
	"endMonth" => $_endDate_month,
	"endYear" => $_endDate_year,
	"report" => $type,
	"update" => time(),
	"site" => $group,
);

$cceClient->set($oids[0], "Sitestats", $config);
$errors = array_merge($errors, $cceClient->errors());

print($serverScriptHelper->toHandlerHtml("/base/sitestats/summary.php?type=$type&group=$group&nodump=1", $errors));

$serverScriptHelper->destructor();
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
