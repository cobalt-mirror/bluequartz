<?php
/**
 * Handle login by authorizing through CCE and setting session and id cookies
 *
 * $Id: loginHandler.php 1050 2008-01-23 11:45:43Z mstauber $
 *
 * @author Kevin K.M. Chiu, Eric Braswell
 * @copyright Copyright 2001 Sun Microsystems, Inc. All Rights Reserved.
 * 
 * @param newLoginName      string  The name of the user to authenticate
 * @param newLoginPassword  string  The password for the user
 *
 * @param target            string  The URL to go to if the login succeeds. 
 *                                  For security reasons, it must start with 
 *                                  a slash "/"
 * @param fallback          string  (Optional) The URL to fall back to if the 
 *                                  login fails.If not supplied, the page will 
 *                                  redirect back to the calling page.                     
 * @param reuseWindow       string  (Optional) If true, the target/fallback is 
 *                                  launched in the current window. If false,the
 *                                  target/fallback is launched in a new window.
 */

include_once("CceClient.php");
include_once("I18n.php");

// connect to CCE
$cceClient = new CceClient();
if(!$cceClient->connect()) {
  $locale = $HTTP_ACCEPT_LANGUAGE;
  if($HTTP_ACCEPT_LANGUAGE == "") {
    include_once("System.php");
    $system = new System();
    $locale = $system->getConfig("defaultLocale");
  }
  $i18n = new I18n("palette", $locale);

  $cceDown = "<CENTER><BR><BR><BR><BR><FONT COLOR=\"#990000\">" 
               . $i18n->get("cceDown") . "</FONT></CENTER>";
  printPage("",$cceDown); 
  error_log("loginHandler.php: $cceDown");
  exit;
}

// check to see if the user is already logged in as another
// user:
// $HTTP_COOKIE_VARS["loginName"] $HTTP_COOKIE_VARS["sessionId"]
if ($HTTP_COOKIE_VARS["loginName"] && $HTTP_COOKIE_VARS["sessionId"])
{
  // release the old session id
  $cceClient->authkey($HTTP_COOKIE_VARS["loginName"],
    $HTTP_COOKIE_VARS["sessionId"]);
  $cceClient->endkey(); # release the session id
  $cceClient = new CceClient();
  $cceClient->connect();
}

// get session ID
$sessionId = $cceClient->auth($newLoginName, $newLoginPassword);

$system = $cceClient->getObject('System');

if ( ! $system['isLicenseAccepted'] ) {
    $target = $fallback = '/nav/flow.php?root=base_wizardLicense';
}
// auth failed?
if($sessionId == "") {
  // figure out the next step
  $secure = "";
  if ($HTTP_POST_VARS["secure"]) {
	$secure="&secure=1";
  }
  $nextStep = ($fallback != "") ? "location.replace('$fallback')" : "location.replace('/login.php?authFailed=true$secure' )";
  printPage($nextStep);
  exit;
}

// send cookie that expires at end of browser session
setcookie("loginName", $newLoginName);
setcookie("sessionId", $sessionId);

// only use URLs of this machine
if(substr($target, 0, 1) != "/") {
  error_log("loginHandler: Invalid target: $target");
  exit();
}

/* Normal Login: Re-use window or launch in new one */
if($reuseWindow){
    $onLoad = "location.replace('$target')";
} else {   
    $onLoad = "init()";
    $head ="<SCRIPT LANGUAGE=\"javascript\">
            function init() {
              if(navigator.appName.indexOf(\"Microsoft\") != -1)
                // IE need to use a unique window name here because it throws access denied
                // error when window name is reused
                // we cannot call focus() here because IE gives paranoid access denied
                window.open(\"$target\", (new Date()).getTime(), \"menubar=yes,resizable=yes\");
              else {
                var windowReference = window.open(\"$target\", \"launchedWindow\", \"menubar=yes,resizable=yes\");
                windowReference.focus();
              }
            
              location.replace(\"/loggedIn.php\");
            }
            </SCRIPT>";
}
printPage($onLoad, "", $head);

/**
 * Print out a basic page with appropriate options
 *
 * @param string $onLoad Javascript to call when page loads
 * @param string $body Contents of the body portion of the page
 */
function printPage($onLoad="", $body="", $head="") {
    if(!empty($onLoad)) {
        $onLoad = sprintf("onLoad=\"%s\"", $onLoad);        
    }
    header("cache-control: no-cache");    
    ?>
    <HTML>
        <HEAD>
            <META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
            <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
            <?php print($head); ?>
        </HEAD>
        <BODY <?php print($onLoad); ?>>
           <?php print($body); ?>
        </BODY>
        <HEAD> <!-- convince IE really not to cache -->
            <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
        </HEAD>
    </HTML>
    <?php
}

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
