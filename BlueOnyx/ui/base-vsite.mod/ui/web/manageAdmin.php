<?php
// Copyright 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: manageAdmin.php
//
// manage admin page

include_once('ServerScriptHelper.php');

$helper = new ServerScriptHelper();

// Only admin should be here
if ($loginName != "admin") {
    header("location: /error/forbidden.html");
    return;
}

$factory =& $helper->getHtmlComponentFactory('base-vsite', 
                                '/base/vsite/manageAdmin.php');
$cce =& $helper->getCceClient();
$i18n =& $factory->getI18n();

$possible_caps = array(
            'manageSite' => 1,
            'siteDNS' => 1,
            'serverShowActiveMonitor' => 1,
            'serverInformation' => 1,
            'serverHttpd' => 1,
            'serverFTP' => 1,
            'serverEmail' => 1,
            'serverDNS' => 1,
            'serverSNMP' => 1,
            'serverShell' => 1,
            'serveriStat' => 1,
            'serverSSL' => 1,
            'serverNetwork' => 1,
            'serverIpPooling' => 1,
            'serverVsite' => 1,
            'serverPower' => 1,
            'serverTime' => 1,
            'serverServerDesktop' => 1,
//            'menuServerServerStats' => 1,
            'serverActiveMonitor' => 1,
            'managePackage' => 1,
            'menuServerSecurity' => 1
                        );

if (isset($_oid))
    $user = $cce->get($_oid);

// handle setting stuff if this is the submit phase
if ($submitted)
{
    list($ok, $errors) = handle_admin_settings($helper, $cce, 
                                            $user, $possible_caps);
    if ($ok)
    {
        print $helper->toHandlerHtml('/base/vsite/adminList.php', 
                                $errors, false);
        exit;
    }
}

$admin_settings =& $factory->getPagedBlock('manageAdmin');
$admin_settings->processErrors($errors);

if (isset($_oid))
{
    $admin_settings->setLabel(
        $factory->getLabel('manageAdmin', 
                        false, array('name' => $user['name']))
        );
}
else
{
    $admin_settings->setLabel(
        $factory->getLabel('createAdminUser', false));
}

$admin_settings->addDivider($factory->getLabel('userInformation', false));

// full name field
$admin_settings->addFormField(
    $factory->getFullName('fullName', $user['fullName']),
    $factory->getLabel('fullName')
    );

// add the sort name field if necessary
if ($i18n->getProperty('needSortName') == 'yes') {
    $sortName =& $factory->getFullName('sortNameField', $user['sortName']);
    $sortName->setOptional('silent');
    $admin_settings->addFormField(
        $sortName,
        $factory->getLabel('sortNameField'));
}

// if this is a create, add the username field
if (!isset($_oid))
{
    $admin_settings->addFormField(
        $factory->getUserName('userName'),
        $factory->getLabel('userNameCreate')
        );
}

// don't pass back data for password fields
$pass_field =& $factory->getPassword('password');
$pass_field->setPreserveData(false);
if (isset($_oid))
    $pass_field->setOptional(true);

$admin_settings->addFormField(
    $pass_field,
    $factory->getLabel('userPassword')
    );

if (isset($_oid))
{
    $disk = $cce->get($_oid, 'Disk');
    $displayed_quota = ($disk['quota'] == -1 ? '' : $disk['quota']);
}
else
    $displayed_quota = 20;

$disk_quota =& $factory->getInteger('diskQuota', $displayed_quota, 1);
$disk_quota->setOptional('silent');
$admin_settings->addFormField(
    $disk_quota,
    $factory->getLabel('userDiskQuota')
    );

// Server Admin Shell
$userShell = $cce->get($_oid, 'Shell');
$admin_settings->addFormField(
    $factory->getBoolean('shell', ($userShell['enabled'] ? 1 : 0)),
    $factory->getLabel('userShell'));

// add suspend check box to be consistent
if (isset($_oid))
{
    $admin_settings->addFormField(
            $factory->getBoolean('suspend', ($user['ui_enabled'] ? 0 : 1)),
            $factory->getLabel('suspendUser'));
}


