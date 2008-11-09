<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: loginHandler.php 201 2003-07-18 19:11:07Z will $

// description:
// This handler handles login tasks to the system through CCE authentication
// and set cookies to the browser appropriately.
//
// usage:
// The configurable variables are:
// newLoginName - the login name to CCE
// newLoginPassword - the login password to CCE
// timeStamp - the time in number of seconds since Epoch on the browser machine
// target - the URL to go to if login succeed. For security reasons, it must
//     start with a slash "/"
// fallback - the URL to fallback to if login fails. Optional. If not supplied,
//     goes back to where the handler is called from by default
// reuseWindow - if true, no new window is launched. Optional.
// windowWidth - the width of the new window.  Optional.
// windowHeight - the height of the new window.  Optional.

include("CceClient.php");
include("I18n.php");

// connect to CCE
$cceClient = new CceClient();
if(!$cceClient->connect()) {
  $locale = $HTTP_ACCEPT_LANGUAGE;
  if($HTTP_ACCEPT_LANGUAGE == "") {
    include ("System.php");
    $system = new System();
    $locale = $system->getConfig("defaultLocale");
  }
  $i18n = new I18n("palette", $locale);

  $cceDown = $i18n->get("cceDown");

  print("
<html>

	<head>
		<meta name=\"Copyright\" value=\"Copyright (C) 2002, Sun Microsystems, Inc.  All rights reserved.\">
		<meta http-equiv=\"content-type\" content=\"text/html;charset=ISO-8859-1\">
	</head>

	<body bgcolor=\"#eaf2ff\">
		<div align=\"center\">
			<br>
			<br>
			<br>
			<br>
			<br>
			<table width=\"433\" border=\"0\" cellspacing=\"0\" cellpadding=\"0\">
				<tr>
					<td width=\"92\"><img src=\"/libImage/errors/Alert-Error-Top-Left.gif\" alt=\"\" width=\"92\" height=\"25\" border=\"0\"></td>
					<td width=\"321\" background=\"/libImage/errors/Alert-Top.gif\"><img src=\"/libImage/errors/empty.gif\" alt=\"\" width=\"10\" height=\"10\" border=\"0\"></td>
					<td width=\"20\"><img src=\"/libImage/errors/Alert-Top-Right.gif\" alt=\"\" width=\"20\" height=\"25\" border=\"0\"></td>
				</tr>
				<tr>
					<td valign=\"top\" width=\"92\" background=\"/libImage/errors/Alert-Left.gif\"><img src=\"/libImage/errors/Alert-Error-Left-Top.gif\" alt=\"\" width=\"92\" height=\"53\" border=\"0\"></td>
					<td valign=\"top\" bgcolor=\"#f4f8ff\" width=\"321\">
						<div style=\"font-family:Arial,Helvetica,sans-serif; font-size:12px; font-weight:bold; margin-left: 12px\">
							$cceDown</div>
					</td>
					<td rowspan=\"2\" width=\"20\" background=\"/libImage/errors/Alert-Right.gif\"><img src=\"/libImage/errors/empty.gif\" alt=\"\" width=\"9\" height=\"127\" border=\"0\"></td>
				</tr>
				<tr>
					<td valign=\"bottom\" width=\"92\" background=\"/libImage/errors/Alert-Left.gif\"><img src=\"/libImage/errors/Alert-Left-Bottom.gif\" alt=\"\" width=\"92\" height=\"15\" border=\"0\"></td>
					<td align=\"right\" bgcolor=\"#f4f8ff\" width=\"321\"><img src=\"/libImage/errors/empty.gif\" alt=\"\" width=\"10\" height=\"10\" border=\"0\"></td>
				</tr>
				<tr>
					<td width=\"92\"><img src=\"/libImage/errors/Alert-Bottom-Left.gif\" alt=\"\" width=\"92\" height=\"25\" border=\"0\"></td>
					<td width=\"321\" background=\"/libImage/errors/Alert-Bottom.gif\"><img src=\"/libImage/errors/empty.gif\" alt=\"\" width=\"10\" height=\"10\" border=\"0\"></td>
					<td width=\"20\"><img src=\"/libImage/errors/Alert-Bottom-Right.gif\" alt=\"\" width=\"20\" height=\"25\" border=\"0\"></td>
				</tr>
			</table>
		</div>
	</body>

</html>");


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

// phpinfo();

// bye
// $cceClient->bye();

// auth failed?
if($sessionId == "") {
  // figure out the next step
  $secure = "";
  if ($HTTP_POST_VARS["secure"]) {
	$secure="&secure=1";
  }
  $nextStep = ($fallback != "") ? "location.replace('$fallback')" : "location.replace('/login.php?authFailed=true$secure' )";

  header("cache-control: no-cache");
  print("
<HTML>
  <HEAD>
    <META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
  <BODY onLoad=\"$nextStep\"></BODY>
  <HEAD>
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
</HTML>
");

  exit;
}

// expire in 2 hours
$expirationTime = $timeStamp+7200;

// send cookie
setcookie("loginName", $newLoginName, "$expirationTime");
setcookie("sessionId", $sessionId, "$expirationTime");

// only use URLs of this machine
if(substr($target, 0, 1) != "/")
  exit();

header("cache-control: no-cache");

if($reuseWindow)
  print "
<HTML>
  <HEAD>
    <META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
  <BODY onLoad=\"location.replace('$target')\"></BODY>
  <HEAD>
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
</HTML>
";
else{
  if($windowWidth && $windowHeight){
    $widthAndHeight="width=$windowWidth,height=$windowHeight,";
  }else{
    $widthAndHeight="";
  }

  print "
<HTML>
<HEAD>
<META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
<SCRIPT LANGUAGE=\"javascript\">
function init() {
  if(navigator.appName.indexOf(\"Microsoft\") != -1)
    // IE need to use a unique window name here because it throws access denied
    // error when window name is reused
    // we cannot call focus() here because IE gives paranoid access denied
    window.open(\"$target\", (new Date()).getTime(), \"".$widthAndHeight."menubar=yes,resizable=yes\");
  else {
    var windowReference = window.open(\"$target\", \"launchedWindow\", \"".$widthAndHeight."menubar=yes,resizable=yes\");
    windowReference.focus();
  }


  location.replace(\"/loggedIn.php\");
}

</SCRIPT>
</HEAD>
<BODY onLoad=\"init()\"></BODY>
<HEAD>
<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
</HEAD>
</HTML>
";
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

