<?php
// Copyright 2000-2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: groupDiskUsage.php 259 2004-01-03 06:28:40Z shibuya $

include_once('ServerScriptHelper.php');
include_once('uifc/PagedBlock.php');

$helper =& new ServerScriptHelper();
$cce =& $helper->getCceClient();
$factory =& $helper->getHtmlComponentFactory('base-disk', 
                "/base/disk/groupDiskUsage.php?group=$group");

// get i18n object
$i18n = $factory->getI18n();

// figure out if this is a Workgroup or Vsite
$cce->names('Workgroup');
if (count($cce->errors()) > 0)
{
    $type = 'Vsite';
}
else
{
    $type = 'Workgroup';
}

list($group_oid) = $cce->find($type, array('name' => $group));
// refresh only this group's quota info, not all users
$cce->set($group_oid, 'Disk', array( 'refresh' => time()));

// get objects
$group_disk = $cce->get($group_oid, 'Disk');
$group_info = $cce->get($group_oid);

$am_obj = $cce->getObject('ActiveMonitor', array(), 'Disk');

// get group disk information
$used = $group_disk['used'];
$available = $group_disk['quota'] * 1024;

// fix to correspond to new quota scheme, negative number means no quota set
// 0 means 0, and any positive number is that number
if($available < 0)
{
    $home = $cce->getObject(
                'Disk', 
                array('mountPoint' => $group_info['volume'])
            );
    $available = $home['total'] - $home['used'];
    $free = $available;
    $percentage = 0;
} else {
  
  // calculate free space for group and if they are over quota
  $overquota = 0;
  if (($available - $used) >= 0)
    $free = $available - $used;
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

$group_name = ($type == 'Vsite' ? $group_info['fqdn'] : $group_info['name']);

$block = new PagedBlock(
            $page, 'diskUsageFor', 
            $factory->getLabel('groupDiskUsageFor', false, 
                    array('groupName' => $group_name)));

$groupUsed = $factory->getNumber('groupDiskUsed', $used, '', '', 'r');
$groupUsed->setPreserveData(false);
$block->addFormField($groupUsed, $factory->getLabel('groupDiskUsed')
);

$groupFree = $factory->getNumber('groupDiskFree', $free, '', '', 'r');
$groupFree->setPreserveData(false);
$block->addFormField($groupFree, $factory->getLabel('groupDiskFree'));

$block->addFormField(
    $factory->getBar('groupDiskPercentage', $percentage),
    $factory->getLabel('groupDiskPercentage')
);






// Site system accounts: anonymous ftp usage & site-specific logs/stats
$sysusage =& $factory->getScrollList('sysQuota', 
                    array('', 'service', 'service_used', 'service_quota', 
			'serviceDiskPercentage'), 
                    array(1,2,3));

$sysusage->setSortEnabled(false);
$sysusage->setAlignments(array('center', 'left', 'right', 'right'));

// find site service objects
$service_sort_index = $sysusage->getSortedIndex();
$service_sort_prop = array(1 => 'label', 2 => 'used', 3 => 'quota');
$service_sort_type = array(1 => 'ascii', 2 => 'old_numeric', 3 => 'old_numeric');
$sysquotas = $cce->findx('ServiceQuota', array('site' => $group), array(),
				$service_sort_type[$service_sort_index],
				$service_sort_prop[$service_sort_index]
				);

if ($sysusage->getSortOrder() == 'descending')
	$sysquotas = array_reverse($sysquotas);

$start = 0;
for ($i = $start; ($i < count($sysquotas)); $i++)
{
	$service = $cce->get($sysquotas[$i]);
	
	$used = sprintf("%.2f", round(100 * ($service["used"] / 1024)) / 100);
	$percent = round(100 * $used / $service['quota']);

	if ($percent > 100)
		$percent = 100;

	if ($percent >= $am_obj['red_pcnt']) {
		$status =& $factory->getStatusSignal('severeProblem');
	} else if ($percent >= $am_obj['yellow_pcnt']) {
		$status =& $factory->getStatusSignal('problem');
	} else {
		$status =& $factory->getStatusSignal('normal');
	}

	$label = $i18n->get($service["label"]);
	$name = $factory->getTextField("sysname$i", $label, 'r');
	$name->setPreserveData(false);

	$used = $factory->getInteger("sysused$i", $used, '', '', 'r');
	$used->setPreserveData(false);

	$quota = $factory->getInteger("sysquota$i", $service['quota'], '', '', 'r');
	$quota->setPreserveData(false);
	$bar = $factory->getBar('userDiskPercentage', $percent);

	$sysusage->addEntry(
		array($status, $name, $used, $quota, $bar),
		'', false);
}



// add on user disk usage
$user_list =& $factory->getScrollList('userQuota', 
                    array('', 'user', 'used', 'quota', 'userDiskPercentage'), 
                    array(1,2,3));

$user_list->setSortEnabled(false);
$user_list->setAlignments(array('center', 'left', 'right', 'right'));
$page_length = 15;
$user_list->setLength($page_length);

$s_index = $user_list->getSortedIndex();

$sort_map = array( 1 => 'name', 2 => 'usage', 3 => 'quota');
$sorttype = $sort_map[$s_index];
$site = $group;
$order = ($user_list->getSortOrder() == 'descending') ? '--descending' : '--ascending';

$cmd = "/usr/sausalito/sbin/get_quotas.pl --sort=$sorttype --site=$group $order";

$handle = $helper->popen($cmd, "r", "root");

$users = array();
while (!feof($handle)) {
  $string = fgets($handle, 256);
  $string = chop($string);
  if (!$string) {
      // empty lines don't count
      continue;
  }
  $pieces = preg_split("/\s+/", $string);
  $users[] = $pieces;
}


$start = $user_list->getPageIndex() * $page_length;
$user_list->setEntryNum(count($users));

for ($i = $start; ($i < $start + $page_length) && ($i < count($users)); $i++)
{
    $user = $cce->get($users[$i]);
    $user_info = $users[$i];
    
    $name = $user_info[0];
    $used = $user_info[1];
    $quota = $user_info[2];

    $used = sprintf("%.2f", $used / 1024); // convert into megs
    $quota = sprintf("%.2f", $quota / 1024); // convert into megs

    // quota <= 0 means unlimited
    if ($quota  > 0) {
      $percent = round(100 * $used / $quota);
    } else {
      $percent = 0;
    }

    if ($percent > 100)
      $percent = 100;

    // quota <= 0 means unlimited
    if ($quota > 0) {
      $quota_field = $factory->getInteger("bar$i", $quota, '', '', 'r');
      $quota_field->setPreserveData(false);
    } else {
      $i18n = $factory->getI18n();
      $quota_field = $factory->getTextField("total$i", 
	       $i18n->interpolateHtml('[[base-disk.unlimited]]'),
	       'r');
      $quota_field->setPreserveData(false);
    }
      
    if ($percent >= $am_obj['red_pcnt']) {
      $status =& $factory->getStatusSignal('severeProblem');
    } else if ($percent >= $am_obj['yellow_pcnt']) {
      $status =& $factory->getStatusSignal('problem');
    } else {
      $status =& $factory->getStatusSignal('normal');
    }      
      
    $name_field = $factory->getTextField($i, $name, 'r');
    $name_field->setPreserveData(false);

    $used_field = $factory->getInteger("foo$i", $used, '', '', 'r');
    $used_field->setPreserveData(false);

    $bar = $factory->getBar('userDiskPercentage', $percent);

    $user_list->addEntry(
            array($status, $name_field, $used_field, $quota_field, $bar),
            '', false, $i);
}

$helper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>
<P>
<?php print($sysusage->toHtml()); ?>
<?
$builder = new FormFieldBuilder();
print $builder->makeHiddenField('group', $group);
?>
<p>
<?php print($user_list->toHtml()); ?>
<?php
// add a back button if viewed within active monitor or server disk usage
if (($serverDiskUsage == 1) || ($activeMonitor == 1)) {
	if ($activeMonitor == 1) {
		$getArgs = '?&activeMonitor=1&selectPath=groups';
	} else {
		$getArgs = '?selectPath=groups';
	}
	$backButton =& $factory->getBackButton('/base/disk/serverDiskUsage.php' .  $getArgs);
	print "<p>\n" . $backButton->toHtml() . "</p>\n";
}
?>
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
