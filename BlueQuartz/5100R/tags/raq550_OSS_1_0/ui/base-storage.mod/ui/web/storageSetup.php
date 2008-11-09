<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: storageSetup.php,v 1.5 2001/11/05 20:36:25 pbaltz Exp $
//
// list "new" storage devices to allow the user to set things up
// how they want

include_once('ServerScriptHelper.php');

$helper =& new ServerScriptHelper();
$factory =& $helper->getHtmlComponentFactory('base-storage');
$cce =& $helper->getCceClient();

$new_disk_list =& $factory->getScrollList('newDiskList',
                                array('deviceName', 'size', 'parts', 'setup'),
                                array(1));

$new_disks = $cce->findx('Disk', array('internal' => 0, 'new' => 1),
                            array('device' => '[^0-9]$'), 
                            'old_numeric', 'total');

// just go to storage list if no new disks
if (count($new_disks) == 0)
{
    header("Location: /base/storage/storageList.php");
    $helper->destructor();
    exit;
}

$new_disk_list->setSortEnabled(true);
if ($new_disk_list->getSortOrder() == "descending")
{
    $new_disks = array_reverse($new_disks);
}

$new_disk_list->setAlignments(array('left', 'right', 'center'));
$new_disk_list->setColumnWidths(array('32%', '32%', '31%', '5%'));
$new_disk_list->setEntryNum(count($new_disks));
$new_disk_list->setLength(15);

$start_index = 15 * $new_disk_list->getPageIndex();

// generate the list
for ($i = $start_index; ($i < count($new_disks)) && ($i < $start_index + 15);
    $i++)
{
    $current =& $cce->get($new_disks[$i]);

    $matches = array();
    if (ereg("^([^0-9]+)$", $current['device'], $matches))
    {
        // check for partitions
        $parts = $cce->findx('Disk', array(), 
                            array('device' => ('^' . $matches[1] . '[0-9]+$')));
    }

    // the setup icon
    $modify = "/base/storage/storageWizard.php?_oid=" . $new_disks[$i]
                . '&parts=' . (count($parts) ? 1 : 0);

    $actions =& $factory->getCompositeFormField();
    $actions->addFormField($factory->getModifyButton($modify));

    $device =& $factory->getTextField($i, $current['device'], 'r');

    $size =& $factory->getNumber("m$i", 
                    sprintf("%.1f", ($current['total'] / 1024 / 1024)), 
                    '', '', 'r');

    $num_partitions =& $factory->getTextField("p$i", count($parts), 'r');
    
    $device->setPreserveData(false);
    $size->setPreserveData(false);

    $new_disk_list->addEntry(
            array(
                    $device,
                    $size,
                    $num_partitions,
                    $actions
                ),
                 "", false, $i);              
}

$page =& $factory->getPage();
$back =& $factory->getBackButton('/base/storage/storageList.php');

print $page->toHeaderHtml();
print $new_disk_list->toHtml();
print "<p></p>\n";
print $back->toHtml();
print $page->toFooterHtml();
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
