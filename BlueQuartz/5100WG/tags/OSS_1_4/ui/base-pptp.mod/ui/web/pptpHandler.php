<?

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();

$cce->setObject("System", array(
	'enabled' => ($pptp_access_field!="pptp_access_disabled"?1:0),
	'allowType' => ($pptp_access_field=="pptp_access_someusers"?"some":"all"),
	'allowData' => ($pptp_access_someusers_selector),
	'dns' => $pptp_dns_addresses,
	'wins' => $pptp_wins_addresses
	),
	"Pptp");

$errors = $cce->errors();
if (count($errors) > 0) {
  print $serverScriptHelper->toHandlerHtml("/base/pptp/pptp.php", $cce->errors());
  exit;
}

$usersWithoutSecrets = array();
/* Look for new users that are being enabled that don't have a secret set */
if ($pptp_access_field=="pptp_access_someusers") {
  $SetUsers = stringToArray($pptp_access_someusers_selector);

  foreach ($SetUsers as $user) {
    $userPptp = $cce->getObject("User", array(name=>$user), "Pptp");
    if (!$userPptp["secret"]) 
      $usersWithoutSecrets[] = $user;
  }

} else if ($pptp_access_field=="pptp_access_allusers") {
  /* grab an entire list of users from cce */
  $userOids = $cce->find("User");
  foreach ($userOids as $userOid) {
    $userpptp = $cce->get($userOid, "Pptp");
    $user = $cce->get($userOid);
    if (!$userpptp["secret"]) {
      $usersWithoutSecrets[] = $user["name"];
    }
  }
}

/* do the proper redirect */
if ($pptp_access_field=="pptp_access_someusers" 
 || $pptp_access_field=="pptp_access_allusers") {

  /* only display this page if there is a user that doesn't have a secret set */

  if (count($usersWithoutSecrets)>0) {
    /* Display the Notify Query Page */
    $userstring = urlencode (arrayToString($usersWithoutSecrets));
    print $serverScriptHelper->toHandlerHtml("/base/pptp/pptpNotify.php?users=$userstring");
    exit;
  }
}


print $serverScriptHelper->toHandlerHtml("/base/pptp/pptp.php");
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

