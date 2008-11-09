<?php
// $Id: storageWizard.php,v 1.15 2001/11/05 22:49:02 pbaltz Exp $
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.

// setup new external storage. partition and format.

include_once("ServerScriptHelper.php");

$helper =& new ServerScriptHelper();
$factory =& $helper->getHtmlComponentFactory('base-storage',
                '/base/storage/storageWizard.php');
$cce =& $helper->getCceClient();

$disk = $cce->get($_oid);

// handle setting attributes here
if (count($HTTP_POST_VARS))
{
    $errors = array();

    // get any partitions
    $parts = $cce->findx('Disk', array(), 
                   array('device' => '^' . $disk['device'] . '[0-9]+$'));
  
    if ($setupOptions == 'useExistingParts')
    {
        // destroy block device and set other part to new
        $ok = $cce->destroy($_oid);
        if ($ok)
        {
            foreach ($parts as $part)
                $cce->set($part, '', array('new' => 0));
        }

        $target = '/base/storage/storageSetup.php';
    }
    else if ($setupOptions == 'eraseDisk')
    {
        // destroy all partitions
        foreach ($parts as $part)
            $cce->destroy($part);

        // do the necessary set in CCE.
        $ok = $cce->set($_oid, '', 
            array(
                'eraseDisk' => time(),
                'fsType' => $fsType,
                'check' => $checkDisk,
                'new' => 0,
                'quota' => 1
                ));

        $target = "/base/storage/storageModify.php?_oid=$_oid&new=1";
    }
/*  don't worry about raid options for now, not enough time to make it safe
 *  else if ($setupOptions == 'appendDisk')
 *  {
 *      $target = "/base/storage/raidStatus.php";
 *  }
 *  else if ($setupOptions == 'mirrorDisk')
 *  {
 *      $target = "/base/storage/raidStatus.php";
 *  }
 *  else if ($setupOptions == 'spareMirrorDisk')
 *  {
 *      $target = "/base/storage/raidStatus.php";
 *  }
 */

    $errors =& array_merge($errors, $cce->errors());

    if ($ok)
    {
        print $helper->toHandlerHtml(
            $target,
            $errors,
            false);
        $helper->destructor();
        exit;
    }
}

$block =& $factory->getPagedBlock('setupDisk');
$block->processErrors($errors);
$block->setLabel(
    $factory->getLabel('setupDisk', false, 
                array('device' => $disk['device'])));

// leave this here in case we ever want to allow different filesystems
/*
 * $block->addFormField(
 *  $factory->getMultiChoice("fsType", array("ext2", "reiserfs", "xfs")),
 *  $factory->getLabel("fsType")
 *   );
 */
$block->addFormField($factory->getTextField('fsType', 'xfs', ''));
    
// since we use xfs just don't show the check disk option
/*
 * $block->addFormField(
 *     $factory->getBoolean('checkDisk', true),
 *     $factory->getLabel('checkDisk')
 *     );
 */
$block->addFormField($factory->getBoolean('checkDisk', 0, ''));

$setup_options =& $factory->getMultiChoice('setupOptions');
$setup_options->addOption($factory->getOption('selectDiskOption', true));
$setup_options->addOption($factory->getOption('eraseDisk'));

// no raid for now
// $setup_options->addOption($factory->getOption('appendDisk'));

/*
 * no raid for now with additional storage
 // add the option to mirror /home if the disk is big enough
 *list($sys_oid) = $cce->find('System');
 *$raid = $cce->get($sys_oid, 'RAID');
 *list($home_oid) = $cce->find('Disk', array('mountPoint' => '/home'));
 *$home = $cce->get($home_oid);
 *
 *if ($disk['total'] > $home['total'])
 *{
 *    if ($raid['disks'] == 1 || $raid['level'] == 0)
 *    {
 *        $setup_options->addOption($factory->getOption('mirrorDisk'));
 *    }
 *    else if ($raid['level'] == 1)
 *    {
 *        $setup_options->addOption($factory->getOption('spareMirrorDisk'));
 *    }
 *}
 */

// add use existing partitions option if the disk is partitioned already
if ($parts)
    $setup_options->addOption($factory->getOption('useExistingParts'));

$block->addFormField($setup_options, $factory->getLabel('setupOptions'));

$page =& $factory->getPage();
$form =& $page->getForm();
$block->addButton($factory->getSaveButton($form->getSubmitAction()));
$block->addButton($factory->getCancelButton("/base/storage/storageSetup.php"));

$hidden_oid =& $factory->getTextField("_oid", $_oid, "");

print $page->toHeaderHtml();
print $hidden_oid->toHtml();
print $block->toHtml();

// add warning dialogs for both fields
$form =& $page->getForm();
$formID = $form->getId();
$i18n = $factory->getI18n();
$eraseDiskQuestion = $i18n->interpolateJs('[[base-storage.eraseDiskQuestion]]');
$appendDiskQuestion = $i18n->interpolateJs(
                                    '[[base-storage.appendDiskQuestion]]');
$mirrorDiskQuestion = $i18n->interpolateJs(
                                    '[[base-storage.mirrorDiskQuestion]]');
$selectOptionError = $i18n->interpolateJs(
                                    '[[base-storage.selectOptionError]]');
?>
<SCRIPT LANGUAGE="javascript">
<!--

var eraseConfirm = '<? print($eraseDiskQuestion); ?>';
var appendConfirm = '<? print($appendDiskQuestion); ?>';
var mirrorDiskConfirm = '<? print($mirrorDiskQuestion); ?>';
var selectOption = '<? print($selectOptionError); ?>';

var oldsubmit = document.<? print($formID); ?>.onsubmit;

function confirmAsk()
{
    var setupOptions = document.<? print($formID); ?>.setupOptions;
    var index = setupOptions.selectedIndex;

    if (index == 0)
    {
        top.code.info_show(selectOption, 'error');
        return false;
    }
    
    if (setupOptions.options[index].value == 'eraseDisk')
    {
        if (!confirm(eraseConfirm))
            return false;
    }
    else if (setupOptions.options[index].value == 'appendDisk')
    {
        if (!confirm(appendConfirm))
            return false;
    }
    else if (setupOptions.options[index].value == 'mirrorDisk' || 
             setupOptions.options[index].value == 'spareMirrorDisk')
    {
        if (!confirm(mirrorDiskConfirm))
            return false;
    }

    return oldsubmit();
}

document.<? print($formID); ?>.onsubmit = confirmAsk;

//-->
</SCRIPT>
<?
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