// display site controls
$admin_settings->addDivider($factory->getLabel('adminSites', false));

if (isset($_oid))
{
    $site = $cce->get($_oid, 'Sites');
    $sites_quota = ($site['quota'] == -1 ? '' : $site['quota']);
    $sites_max = ($site['max'] == -1 ? '' : $site['max']);
    $sites_user = ($site['user'] == -1 ? '' : $site['user']);
}
else
{
    $sites_quota = 200;
    $sites_max = 5;
    $sites_user = 100;
}

$site_quota =& $factory->getInteger('siteQuota', $sites_quota, 1);
$site_quota->setOptional('silent');
$admin_settings->addFormField(
    $site_quota,
    $factory->getLabel('userSitesQuota')
    );

$site_max =& $factory->getInteger('siteMax', $sites_max, 1);
$site_max->setOptional('silent');
$admin_settings->addFormField(
    $site_max,
    $factory->getLabel('userSitesMax')
    );

$site_user =& $factory->getInteger('siteUser', $sites_user, 1);
$site_user->setOptional('silent');
$admin_settings->addFormField(
    $site_user,
    $factory->getLabel('userSitesUser')
    );

// display admin controls
if (isset($_oid))
    $root_access = $cce->get($_oid, 'RootAccess');

$admin_settings->addDivider($factory->getLabel('adminOptions', false));

// get strings to use as labels
list($caps_oid) = $cce->find('Capabilities');
$possible_labels = array();
foreach ($possible_caps as $cap => $junk)
{
    $ns = $cce->get($caps_oid, $cap);
    $possible_labels[$cap] = $i18n->get($ns['nameTag']);
}

$allowed_caps = array();
$allowed_labels = array();
$caps = $cce->scalar_to_array($user['capLevels']);

foreach ($caps as $capability)
{
    if ($possible_caps[$capability])
    {
        $allowed_caps[] = $capability;
        $allowed_labels[] = $possible_labels[$capability];
        unset($possible_caps[$capability]);
        unset($possible_labels[$capability]);
    }
}

// hack in root access
if ($root_access['enabled'])
{
    $allowed_labels[] = $i18n->get('[[base-vsite.rootAccess]]');
    $allowed_caps[] = 'rootAccess';
}
else
{
    $possible_labels[] = $i18n->get('[[base-vsite.rootAccess]]');
    $possible_caps['rootAccess'] = 1;
}

$select_caps =& $factory->getSetSelector('adminPowers',
                    $cce->array_to_scalar($allowed_labels), 
                    $cce->array_to_scalar($possible_labels),
                    'allowedAbilities', 'disallowedAbilities',
                    'rw', 
                    $cce->array_to_scalar($allowed_caps),
                    $cce->array_to_scalar(array_keys($possible_caps)),
                    10);
   
$select_caps->setOptional(true);

$admin_settings->addFormField($select_caps, 
            $factory->getLabel('adminPowers'));

$admin_settings->addFormField($factory->getTextField('submitted', 1, ''));
if (isset($_oid))
    $admin_settings->addFormField($factory->getTextField('_oid', $_oid, ''));

$page =& $factory->getPage();
$form =& $page->getForm();
$admin_settings->addButton($factory->getSaveButton($form->getSubmitAction()));
$admin_settings->addButton(
        $factory->getCancelButton('/base/vsite/adminList.php'));

print $page->toHeaderHtml();
print $admin_settings->toHtml();
print $page->toFooterHtml();

$helper->destructor();

