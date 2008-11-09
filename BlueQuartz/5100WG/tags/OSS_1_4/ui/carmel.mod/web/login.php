<?    
// Author: Mike Waychison, Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: login.php 3 2003-07-17 15:19:15Z will $

include("I18n.php");
include("uifc/Stylist.php");

// determine the local to use..
$locale = $HTTP_ACCEPT_LANGUAGE;
if ($HTTP_ACCEPT_LANGUAGE == "") {
	include ("System.php");
	$system = new System();
	$locale = $system->getConfig("defaultLocale");
}
$i18n = new I18n("base-carmel", $locale);

$stylist = new Stylist();
$locale=ereg_replace("-","_",$locale);
$stylist->setResource("trueBlue",$locale);

$myStyle = $stylist->getStyle("Login");

$secure = "";
if ($HTTP_GET_VARS['secure']) { $secure = "CHECKED"; }

$hostname = `/bin/hostname`;

if(($charset=$i18n->getProperty("encoding","palette"))!="none")
	header("Content-type: text/html; charset=$charset");
?>

<HTML>
<HEAD>
<STYLE TYPE="text/css">
<!--
SMALL {<?php print($myStyle->toTextStyle("small")); ?>}
BIG { <?php print($myStyle->toTextStyle("login")); ?>}
-->
</STYLE>

<SCRIPT LANGUAGE="javascript">
<!--
function focuslogin() {
document.form.newLoginName.focus();
}
// -->
</SCRIPT>

<TITLE><?
        print $i18n->interpolate(
                "[[base-carmel.loginPageTitle,hostname=\"$hostname\"]]");
?></TITLE>
</HEAD>
<BODY BGCOLOR="#FFFFFF" onLoad="focuslogin()" onResize="if(navigator.appName == 'Netscape') location.reload()" LINK="#FFFFFF" VLINK="#FFFFFF">
<DIV ID="checkCss" STYLE="position:absolute"></DIV>
<SCRIPT LANGUAGE="javascript">
cssEnabled = false;
if(document.layers && document.checkCss != null)
        cssEnabled = true;
if(document.all && checkCss != null)
        cssEnabled = true;
if(document.getElementById && document.getElementById != null)
        cssEnabled = true;
</SCRIPT>
<CENTER>
<TABLE WIDTH="650" CELLSPACING="0" CELLPADDING="0" BORDER="0">
	<TR>
		<td valign="middle" align="right" colspan=1 background="/libImage/login_top.jpg" width="650" height="174">

<TABLE CELLSPACING=0 CELLPADDING=0 WIDTH="350" background="">
	<TR>
		<TD>
		<FONT STYLE="<? print($myStyle->toTextStyle()); ?>"><?php print($i18n->getHtml("login","",array("hostName" =>$hostname))); ?></FONT>
<BR><BR>
<FONT STYLE="<?php print($myStyle->toTextStyle("medium")); ?>">
<SCRIPT LANGUAGE="javascript"> <!--
// cookie check
cookieEnabled = false;
var cookie = "cookieSupport=true";
document.cookie = cookie;
if(document.cookie.indexOf(cookie) != -1)
  cookieEnabled = true;
// cookie check end

 if (!cookieEnabled) { 
  document.write("<FONT COLOR=\"<? print($myStyle->getProperty("color","error")); ?>\">\n<? print($i18n->getJs("loginMissingCookies")); ?>\n</FONT>"); 
 } else if(location.search.indexOf("expired=true") != -1) { 
 document.write("<SMALL><FONT COLOR=\"<? print($myStyle->getProperty("color","error")); ?>\">\n<?print($i18n->getJs("loginExpiredMessage")); ?>\n</FONT></SMALL>"); 
 } else if(location.search.indexOf("bye=true") != -1) { 
  document.write("\n<? print($i18n->getJs("loginByeMessage")); ?>\n"); 
 } else if(cssEnabled) {
        <?php if (!$authFailed) { ?> 
  document.write('\n<? print($i18n->getJs("loginOkMessage")); ?>\n');
        <?php } else { ?> 
  document.write("<FONT COLOR=\"<?print($myStyle->getProperty("color","error")); ?>\">\n<?php print($i18n->getJs("loginAuthFailed")); ?>\n</FONT>");
        <?php } ?> 
 } else { 
  document.write("<FONT COLOR=\"<? print($myStyle->getProperty("color","error")); ?>\">\n<? print($i18n->getJs("loginNoCssMessage")); ?>\n</FONT>"); 
 }
  // -->
                                </SCRIPT>
                                <NOSCRIPT>
                                        <FONT COLOR="<? print($myStyle->getProperty("color","error")); ?>">
                                                <? print($i18n->getHtml("loginNoJsMessage")); ?> </FONT>
                                </NOSCRIPT>
                        </FONT>
		</TD>
	</TR>
</TABLE>

		</td>
	</TR>
	<TR>
		<td valign="top" align="left" background="/libImage/login_bottom.jpg" WIDTH="650">
		<table cellspacing=0 cellpadding=0 border=0><tr><td background=""><img src="/libImage/Spacer.gif" width=300 height=174></td><td background="" valign="top" align="left">	
			<SCRIPT LANGUAGE="javascript">
if(cssEnabled && cookieEnabled){
        document.write(' \
<FORM ENCTYPE="application/x-www-form-urlencoded" METHOD="POST" NAME="form" ID="form"> \
<INPUT TYPE="HIDDEN" NAME="reuseWindow" VALUE="true"> \
<INPUT TYPE="HIDDEN" NAME="timeStamp"> \
<INPUT TYPE="HIDDEN" NAME="target" VALUE="<?php print(($target != "") ? $target : "/nav/cList.php?root=root") ?>"> \
<table border=0 cellspacing=0 cellspacing=0> \
<tr> \
        <td align="right"><big><? print $i18n->getHtml("loginPageUsername") ?></big></td> \
        <td><input type="text" name="newLoginName" size=15></td> \
</tr> \
<tr> \
        <td align="right"><BIG><? print $i18n->getHtml("loginPagePassword") ?></BIG></td> \
        <td><input type="password" name="newLoginPassword" size="15" onKeyPress="if(document.layers && event.which == 13 && document.form.onsubmit()) document.form.submit()"></td> \
</tr> \
<tr> \
        <td align="right"><BIG><? print $i18n->getHtml("loginPageSecurity") ?></BIG></td> \
        <TD><INPUT TYPE="CHECKBOX" NAME="secure" <?php echo $secure; ?>></TD> \
</tr> \
<tr> \
<td colspan=2 background="" ALIGN="right"> \
<TABLE BORDER="0" CELLPADDING="0" CELLSPACING="0"> \
<TR> \
<TD><A HREF="javascript: if(document.form.onsubmit()) document.form.submit();"><IMG BORDER="0" SRC="/libImage/buttonLeftDot.gif"></A></TD> \
<TD NOWRAP BGCOLOR="#000099"><A HREF="javascript: if(document.form.onsubmit()) document.form.submit();"><SMALL><? print $i18n->get("loginPageLogin") ?></SMALL></A></TD> \
<TD><A HREF="javascript: if(document.form.onsubmit()) document.form.submit(); "><IMG BORDER="0" SRC="/libImage/buttonRight.gif"></A></TD> \
</TR> \
</TABLE> \
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


			</SCRIPT>
		</td></tr></table>
		</td>
	</TR>
</TABLE>
</CENTER>
</BODY>
</HTML>
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

