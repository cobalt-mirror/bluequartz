<?

/* This script sends emails to those users specified in $users
 * and returns to the main pptp page */

include_once("ServerScriptHelper.php");
include_once("ArrayPacker.php");

/* TODO: add a check for the pptp capability */

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();

$userAr = stringToArray($users);

/* default locale for messages */
$system = $cce->getObject("System");
$hostname = $system["hostname"] . "." . $system["domainname"];
$defaultLocale = $system["productLanguage"];

$thisUser = $cce->getObject("User", array(name=>$serverScriptHelper->getLoginName()));


/* Create an array of args.  We cluster the message by Locale */
$toArgs = array();

foreach ($userAr as $user) {
  $oids = $cce->find("User", array(name=>$user));
  if (count($oids)!=1) 
    continue;
  $obj = $cce->get($oids[0]);
  $locale = $obj["localePreference"];
  if ($locale == "browser") 
    $locale = $defaultLocale;

  $tmp=array(
	str_replace(",", "\\,", escapeShellArg($obj["fullName"]." <".$user."@".$hostname.">")), 
	$locale);
  array_push($toArgs, $tmp);
}


foreach ($toArgs as $info) {
  $toArg=$info[0];
  $locale=$info[1];

  $cmd = "/usr/sausalito/bin/i18nmail.pl";
  $cmd .= " -f ".escapeShellArg($thisUser["fullName"]." <" . $thisUser["name"] . "@" . $hostname . ">");
  $cmd .= " -l ".escapeShellArg($locale);
  $cmd .= " -s '[[base-pptp.pptp_notify_email_subject]]'";
  $cmd .= " $toArg"; 
//  $cmd .= " ".escapeShellArg($thisUser["fullName"]." <" .$thisUser["name"]."@".$hostname.">"); 
  $cmd .= " >/dev/null 2>&1 ";

  $pipe = popen($cmd, "w");
  fwrite($pipe, "[[base-pptp.pptp_notify_email_body,hostname=$hostname]]");
  pclose($pipe);
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

