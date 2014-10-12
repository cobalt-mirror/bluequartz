<?php
/*
 * $Id: userModHandler.php
 */

include_once("ArrayPacker.php");
include_once("ServerScriptHelper.php");
include_once("AutoFeatures.php");
include_once("BXEncoding.php");

$serverScriptHelper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$serverScriptHelper->getAllowed('adminUser') &&
    !($serverScriptHelper->getAllowed('siteAdmin') &&
      $group == $serverScriptHelper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

// Start sane:
$errors = array();

$autoFeatures = new AutoFeatures($serverScriptHelper);
$cceClient = $serverScriptHelper->getCceClient();

$oids = $cceClient->find("User", array("name" => $userNameField));
$iam = $serverScriptHelper->getLoginName();

// modify user
$attributes = array("fullName" => $fullNameField);
if($sortNameField)
  $attributes["sortName"]=$sortNameField;
if($passwordField)
  $attributes["password"] = $passwordField;
$obj = $cceClient->get($oids[0], "");
if (!$obj["desc_readonly"]) {
  $attributes["description"] = $userDescField;  
}

# don't set this attribute now if a siteadmin is trying to demote himself
if (isset($siteAdministrator) && ($siteAdministrator || (!$siteAdministrator && ($iam != $userNameField))))
  $attributes["capLevels"] = ($siteAdministrator ? '&siteAdmin&' : '');

if (isset($dnsAdministrator) && ($dnsAdministrator || (!$dnsAdministrator && ($iam != $userNameField))))
  $attributes["capLevels"] .= ($dnsAdministrator ? '&siteDNS&' : '');

// dirty trick
$attributes["capLevels"] = str_replace("&&", "&", $attributes["capLevels"]);

if (isset($suspendUser))
  $attributes['ui_enabled'] = ($suspendUser) ? '0' : '1';

// Handle FTP access clauses:
if ($ftpForNonSiteAdmins == "0") {
    $hasNoFTPaccess = "1";
}
else {
    $hasNoFTPaccess = "0";
}

if ($siteAdministrator == "1") {
    $hasNoFTPaccess = "0";
}

// Username = Password? Baaaad idea!
if (strcasecmp($userNameField, $passwordField) == 0) {
        $attributes["password"] = "1";
        $error_msg = "[[base-user.error-password-equals-username]] [[base-user.error-invalid-password]]";
        $errors[] = new Error($error_msg);
}

// Only run cracklib checks if something was entered into the password field:
if ($passwordField) {

    // Open CrackLib Dictionary for usage:
    $dictionary = crack_opendict('/usr/share/dict/pw_dict') or die('Unable to open CrackLib dictionary');

    // Perform password check with cracklib:
    $check = crack_check($dictionary, $passwordField);

    // Retrieve messages from cracklib:
    $diag = crack_getlastmessage();

    if ($diag == 'strong password') {
        // Nothing to do. Cracklib thinks it's a good password.
    }
    else {
        $attributes["password"] = "1";
        $errors[] = new Error("[[base-user.error-password-invalid]]" . $diag . ". " . "[[base-user.error-invalid-password]]");
    }

    // Close cracklib dictionary:
    crack_closedict($dictionary);
}

$attributes['emailDisabled'] = $emailDisabled;

$attributes['ftpDisabled'] = $hasNoFTPaccess;

$cceClient->set($oids[0], "", $attributes);
//$errors = $cceClient->errors();

for ($i = 0; $i < count($errors); $i++) {
  if ( ($errors[$i]->code == 2) && ($errors[$i]->key === "password"))
  {
    $errors[$i]->message = "[[base-user.error-invalid-password]]";
  }
}

// set quota
if(!$maxDiskSpaceField)
  $maxDiskSpaceField = -1;

$cceClient->set($oids[0], "Disk", array("quota" => $maxDiskSpaceField));
$errors = array_merge($errors, $cceClient->errors());

if ( isset( $group ) && $group != "" )
  $site = $group;

// handle autofeatures
list($userservices) = $cceClient->find("UserServices", array("site" => $site));
$af_errors = $autoFeatures->handle("modify.User", array("CCE_SERVICES_OID" =>
$userservices, "CCE_OID" => $oids[0]));
$errors = array_merge($errors, $af_errors);

$userNames = $cceClient->names("User");

// set group membership
$memberGroups = stringToArray($memberGroupsField);
$goids = $cceClient->find("Workgroup");
for($i = 0; $i < count($goids); $i++) {
  $group = $cceClient->get($goids[$i]);
  $members = stringToArray($group["members"]);

  if(in_array($group["name"], $memberGroups)) {
    // add user to group
    $found = false;
    for($j = 0; $j < count($members); $j++)
  if($members[$j] == $userNameField) {
    $found = true;
    break;
  }
    if(!$found)
  $members[] = $userNameField;
  }
  else {
    // remove user from group
    for($j = 0; $j < count($members); $j++)
  if($members[$j] == $userNameField) {
    array_splice($members, $j, 1);
    break;
  }
  }

  // update CCE
  $cceClient->set($goids[$i], "", array("members" => arrayToString($members)));
  $errors = array_merge($errors, $cceClient->errors());
}

// Special case: If user is disabled, forwarding WILL be off:
$Uoids = $cceClient->find("User", array("name" => $userNameField));
$user = $cceClient->get($Uoids[0]);
if ($user['ui_enabled'] == "0") {
    $forwardEnableField = "0";
}

// set email forwarding info
$cceClient->set($oids[0], "Email", array(
  "forwardEnable" => ($forwardEnableField ? 1 : 0), 
  "forwardEmail" => $forwardEmailField, 
  "forwardSave" => $forwardSaveField));
$errors = array_merge($errors, $cceClient->errors());

// set email aliases info

//Prune the duplicate email aliases
$emailAliasesFieldArray = $cceClient->scalar_to_array($emailAliasesField);
$emailAliasesFieldArray = array_unique($emailAliasesFieldArray);
$emailAliasesField = $cceClient->array_to_scalar($emailAliasesFieldArray);

// replace && with &, to avoid always getting a blank alias in the field
// in cce, this also skirts around dealing with browser issues
$emailAliasesField = str_replace("&&", "&", $emailAliasesField);
if ($emailAliasesField == '&') {
  $emailAliasesField = '';
}

$attributes = array("aliases" => $emailAliasesField);
#if ( isset($apop) ) 
#  $attributes["apop"] = $apop;

$cceClient->set($oids[0], "Email", $attributes);
$errors = array_merge($errors, $cceClient->errors());

// set vacation info
if ($_autoRespondStartDate_amPm == "PM") { 
  $_autoRespondStartDate_hour = $_autoRespondStartDate_hour + 12; 
 } 

if ($_autoRespondStopDate_amPm == "PM") { 
  $_autoRespondStopDate_hour = $_autoRespondStopDate_hour + 12; 
 } 

$vacationMsgStart = mktime($_autoRespondStartDate_hour, $_autoRespondStartDate_minute, 
         $_autoRespondStartDate_second, $_autoRespondStartDate_month, 
         $_autoRespondStartDate_day, $_autoRespondStartDate_year); 
$vacationMsgStop = mktime($_autoRespondStopDate_hour, $_autoRespondStopDate_minute, 
        $_autoRespondStopDate_second, $_autoRespondStopDate_month, 
        $_autoRespondStopDate_day, $_autoRespondStopDate_year); 

if (($vacationMsgStop - $vacationMsgStart) < 0) { 
  $vacationMsgStop = $oldStop; 
  
  $error_msg = "[[base-user.invalidVacationDate]]"; 
  $errors[] = new Error($error_msg); 
 } 

if (isset($autoResponderMessageField)) {
  $cceClient->set($oids[0], "Email", array( 
    "vacationOn" => ($autoResponderField ? 1 : 0), 
    "vacationMsg" => BXEncoding::toUTF8($autoResponderMessageField), 
    "vacationMsgStart" => $vacationMsgStart, 
    "vacationMsgStop" =>$vacationMsgStop)); 
}
else {
  $cceClient->set($oids[0], "Email", array( 
    "vacationOn" => ($autoResponderField ? 1 : 0), 
    "vacationMsgStart" => $vacationMsgStart, 
    "vacationMsgStop" =>$vacationMsgStop));   
}

$errors = array_merge($errors, $cceClient->errors());

# log the user out if they are trying to demote themself
if (isset($siteAdministrator) && !$siteAdministrator && ($iam == $userNameField)) {
  $cceClient->set($oids[0], "", array('capLevels' => ''));
  print($serverScriptHelper->toHandlerHtml("/logoutHandler.php"));
  $serverScriptHelper->destructor();
  exit(0);
}

print($serverScriptHelper->toHandlerHtml("/base/user/userList.php?group=" . $obj["site"], $errors, false));

$serverScriptHelper->destructor();

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