<?php
// Author: Mike Waychison, Kevin K.M. Chiu, Michael Stauber
// $Id: login.php

// Prevent cross site scripting attacks (suggested by Ken Marcus):
if (preg_match('/iframe/', $target)) {
    $target = "";
}
if (preg_match('/alert/', $target)) {
    $target = "";
}

include_once("I18n.php");
include_once("uifc/Stylist.php");
include_once("BXBrowserLocale.php");

// Detect the browser locale to see if it is supported.
// If not, fall back to 'en_US':
$locale = BXBrowserLocale::prefered_language();

// check to accept license?
include_once("CceClient.php");
$cceClient = new CceClient();
if(!$cceClient->connect()) {

  $i18n = new I18n("palette", $locale);

  $cceDown = "<div style=\"text-align: center;\"><br><br><br><br><span style=\"color: #990000;\">"
               . $i18n->get("cceDown") . "</span></div>";
  echo "$cceDown";
  error_log("loginHandler.php: $cceDown");
  exit;
}

$system = $cceClient->getObject('System');
if ( ! $system['isLicenseAccepted'] ) {
	header("Location: /intro.html");
	exit;
}

// determine the local to use..
$i18n = new I18n("base-alpine", $locale);

$stylist = new Stylist();

$stylist->setResource("BlueOnyx",$locale);

$myStyle = $stylist->getStyle("Login");

$secure = "checked";
if ($HTTP_GET_VARS['secure']) { $secure = "checked"; }

preg_match("/^([^:]+)/", $HTTP_SERVER_VARS['HTTP_HOST'], $matches);
$hostname = $matches[0];

header("Content-type: text/html; charset=UTF-8");
?>

<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<style type="text/css">
<!--
small {<?php print($myStyle->toTextStyle("small")); ?>}
big { <?php print($myStyle->toTextStyle("login")); ?>}
-->
</style>

<script language="JavaScript" type="text/javascript">
<!--
 //<![CDATA[
function focuslogin() {
document.form.newLoginName.focus();
}
//]]>
// -->
</script>

<title><?php
        print $i18n->interpolate(
                "[[base-alpine.loginPageTitle,hostname=\"$hostname\"]]");
?></title>
</head>
<body bgcolor="#FFFFFF" onload="focuslogin()" link="#FFFFFF" vlink="#FFFFFF">
<div id="checkCss" style="position:absolute"></div>
<script language="JavaScript" type="text/javascript">
<!--
 //<![CDATA[
cssEnabled = false;
if(document.layers && document.checkCss != null)
        cssEnabled = true;
if(document.all && checkCss != null)
        cssEnabled = true;
if(document.getElementById && document.getElementById != null)
        cssEnabled = true;
//]]>
// -->
</script>
<center>
<table width="650" cellspacing="0" cellpadding="0" border="0">
	<tr>
		<td valign="middle" align="right" colspan="1" style="background-image: url(/libImage/login_top.jpg);" width="650" height="174">

<table width="350">
	<tr>
		<td align="left">
		<span style="<?php print($myStyle->toTextStyle()); ?>"><?php print($i18n->getHtml("login","",array("hostname" =>$hostname))); ?></span>
<br /><br />
<div style="<?php print($myStyle->toTextStyle("medium")); ?>">
<script language="JavaScript" type="text/javascript">
<!--
//<![CDATA[
// cookie check
cookieEnabled = false;
var cookie = "cookieSupport=true";
document.cookie = cookie;
if(document.cookie.indexOf(cookie) != -1)
  cookieEnabled = true;
// cookie check end

 if (!cookieEnabled) { 
  document.write("<span style=\"<?php print($myStyle->getProperty("color","error")); ?>;\">\n<?php print($i18n->getJs("loginMissingCookies")); ?>\n</span>"); 
 } else if(location.search.indexOf("expired=true") != -1) { 
 document.write("<span style=\"<?php print($myStyle->getProperty("color","error")); ?>;\">\n<?php print($i18n->getJs("loginExpiredMessage")); ?>\n</span>"); 
 } else if(location.search.indexOf("bye=true") != -1) { 
  document.write("\n<?php print($i18n->getJs("loginByeMessage")); ?>\n"); 
 } else if(cssEnabled) {
        <?php if (!$authFailed) { ?> 
  document.write('\n<?php print($i18n->getJs("loginOkMessage")); ?>\n');
        <?php } else { ?> 
  document.write("<span style=\"<?php print($myStyle->getProperty("color","error")); ?>;\">\n<?php print($i18n->getJs("loginAuthFailed")); ?>\n</span>");
        <?php } ?> 
 } else { 
  document.write("<span style=\"<?php print($myStyle->getProperty("color","error")); ?>;\">\n<?php print($i18n->getJs("loginNoCssMessage")); ?>\n</span>"); 
 }
