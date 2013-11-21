<?php
// $Id: adminList.php,v 1.7.2.2 2002/02/22 01:23:02 pbaltz Exp $
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
//
// list the non sys-admin adminUsers for the machine and allow them to be
// edited

include_once('ServerScriptHelper.php');

$helper =& new ServerScriptHelper();

// Only admin should be here
if ($loginName != "admin") {
    header("location: /error/forbidden.html");
    return;
}

$factory =& $helper->getHtmlComponentFactory('base-vsite');
$cce =& $helper->getCceClient();
$i18n = $factory->getI18n();

$admin_list = $factory->getScrollList('adminUsersList',
                                    array('fullName', 'userName', 
                                                'userSuspended', 'actions'),
                                    array(1, 2));

$admin_list->processErrors($helper->getErrors());

$admin_list->addButton(
    $factory->getAddButton('/base/vsite/manageAdmin.php',
                            '[[base-vsite.addAdminHelp]]')
    );
$admins_per_page = 15;

$sort_fields = array(0 => 'fullName', 1 => 'name', 2 => 'ui_enabled');
$sort_type_map = array(0 => 'ascii', 1 => 'ascii', 2 => 'old_numeric');

$admins = $cce->findx('User', 
                array('systemAdministrator' => 0, capLevels => 'adminUser'),
                array(), 
                $sort_type_map[$admin_list->getSortedIndex()],
                $sort_fields[$admin_list->getSortedIndex()]);

$admin_list->setSortEnabled(false);
$admin_list->setColumnWidths(array("45%", "45%", "10%"));
$admin_list->setEntryNum(count($admins));
$admin_list->setLength($admins_per_page);

$current_page = $admin_list->getPageIndex();
$start_index = $admins_per_page * $current_page;

if ($admin_list->getSortOrder() == 'descending')
    $admins = array_reverse($admins);

for ($i = $start_index;
        $i < ($start_index + $admins_per_page) && $i < count($admins); $i++)
{
    $current = $cce->get($admins[$i]);

    $actions = $factory->getCompositeFormField();
    $modify = $factory->getModifyButton(
                    "/base/vsite/manageAdmin.php?_oid=$admins[$i]");

    $actions->addFormField($modify);

    $remove = $factory->getRemoveButton(
                "javascript: del_admin_user($admins[$i], '$current[name]');");
    
    $actions->addFormField($remove);

    $full_name =& $factory->getTextField("fn$i", $current['fullName'], 'r');
    $full_name->setPreserveData(false);

    $user_name =& $factory->getTextField("un$i", $current['name'], 'r');
    $user_name->setPreserveData(false);
    
    $yes_no = $current['ui_enabled'] ? ' ' : '[[base-vsite.suspended]]';
    $suspended =& $factory->getTextField("su$i", 
                        $factory->i18n->get($yes_no), 'r');
    $suspended->setPreserveData(false);

    $admin_list->addEntry(
                    array($full_name, $user_name, $suspended, $actions), 
                    '', false, $i);
}

$page =& $factory->getPage();

print $page->toHeaderHtml();
print $admin_list->toHtml();

$confirmDelete = $i18n->interpolateJs('[[base-vsite.deleteQuestion]]');
$deletingUser = $i18n->interpolateJs('[[base-vsite.deletingUser]]');
?>
<SCRIPT LANGUAGE="javascript">
<!--
var confirmDelete = '<?php print ($confirmDelete); ?>';
var deleting = '<?php print ($deletingUser); ?>';

function del_admin_user(oid, name)
{
    if (confirm(top.code.string_substitute(confirmDelete, '[[VAR.name]]', name)))
    {
        top.code.info_show(deleting, 'wait');
        location.replace('/base/vsite/deleteAdminUser.php?oid=' + oid);
    }
}
// -->
</SCRIPT>
<?php
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
