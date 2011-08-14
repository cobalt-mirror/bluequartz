<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// Copyright 2008-2010 Project BlueOnyx.  All rights reserved. 
// $Id: serverDiskUsage.php Mon 11 Jan 2010 09:27:02 AM CET mstauber $

include_once('ServerScriptHelper.php');
include_once('ArrayPacker.php');

$helper = new ServerScriptHelper();

// Only adminUser should be here
if (!$helper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$factory =& $helper->getHtmlComponentFactory('base-disk', $PHP_SELF);
$cce =& $helper->getCceClient();
$page = $factory->getPage();

// by default show summary and users
$disk_usage =& $factory->getPagedBlock('serverDiskUsage', 
                    array('summary'));

$cce->names('Workgroup');

if (count($cce->errors()) == 0) {
	$hasWorkgroups = 1;
}

if ($hasWorkgroups) {
	$group_class = 'Workgroup';
	$group_name = 'name'; //internal CCE name of Workgroup
	$name_sort = 'name';
	$groupcolumn_name = 'groupName';
	$group_used = 'groupUsed';
	$group_quota = 'groupQuota';
	$disk_usage->addPage('summaryWorkgroup',
			     $factory->getLabel('summaryWorkgroup'));
	if ($selectPath == 'groups') {
		$disk_usage->setSelectedId('summaryWorkgroup');
	}
} else {
	$group_class = 'Vsite';
	$name_sort = 'fqdn';
	$group_name = 'name';  //internal CCE name of Vsite
	$groupcolumn_name = 'vsiteName';
	$group_used = 'vsiteUsed';
	$group_quota = 'vsiteQuota';
	$disk_usage->addPage('summaryVsite',
			     $factory->getLabel('summaryVsite'));
	if ($selectPath == 'groups') {
		$disk_usage->setSelectedId('summaryVsite');
	}
}

$disk_usage->addPage('users', $factory->getLabel('users'));
$disk_usage->addPage('settings', $factory->getLabel('settings'));

// make things faster by only bothering to add a scrollist if that page
// will be displayed
if ($disk_usage->getSelectedId() == 'summary' 
        || $disk_usage->getSelectedId() == '')
{
    // setup the scroll list
    $usage_list =& $factory->getScrollList(' ', 
                    array(' ', 'partition', 'diskUsed', 'total', 'percentage'),
                    array(1, 2, 3));
    
    $usage_list->setSortEnabled(false);
    $usage_list->setAlignments(array('center', 'left', 'right', 'right', 'left'));
    $page_length = 15;  // FIXME configurable?
    $usage_list->setLength($page_length);

    // if we're coming from a different tab, reset the page index
    if ($last_page != $disk_usage->getSelectedId()) {
	$usage_list->setPageIndex(0);
    } 
    
    // only display partitions that are mounted
    $sort_index = $usage_list->getSortedIndex();
    if ($sort_index == 1)
    {
        $partitions = $cce->findSorted('Disk', 'mountPoint', 
                            array('mounted' => true));
    }
    else
    {
        $sort_map = array(2 => 'used', 3 => 'total');
        $partitions = $cce->findNSorted('Disk', $sort_map[$sort_index],
                            array('mounted' => true));
    }
        
    if ($usage_list->getSortOrder() == 'descending')
        $partitions = array_reverse($partitions);

    $start = $usage_list->getPageIndex() * $page_length;

    // On a VPS we only have one partition, although for sake of compatability we 
    // also have "/home" as Object "Disk". But this is not a real partition. Hence
    // We want to hide it here:
    if ((is_file("/proc/user_beancounters")) || (is_file("/etc/is_aws"))) {
	$partitionsvps = "1";
        $usage_list->setEntryNum($partitionsvps);
    }
    else {
	$usage_list->setEntryNum(count($partitions));
    }

    // get AM object for quota percents and stuff
    $am_obj = $cce->getObject('ActiveMonitor', array(), 'Disk');

    for ($i = $start; ($i < $start + $page_length) && ($i < count($partitions));
            $i++)
    {
      //refresh partition info first
	$cce->set($partitions[$i], '', array('refresh' => time()));
        $disk = $cce->get($partitions[$i]);
        $used = sprintf("%.2f", round(100 * ($disk['used'] / 1024)) / 100);
        $total = sprintf("%.2f", round(100 * ($disk['total'] / 1024)) / 100);
        $percent = round(100 * ($disk['used'] / $disk['total']));

        $label =& $factory->getTextField($i, 
                    ($disk['label'] ? $disk['label'] : $disk['mountPoint']),
                    'r');
        $label->setPreserveData(false);
        $used_field =& $factory->getInteger("used$i", $used, '', '', 'r');
        $used_field->setPreserveData(false);
        $total_field =& $factory->getInteger("total$i", $total, '', '', 'r');
        $total_field->setPreserveData(false);
        
	if ($percent > $am_obj['red_pcnt']) {
	  $status =& $factory->getStatusSignal('severeProblem');
	} else if ($percent > $am_obj['yellow_pcnt']) {
	  $status =& $factory->getStatusSignal('problem');
	} else {
	  $status =& $factory->getStatusSignal('normal');
	} 

	// On a VPS we only have one partition, although for sake of compatability we 
	// also have "/home" as Object "Disk". But this is not a real partition. Hence
	// We want to hide it here:
	if ((is_file("/proc/user_beancounters")) && ($disk['mountPoint'] == '/home')) {
	    next;
	}
	elseif ((is_file("/etc/is_aws")) && ($disk['mountPoint'] == '/home')) {
	    next;
	}
	else {
    	    $usage_list->addEntry(
                array(
  		    $status,
                    $label,
                    $used_field,
                    $total_field,
                    $factory->getBar("bar$i", $percent)
                ),
                '', false, $i);
        }
    }
}
else if ($disk_usage->getSelectedId() == 'users') 
{

// Over Quota Section:

    // setup the scroll list
    $usage_list_oq =& $factory->getScrollList('OverQuota_Users', 
                        array(' ', 'user', 'vsiteName', 'used', 'quota', 'percentage'),
                        array(1,2,3,4));
    
    $usage_list_oq->setSortEnabled(false);
    $usage_list_oq->setAlignments(array('center', 'left', 'left', 'right', 'right', 'left'));
    $page_length = 15;  // FIXME configurable?
    $usage_list_oq->setLength($page_length);

    // if we're coming from a different tab, reset the page index
    if ($last_page != $disk_usage->getSelectedId()) {
	$usage_list_oq->setPageIndex(0);
    } 

    $sort_index = $usage_list_oq->getSortedIndex();

    // don't specify a site, b/c we want all server users
    $sort_map = array( 1 => 'name', 2 => 'usage', 3 => 'quota');
    $sorttype = $sort_map[$sort_index];
    $order = ($usage_list_oq->getSortOrder() == 'descending') ? '--descending' : '--ascending';
    
    $cmd = "/usr/sausalito/sbin/get_quotas.pl --sort=$sorttype $order | /bin/awk '{if ($3 != 0 && ($2 > $3 || $2 * 1.11 > $3)) print $1,$2,$3;}'";
    
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
    

    $start = $usage_list_oq->getPageIndex() * $page_length;
    $usage_list_oq->setEntryNum(count($users));

    $am_obj = $cce->getObject('ActiveMonitor', array(), 'Disk');

    for ($i = $start; ($i < $start + $page_length) && ($i < count($users));
            $i++)
    {
        
        $user_info = $users[$i];
	$name = $user_info[0];
	$used = $user_info[1];
	$quota = $user_info[2];

	// Get the Vsite of this user:
        list($users_vsite) = $cce->find("User", array("name" => $name));
        $user_siteObj = $cce->get($users_vsite);
        $users_site = $user_siteObj["site"];
        if ($users_site) {
            list($the_vsite) = $cce->find("Vsite", array("name" => $user_siteObj["site"]));
	    $the_siteObj = $cce->get($the_vsite);
	    $fqdn = $the_siteObj["fqdn"];
	    $url = "/base/disk/groupDiskUsage.php?group=" . urlencode($users_site) . ($activeMonitor == 1 ? '&activeMonitor=1' : '&serverDiskUsage=1');
    	    $site =& $factory->getUrl($i, $url, $fqdn, '', 'r');
    	}
    	else {
    	    $site =& $factory->getTextField($i, "-- Server --", 'r');
    	    $site->setPreserveData(false);
    	}
	
        $used = sprintf("%.2f", $used / 1024); // convert into megs
        $quota = sprintf("%.2f", $quota / 1024); // convert into megs

        $total = $quota;
        if ($quota > 0)
            $percent = round(100 * ($used / $quota));
        else
            $percent = 0;

	if ($percent > 100) {
	  $percent = 100;
	}

        $label =& $factory->getTextField($i, $name, 'r');
        $label->setPreserveData(false);
        $used_field =& $factory->getInteger("used$i", $used, '', '', 'r');
        $used_field->setPreserveData(false);

        if ($total > 0)
        {
            $total_field =& $factory->getInteger("total$i", $total, 
                                    '', '', 'r');
	    if ($percent >= $am_obj['red_pcnt']) {
	      $status =& $factory->getStatusSignal('severeProblem');
	    } else if ($percent >= $am_obj['yellow_pcnt']) {
	      $status =& $factory->getStatusSignal('problem');
	    } else {
	      $status =& $factory->getStatusSignal('normal');
	    } 
        }
        else
        {
            $i18n =& $factory->getI18n();
            $total_field =& $factory->getTextField("total$i", 
                            $i18n->interpolateHtml('[[base-disk.unlimited]]'),
                            'r');
	    $status =& $factory->getStatusSignal('normal');
        }
        
        $total_field->setPreserveData(false);

        $usage_list_oq->addEntry(
                array(
            	    $status,
		    $label,
		    $site,
                    $used_field,
                    $total_field,
                    $factory->getBar("bar$i", $percent)
                ),
                '', false, $i);
    }


// Full Quota section:

    // setup the scroll list
    $usage_list =& $factory->getScrollList('All_Users', 
                        array(' ', 'user', 'vsiteName', 'used', 'quota', 'percentage'),
                        array(1,2,3,4));
    
    $usage_list->setSortEnabled(false);
    $usage_list->setAlignments(array('center', 'left', 'left', 'right', 'right', 'left'));
    $page_length = 15;  // FIXME configurable?
    $usage_list->setLength($page_length);

    // if we're coming from a different tab, reset the page index
    if ($last_page != $disk_usage->getSelectedId()) {
	$usage_list->setPageIndex(0);
    } 

    $sort_index = $usage_list->getSortedIndex();

    // don't specify a site, b/c we want all server users
    $sort_map = array( 1 => 'name', 2 => 'usage', 3 => 'quota');
    $sorttype = $sort_map[$sort_index];
    $order = ($usage_list->getSortOrder() == 'descending') ? '--descending' : '--ascending';
    
    $cmd = "/usr/sausalito/sbin/get_quotas.pl --sort=$sorttype $order";
    
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
    

    $start = $usage_list->getPageIndex() * $page_length;
    $usage_list->setEntryNum(count($users));

    $am_obj = $cce->getObject('ActiveMonitor', array(), 'Disk');

    for ($i = $start; ($i < $start + $page_length) && ($i < count($users));
            $i++)
    {
        
        $user_info = $users[$i];
	$name = $user_info[0];
	$used = $user_info[1];
	$quota = $user_info[2];

	// Get the Vsite of this user:
        list($users_vsite) = $cce->find("User", array("name" => $name));
        $user_siteObj = $cce->get($users_vsite);
        $users_site = $user_siteObj["site"];
        if ($users_site) {
            list($the_vsite) = $cce->find("Vsite", array("name" => $user_siteObj["site"]));
	    $the_siteObj = $cce->get($the_vsite);
	    $fqdn = $the_siteObj["fqdn"];
	    $url = "/base/disk/groupDiskUsage.php?group=" . urlencode($users_site) . ($activeMonitor == 1 ? '&activeMonitor=1' : '&serverDiskUsage=1');
    	    $site =& $factory->getUrl($i, $url, $fqdn, '', 'r');
    	}
    	else {
    	    $site =& $factory->getTextField($i, "-- Server --", 'r');
    	    $site->setPreserveData(false);
    	}

        $used = sprintf("%.2f", $used / 1024); // convert into megs
        $quota = sprintf("%.2f", $quota / 1024); // convert into megs

        $total = $quota;
        if ($quota > 0)
            $percent = round(100 * ($used / $quota));
        else
            $percent = 0;

	if ($percent > 100) {
	  $percent = 100;
	}

        $label =& $factory->getTextField($i, $name, 'r');
        $label->setPreserveData(false);
        $used_field =& $factory->getInteger("used$i", $used, '', '', 'r');
        $used_field->setPreserveData(false);

        if ($total > 0)
        {
            $total_field =& $factory->getInteger("total$i", $total, 
                                    '', '', 'r');
	    if ($percent >= $am_obj['red_pcnt']) {
	      $status =& $factory->getStatusSignal('severeProblem');
	    } else if ($percent >= $am_obj['yellow_pcnt']) {
	      $status =& $factory->getStatusSignal('problem');
	    } else {
	      $status =& $factory->getStatusSignal('normal');
	    } 
        }
        else
        {
            $i18n =& $factory->getI18n();
            $total_field =& $factory->getTextField("total$i", 
                            $i18n->interpolateHtml('[[base-disk.unlimited]]'),
                            'r');
	    $status =& $factory->getStatusSignal('normal');
        }
        
        $total_field->setPreserveData(false);

        $usage_list->addEntry(
                array(
            	    $status,
		    $label,
		    $site,
                    $used_field,
                    $total_field,
                    $factory->getBar("bar$i", $percent)
                ),
                '', false, $i);
    }
}
else if ($disk_usage->getSelectedId() == 'settings') {
  //setup the settings page
  $errors = array();
  if (isset($user_exceeds)) {
    //one of them is set, so we know we should handle a post
    $mail_admin_on_user = in_array('mail_admin_on_user', stringToArray($user_exceeds)) ? 1 : 0;
    $mail_user = in_array('mail_user', stringToArray($user_exceeds))  ? 1 : 0;
    $mail_admin_on_vsite = in_array('mail_admin_on_vsite', stringToArray($site_exceeds))  ? 1 : 0;

    list($oid) = $cce->findx('ActiveMonitor');
    $ok = $cce->set($oid, 'Disk', array( 'mail_admin_on_user' => $mail_admin_on_user, 'mail_admin_on_vsite' => $mail_admin_on_vsite, 'mail_user' => $mail_user));
    if (!$ok) {
      array_push($errors, new Error('[[base-disk.errSettingEmails]]'));
    }
    $errors = array_merge($errors, $cce->errors());
  } else {
    $am_obj = $cce->getObject('ActiveMonitor', array(), 'Disk');
  }

  $user_exceeds_field = $factory->getMultiChoice("user_exceeds");
  $user_exceeds_field->setMultiple(true);

  $admin_for_user_field = $factory->getOption('mail_admin_on_user', $am_obj['mail_admin_on_user']);
  $user_exceeds_field->addOption($admin_for_user_field);
  $user_field = $factory->getOption('mail_user', $am_obj['mail_user']);
  $user_exceeds_field->addOption($user_field);

  $disk_usage->addFormField($user_exceeds_field, $factory->getLabel('user_exceeds'), 'settings');

  $site_exceeds_field = $factory->getMultiChoice("site_exceeds");
  $site_exceeds_field->setMultiple(true);

  $admin_for_vsite_field = $factory->getOption('mail_admin_on_vsite', $am_obj['mail_admin_on_vsite']);
  $site_exceeds_field->addOption($admin_for_vsite_field);

  $disk_usage->addFormField($site_exceeds_field, $factory->getLabel('site_exceeds'), 'settings');

  // PHP5 related fix:
  $disk_usage->addFormField(
      $factory->getTextField("debug_1", "", 'r'),
      $factory->getLabel("debug_1"),
      Hidden
  );

  $disk_usage->addButton($factory->getSaveButton($page->getSubmitAction())); 
  $disk_usage->processErrors($errors);

}
else // handle groups
{
    $i18n =& $factory->getI18n();

// Over-Quota List:

    // setup the scroll list
    $usage_list_oq =& $factory->getScrollList('OverQuota_Sites', 
			array( ' ',
                                $groupcolumn_name, $group_used, 
                                $group_quota, 'percentage'
                        ),
                        array(1,2,3));
    
    $usage_list_oq->setSortEnabled(false);
    $usage_list_oq->setAlignments(array('center', 'left', 'right', 'right', 'left'));
    $page_length = 15;  // FIXME configurable?
    $usage_list_oq->setLength($page_length);

    // if we're coming from a different tab, reset the page index
    if ($last_page != $disk_usage->getSelectedId()) {
	$usage_list_oq->setPageIndex(0);
    } 

    // this won't work on workgroups
    // I don't have time to make it generic
    $sort_index = $usage_list_oq->getSortedIndex();
    $sort_map = array( 1 => 'name', 2 => 'usage', 3 => 'quota');
    $sorttype = $sort_map[$sort_index];
    $allsites = '--sites';
    $order = ($usage_list_oq->getSortOrder() == 'descending') ? '--descending' : '--ascending';
    
    $cmd = "/usr/sausalito/sbin/get_quotas.pl $allsites --sort=$sorttype $order | /bin/awk '{if ($3 != 0 && ($2 > $3 || $2 * 1.11 > $3)) print $1,$2,$3;}'";

    $handle = $helper->popen($cmd, "r", "root");
    $sites = array();
    $numsites = 0;
    while (!feof($handle)) {
      $string = fgets($handle, 256);
      $string = chop($string);
      if (!$string) {
          // empty lines don't count
	  continue;
      }
      $pieces = preg_split("/\s+/", $string);
      // put into hash by name...
      $sites[$pieces[0]] = $pieces;
      // and by number
      $sites[$numsites] = $pieces;

      $numsites++;
    }

    // this is used only when sites are sorted by name
    $cce_vsites = $cce->findx('Vsite', array(), array(), 'hostname', 'fqdn');

    // reverse the oid list based on sort order
    if ($usage_list_oq->getSortOrder() == 'descending') {
	$cce_vsites = array_reverse($cce_vsites);
    }

    $start_oq = $usage_list_oq->getPageIndex() * $page_length;
    $usage_list_oq->setEntryNum($numsites);

    $am_obj = $cce->getObject('ActiveMonitor', array(), 'Disk');
    
    for ($i = $start_oq; ($i < $start_oq + $page_length) && ($i < $numsites);
	 $i++)
    {
	    // repquota results are sorted correctly
	    // so we take the sitename from there...
	    $site_info = $sites[$i];
	    $name = $site_info[0];
	    // lookup the CCE object corresponding to that sitename...
	    list($oid) = $cce->find('Vsite', array('name' => $name));
	    if (!$oid) {
		error_log("couldn't find CCE object for site name $name");
		continue;
	    }
	    $site_obj = $cce->get($oid);
	    // so we can get the fqdn...
	    $fqdn = $site_obj['fqdn'];
	    
	    $site_obj = $cce->get($oid, 'Disk');
	    // and their over_quota status
	    $user_over_quota = $site_obj['user_over_quota'];
	
	    // then use the repquota results for usage and quota
	    $used = $site_info[1];
	    $quota = $site_info[2];

	$used = sprintf("%.2f", $used / 1024); // convert into megs
	$quota = sprintf("%.2f", $quota / 1024); // convert into megs

	$total = $quota;
        if ($quota > 0)
            $percent = round(100 * ($used / $quota));
        else
            $percent = 0;

	$url = "/base/disk/groupDiskUsage.php?group=" . urlencode($name) . ($activeMonitor == 1 ? '&activeMonitor=1' : '&serverDiskUsage=1');
        $label =& $factory->getUrl($i, $url, $fqdn, '', 'r');
        $label->setPreserveData(false);
        $used_field =& $factory->getInteger("used$i", $used, '', '', 'r');
        $used_field->setPreserveData(false);

        if ($total > 0)
        {
            $total_field =& $factory->getInteger("total$i", $total, 
                                    '', '', 'r');
        }
        else
        {
            $total_field =& $factory->getTextField("total$i", 
                            $i18n->interpolateHtml('[[base-disk.unlimited]]'),
                            'r');
        }
        
        $total_field->setPreserveData(false);

	if ($percent > $am_obj['red_pcnt']) {
	  $status =& $factory->getStatusSignal('severeProblem');
	} else if ($user_over_quota || 
		   ($percent > $am_obj['yellow_pcnt'])) {
	  $status =& $factory->getStatusSignal('problem');
	} else {
	  $status =& $factory->getStatusSignal('normal');
	}

        $usage_list_oq->addEntry(
                array(
		    $status,
                    $label,
                    $used_field,
                    $total_field,
                    $factory->getBar("bar$i", $percent)
                ),
                '', false, $i);
    }

    if ($hasWorkgroups) {
      $choose_group_message =& $factory->getSimpleText($i18n->get('choose_workgroup'));
    } else {
      $choose_group_message =& $factory->getSimpleText($i18n->get('choose_site'));
    }


// Full Quota List:

    // setup the scroll list
    $usage_list =& $factory->getScrollList('All_Sites', 
			array( ' ',
                                $groupcolumn_name, $group_used, 
                                $group_quota, 'percentage'
                        ),
                        array(1,2,3));
    
    $usage_list->setSortEnabled(false);
    $usage_list->setAlignments(array('center', 'left', 'right', 'right', 'left'));
    $page_length = 15;  // FIXME configurable?
    $usage_list->setLength($page_length);

    // if we're coming from a different tab, reset the page index
    if ($last_page != $disk_usage->getSelectedId()) {
	$usage_list->setPageIndex(0);
    } 

    // this won't work on workgroups
    // I don't have time to make it generic
    $sort_index = $usage_list->getSortedIndex();
    $sort_map = array( 1 => 'name', 2 => 'usage', 3 => 'quota');
    $sorttype = $sort_map[$sort_index];
    $allsites = '--sites';
    $order = ($usage_list->getSortOrder() == 'descending') ? '--descending' : '--ascending';
    
    $cmd = "/usr/sausalito/sbin/get_quotas.pl $allsites --sort=$sorttype $order";

    $handle = $helper->popen($cmd, "r", "root");
    $sites = array();
    $numsites = 0;
    while (!feof($handle)) {
      $string = fgets($handle, 256);
      $string = chop($string);
      if (!$string) {
          // empty lines don't count
	  continue;
      }
      $pieces = preg_split("/\s+/", $string);
      // put into hash by name...
      $sites[$pieces[0]] = $pieces;
      // and by number
      $sites[$numsites] = $pieces;
      $numsites++;
    }

    // this is used only when sites are sorted by name
    $cce_vsites = $cce->findx('Vsite', array(), array(), 'hostname', 'fqdn');

    // reverse the oid list based on sort order
    if ($usage_list->getSortOrder() == 'descending') {
	$cce_vsites = array_reverse($cce_vsites);
    }

    $start = $usage_list->getPageIndex() * $page_length;
    $usage_list->setEntryNum($numsites);

    $am_obj = $cce->getObject('ActiveMonitor', array(), 'Disk');
    
    for ($i = $start; ($i < $start + $page_length) && ($i < $numsites);
	 $i++)
    {
	
	// we need to either get the info from CCE or from the repquota results,
	if ($sorttype == 'usage' || $sorttype == 'quota') {
	    // repquota results are sorted correctly
	    // so we take the sitename from there...
	    $site_info = $sites[$i];
	    $name = $site_info[0];
	    // lookup the CCE object corresponding to that sitename...
	    list($oid) = $cce->find('Vsite', array('name' => $name));
	    if (!$oid) {
		error_log("couldn't find CCE object for site name $name");
		continue;
	    }
	    $site_obj = $cce->get($oid);
	    // so we can get the fqdn...
	    $fqdn = $site_obj['fqdn'];

	    $site_obj = $cce->get($oid, 'Disk');
	    // and their over_quota status
	    $user_over_quota = $site_obj['user_over_quota'];
	
	    // then use the repquota results for usage and quota
	    $used = $site_info[1];
	    $quota = $site_info[2];
	} else {
	    // CCE find results are sorted correctly
	    // so we get the CCE object from there...
	    $oid = $cce_vsites[$i];
	    $site_obj = $cce->get($oid);
	    // to find the fqdn...
	    $fqdn = $site_obj['fqdn'];
	    // and the sitename...
	    $name = $site_obj['name'];

	    // and the over_quota status...
	    $site_obj = $cce->get($oid, 'Disk');
	    $user_over_quota = $site_obj['user_over_quota'];

	    // we then use the sitename to figure out which
	    // entry from the repquota results we need...
	    $site_info = $sites[$name];
	    // and lookup the usage info
	    $used = $site_info[1];
	    $quota = $site_info[2];
	}

	$used = sprintf("%.2f", $used / 1024); // convert into megs
	$quota = sprintf("%.2f", $quota / 1024); // convert into megs

	$total = $quota;
        if ($quota > 0)
            $percent = round(100 * ($used / $quota));
        else
            $percent = 0;

	$url = "/base/disk/groupDiskUsage.php?group=" . urlencode($name) . ($activeMonitor == 1 ? '&activeMonitor=1' : '&serverDiskUsage=1');
        $label =& $factory->getUrl($i, $url, $fqdn, '', 'r');
        $label->setPreserveData(false);
        $used_field =& $factory->getInteger("used$i", $used, '', '', 'r');
        $used_field->setPreserveData(false);

        if ($total > 0)
        {
            $total_field =& $factory->getInteger("total$i", $total, 
                                    '', '', 'r');
        }
        else
        {
            $total_field =& $factory->getTextField("total$i", 
                            $i18n->interpolateHtml('[[base-disk.unlimited]]'),
                            'r');
        }
        
        $total_field->setPreserveData(false);

	if ($percent > $am_obj['red_pcnt']) {
	  $status =& $factory->getStatusSignal('severeProblem');
	} else if ($user_over_quota || 
		   ($percent > $am_obj['yellow_pcnt'])) {
	  $status =& $factory->getStatusSignal('problem');
	} else {
	  $status =& $factory->getStatusSignal('normal');
	}

        $usage_list->addEntry(
                array(
		    $status,
                    $label,
                    $used_field,
                    $total_field,
                    $factory->getBar("bar$i", $percent)
                ),
                '', false, $i);
    }

    if ($hasWorkgroups) {
      $choose_group_message =& $factory->getSimpleText($i18n->get('choose_workgroup'));
    } else {
      $choose_group_message =& $factory->getSimpleText($i18n->get('choose_site'));
    }
}


print $page->toHeaderHtml();
print $disk_usage->toHtml();
if ($usage_list) {
    if ($usage_list_oq) {
	print $usage_list_oq->toHtml();
    }
    print $usage_list->toHtml();
    $builder = new FormFieldBuilder();
    print $builder->makeHiddenField("last_page", $disk_usage->getSelectedId());
}
if ($choose_group_message) {
  print '<BR>';
  print $choose_group_message->toHtml();
}
// add a back button if we are in active monitor
if ($activeMonitor == 1) {
	// remember we are in active monitor, if they click a tab
	$amRemember =& $factory->getTextField('activeMonitor', 1, '');
	$backButton =& $factory->getBackButton('/base/am/amStatus.php');

	print $amRemember->toHtml();
	print "<p>\n" . $backButton->toHtml() . "</p>\n";
}


print $page->toFooterHtml();

$helper->destructor();
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
