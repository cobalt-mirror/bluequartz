<?php
// $Id: storageList.php,v 1.12 2001/11/05 20:08:14 pbaltz Exp $
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// storageList.php
// list available storage devices with links to modify/remove
// the storage devices.  Ignores internal hard drives.

include_once('ServerScriptHelper.php');

$helper =& new ServerScriptHelper();

// Only adminUser should be here
if (!$helper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$factory =& $helper->getHtmlComponentFactory('base-storage');
$cce =& $helper->getCceClient();

// get a scrollist and set it up
$disk_list =& $factory->getScrollList('diskList', 
                                array('deviceName', 'size', 'mounted', 'actions'),
                                array(0, 1, 2));

// FIXME:  This shouldn't be hardcoded.  User's should be able to customize
//	   somehow.
$disks_per_page = 15;

$sort_map = array(0 => 'label', 1 => 'total', 2 => 'mounted');
$sort_index = 0;
$sort_index = $disk_list->getSortedIndex();

$disks = $cce->findx('Disk', array('internal' => 0, 'new' => 0), array(),
                    ($sort_index == 1 ? 'old_numeric' : 'ascii'),
                    $sort_map[$sort_index]);

// do the stuff necessary for cce sorting
$disk_list->setSortEnabled(false);
if ($disk_list->getSortOrder() == "descending")
{
    $disks = array_reverse($disks);
}

$disk_list->setAlignments(array('left', 'right', 'center', 'center'));
$disk_list->setColumnWidths(array("30%", "30%", "30%", "10%"));
$disk_list->setEntryNum(count($disks));
$disk_list->setLength($disks_per_page);

$start_index = $disks_per_page * $disk_list->getPageIndex();

// add the disks to the page
for ($i = $start_index; 
        ($i < count($disks)) && ($i < $start_index + $disks_per_page);
        $i++)
{
    $current =& $cce->get($disks[$i]);

    // the modify icon
    $modify = "/base/storage/storageModify.php?_oid=" . $disks[$i];

    $actions =& $factory->getCompositeFormField();
    $actions->addFormField($factory->getModifyButton($modify));

    $actions->addFormField(
                $factory->getRemoveButton(
                    "/base/storage/storageRemove.php?_oid=" . $disks[$i]));
    $device =& $factory->getTextField($i, $current['label'], 'r');

    $i18n =& $factory->getI18n();
    $yes_no = ($current['mounted'] ? 'yes' : 'no');
    $mounted =& $factory->getTextField("m$i", 
                    $i18n->interpolateHtml("[[base-storage.$yes_no]]"), 
                    'r');

    $size =& $factory->getNumber("n$i",
                sprintf("%.1f", ($current['total'] / 1024 / 1024)),
                '', '', 'r');

    $device->setPreserveData(false);
    $size->setPreserveData(false);
    $mounted->setPreserveData(false);

    $disk_list->addEntry(
            array(
                    $device,
                    $size,
                    $mounted,
                    $actions
                ),
                 "", false, $i);              
}

$page =& $factory->getPage();
$visit_mark =& $factory->getTextField("re_sort", 1, "");
$setup_button =& $factory->getButton('/base/storage/storageSetup.php',
                                    'setupNewDisks');
$new_disks = $cce->find('Disk', array('new' => 1, 'internal' =>0));
if (count($new_disks) < 1)
{
    $setup_button->setDisabled(true);
}

print $page->toHeaderHtml();
print $setup_button->toHtml();
print "<p></p>\n";
print $visit_mark->toHtml();
print $disk_list->toHtml();
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
