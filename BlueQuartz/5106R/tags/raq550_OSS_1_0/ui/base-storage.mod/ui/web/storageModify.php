<?php
// $Id: storageModify.php,v 1.5 2001/11/05 22:49:02 pbaltz Exp $
// Copyright 2001 Sun Microsystems, Inc.  All right reserved.
//
// one stop page to change all the information about a partition

include_once('ServerScriptHelper.php');

$helper =& new ServerScriptHelper();
$factory =& $helper->getHtmlComponentFactory('base-storage',
                                    '/base/storage/storageModify.php');
$cce =& $helper->getCceClient();

if ($save)
{
    $ok = $cce->set($_oid, '',
                    array(
                        'label' => $label,
                        'mount' => $mount,
                        'isHomePartition' => $isHomePartition
                        ));
    
    $errors = $cce->errors();
    
    if ($ok)
    {
        if ($new)
            $target = '/base/storage/storageSetup.php';
        else
            $target = '/base/storage/storageList.php';
            
        print $helper->toHandlerHtml($target, $errors, false);
        $helper->destructor();
        exit;
    }
}

$disk = $cce->get($_oid);

$settings =& $factory->getPagedBlock('modifyDisk');
$settings->processErrors($errors);
$settings->setLabel(
    $factory->getLabel('modifyDisk', false, array('device' => $disk['device'])));

$settings->addFormField(
        $factory->getTextField('label', $disk['label']),
        $factory->getLabel('label')
        );

$settings->addFormField(
        $factory->getBoolean('mount', $disk['mount']),
        $factory->getLabel('mount')
        );

$settings->addFormField(
        $factory->getBoolean('isHomePartition', $disk['isHomePartition']),
        $factory->getLabel('isHomePartition')
        );

$page =& $factory->getPage();
$form =& $page->getForm();

$settings->addButton($factory->getSaveButton($form->getSubmitAction()));
if (!$new)
    $settings->addButton(
        $factory->getCancelButton('/base/storage/storageList.php'));

// add hidden stuff
$settings->addFormField($factory->getTextField('_oid', $_oid, ''));
$settings->addFormField($factory->getTextField('save', 1, ''));
if (isset($new))
    $settings->addFormField($factory->getTextField('new', 1, ''));

$format_button =& $factory->getButton(
                "javascript: confirmFormat();", 'formatDisk');

print $page->toHeaderHtml();
print $format_button->toHtml();
print "\n<P></P>\n";
print $settings->toHtml();

$i18n = $factory->getI18n();
$formatQuestion = $i18n->interpolateJs('[[base-storage.formatQuestion]]',
                            array('device' => $disk['device']));
?>
<SCRIPT LANGUAGE="javascript">
<!--
var formatQuestion = '<? print($formatQuestion); ?>';

function confirmFormat()
{
    if (confirm(formatQuestion))
        location.replace(
            '/base/storage/storageFormat.php?oid=<? print($_oid); ?>');

}
// -->
</SCRIPT>

<?
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