function handle_admin_settings(&$helper, &$cce, &$user, $special_caps)
{
    global $fullName, $sortNameField, $userName, $password, $diskQuota, $shell;
    global $siteQuota, $siteMax, $siteUser;
    global $_oid, $rootAccess, $adminPowers, $suspend;

    $errors = array();
   
    // Only run cracklib checks if something was entered into the password field:
    if ($password) {

        // Check password
        // Username = Password? Baaaad idea!
        if (strcasecmp($userName, $password) == 0) {
            $attributes["password"] = "1";
            $error_msg = "[[base-user.error-password-equals-username]] [[base-user.error-invalid-password]]";
            $errors[] = new Error($error_msg);
            return array(0, $errors);
        }

        // Open CrackLib Dictionary for usage:
        $dictionary = crack_opendict('/usr/share/dict/pw_dict') or die('Unable to open CrackLib dictionary');

        // Perform password check with cracklib:
        $check = crack_check($dictionary, $password);

        // Retrieve messages from cracklib:
        $diag = crack_getlastmessage();

        if ($diag == 'strong password') {
            // Nothing to do. Cracklib thinks it's a good password.
        } else {
            $attributes["password"] = "1";
            $errors[] = new Error("[[base-user.error-password-invalid]]" . $diag . " . " . "[[base-user.error-invalid-password]]");
            return array(0, $errors);
        }

        // Close cracklib dictionary:
        crack_closedict($dictionary);
    }

    $current_caps = $cce->scalar_to_array($user['capLevels']);
    
    // remove the special capabilities from the user's current ones
    remove_caps($current_caps, $special_caps);

    if (!in_array('adminUser', $current_caps)) {
        $current_caps[] = 'adminUser';
    }

    // hack root access back out
    if (preg_match("/&rootAccess&/", $adminPowers)) 
        $rootAccess = 1;
    else
        $rootAccess = 0;

    $adminPowers = preg_replace('/&(rootAccess)&/', '&', $adminPowers); 
    $current_caps = array_merge($current_caps, 
                        $cce->scalar_to_array($adminPowers));

    $cap_string = $cce->array_to_scalar($current_caps);
    if (preg_match("/^&+$/", $cap_string))
        $cap_string = '';
    else
        $cap_string = preg_replace('/&&/', '&', $cap_string);

    // handle create if necessary
    if (!isset($_oid))
    {
        $big_ok = $cce->create('User',
                        array(
                            'fullName' => $fullName,
                            'sortName' => $sortNameField,
                            'name' => $userName,
                            'password' => $password,
                            'capLevels' => $cap_string
                            ));

        if ($big_ok)
            $user['OID'] = $big_ok;
    }
    else
    {
        $new_settings = array(
                            'fullName' => $fullName,
                            'sortName' => $sortNameField,
                            'capLevels' => $cap_string,
                            'ui_enabled' => ($suspend ? 0 : 1)
                            );
                            
        if ($password)
            $new_settings['password'] = $password;
            
        $big_ok = $cce->set($user['OID'], '', $new_settings);
    }

    $errors = array_merge($errors, $cce->errors());

    // set disk quota
    if ($big_ok)
    {
        $cce->set($user['OID'], 'Disk', 
            array('quota' => ($diskQuota == '' ? -1 : $diskQuota)));
        $errors = array_merge($errors, $cce->errors());
    }

    // set the root access flag
    if ($big_ok)
    {
        $ok = $cce->set($user['OID'], 'RootAccess',
                    array('enabled' => $rootAccess));
        $errors = array_merge($errors, $cce->errors());

        $ok = $cce->set($user['OID'], 'Shell', array('enabled' => ($shell ? 1 : 0)));
        $errors = array_merge($errors, $cce->errors());
    }

    // set sites information
    if ($big_ok)
    {
        $cce->set($user['OID'], 'Sites',
            array('quota' => ($siteQuota == '' ? -1 : $siteQuota),
                  'max' => ($siteMax == '' ? -1 : $siteMax),
                  'user' => ($siteUser == '' ? -1 : $siteUser)));
        $errors = array_merge($errors, $cce->errors());
    }

    return array($big_ok, $errors);
}

function remove_caps(&$current_caps, $special_caps)
{
    foreach ($current_caps as $key => $cap)
    {
        // remove any sys admin special capabilities
        if ($special_caps[$cap])
            unset($current_caps[$key]);
    }
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>