//]]>
// -->
                                </script>
                                <noscript>
                                        <span style="<?php print($myStyle->getProperty("color","error")); ?>;">
                                                <?php print($i18n->getHtml("loginNoJsMessage")); ?> </span>
                                </noscript>
                        </div>
		</td>
	</tr>
</table>

		</td>
	</tr>
	<tr>
		<td valign="top" align="left" style="background-image: url(/libImage/login_bottom.jpg);" width="650">
		<table cellspacing="0" cellpadding="0" border="0"><tr><td><img src="/libImage/Spacer.gif" width="300" height="374" alt="" /></td><td valign="top" align="left">	
			<script language="JavaScript" type="text/javascript">
			<!--
			//<![CDATA[

if(cssEnabled && cookieEnabled){
        document.write(' \
<form enctype="application/x-www-form-urlencoded" method="post" name="form" id="form"> \
<input type="hidden" name="reuseWindow" value="true"> \
<input type="hidden" name="timeStamp"> \
<input type="hidden" name="target" value="<?php print(($target != "") ? $target : "/redirector.php") ?>"> \
<table border=0 cellspacing=0 cellspacing=0> \
<tr> \
        <td align="right"><big><?php print $i18n->getHtml("loginPageUsername") ?></big></td> \
        <td><input type="text" name="newLoginName" size=15></td> \
</tr> \
<tr> \
        <td align="right"><big><?php print $i18n->getHtml("loginPagePassword") ?></big></td> \
        <td><input type="password" name="newLoginPassword" size="15" onKeyPress="if(document.layers && event.which == 13 && document.form.onsubmit()) document.form.submit()"></td> \
</tr> \
<tr> \
        <td align="right"><big><?php print $i18n->getHtml("loginPageSecurity") ?></big></td> \
        <TD><INPUT TYPE="CHECKBOX" NAME="secure" <?php echo $secure; ?>></TD> \
</tr> \
<tr> \
<td colspan=2 background="" align="right"> \
<table border="0" cellpadding="0" cellspacing="0"> \
<tr> \
<td><a href="javascript: if(document.form.onsubmit()) document.form.submit();"><img border="0" src="/libImage/buttonLeftDot.gif"></a></td> \
<td nowrap bgcolor="#000099"><a href="javascript: if(document.form.onsubmit()) document.form.submit();"><small><?php print $i18n->get("loginPageLogin") ?></small></a></td> \
<td><a href="javascript: if(document.form.onsubmit()) document.form.submit(); "><img border="0" src="/libImage/buttonRight.gif"></a></td> \
</tr> \
</table> \
</td></tr></table> \
</form> \
 '); 
  if (!document.form && document.getElementById) 
  document.form = document.getElementById("form"); 
  document.form.onsubmit = _form_onSubmit; 
 } 
 
 function _form_onSubmit(e) { 
  this.timeStamp.value = (new Date()).getTime(); 
  if(this.secure.checked)
  this.action = 'https://'+location.hostname+':81/loginHandler.php'; 
  else 
  this.action = 'http://'+location.hostname+':444/loginHandler.php';
  return true 
 }
//]]>
// -->
			</script>
		</td></tr></table>
		</td>
	</tr>
</table>
</center>
</body>
</html>

<!--
# 
# Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
# Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
# Copyright (c) 2003 Sun Microsystems, Inc. 
# All Rights Reserved.
# 
# 1. Redistributions of source code must retain the above copyright 
#   notice, this list of conditions and the following disclaimer.
# 
# 2. Redistributions in binary form must reproduce the above copyright 
#   notice, this list of conditions and the following disclaimer in 
#   the documentation and/or other materials provided with the 
#   distribution.
# 
# 3. Neither the name of the copyright holder nor the names of its 
#   contributors may be used to endorse or promote products derived 
#   from this software without specific prior written permission.
# 
# THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
# "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
# LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
# FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
# COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
# INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
# BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
# LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
# CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
# LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
# ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
# POSSIBILITY OF SUCH DAMAGE.
# 
# You acknowledge that this software is not designed or intended for 
# use in the design, construction, operation or maintenance of any 
# nuclear facility.
# 
-->