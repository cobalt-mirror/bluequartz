<?
include("ServerScriptHelper.php");
include("./imapconnect.inc");
global $serverScriptHelper;
global $i18n;

$product = $serverScriptHelper->getProductCode();
$isMonterey = ereg("35[0-9][0-9]R", $product);

$stylist = $serverScriptHelper->getStylist();
$style = $stylist->getStyle("Page");
$fontTag = "<FONT STYLE=\"" . $style->toTextStyle() . "\">%s</FONT>";


// Figure out if IMAP is available or not
$everythingCool = true;
if ($isMonterey) {
  $msg = $i18n->get("splashWelcomeTo_monterey");
} else {
  $msg = $i18n->get("splashWelcomeTo") ;
}

$isServerUp = isServerUp();
if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
	$cce = $serverScriptHelper->getCceClient();
	$Email = $cce->getObject("System", array(), "Email");
	if (!$Email["enableImap"]) {
		$everythingCool = false;
		$msg = "<FONT color=\"#FF0000\">" . $i18n->get("webmailDownImapNotRunning") . "</FONT>";
		
	}
} else if ($isMonterey && !$isServerUp) {
  $everythingCool = false;
  $msg = "<FONT color=\"#FF0000\">" . $i18n->get("webmailDownImapNotRunning") . "</FONT>";
} else if ($isMonterey && $isServerUp) {
  connectToImap();
}

$lang=$i18n->getLocales();
header("Content-language: $lang[0]");
if(($encoding=$i18n->getProperty("encoding","palette"))!="none")
    header("Content-type: text/html; charset=$encoding");
?>
<!DOCTYPE html public "-//W3C//DTD HTML 4.0 Transitional//EN">
<HTML>
<HEAD>
<TITLE>Untitled</TITLE>

<SCRIPT language="javascript">
function loadLogin() {
	// declare all the variables so that they are just used in this function
	// and get garbage collected when they go out of scope
	var height, width, windowFeatures, webmailmain;

	if ((self.innerHeight + 100) < 480)
		height = 480;
	else
		height = self.innerHeight + 100;

	if (self.outerWidth < 750)
		width = 750;
	else
		width = self.outerWidth;

	windowFeatures = "menubar=yes,scrollbars=yes,status=yes,hotkeys=yes,toolbar=yes,location=yes,resizable=yes,personalbar=yes,width=" + width + ",height=" + height;

        window.open("/nav/cList.php?root=webmail_root&commFrame=/base/webmail/genfolders.php", "webmailmain", windowFeatures);

}
</SCRIPT>
</HEAD>
<BODY leftmargin="0" topmargin="0" bottommargin="0" marginheight="0" marginwidth="0" rightmargin="0" bgcolor="White" <? if ($everythingCool) print "onload=\"setTimeout('loadLogin()',10);return true\""?>>

<BR>

<CENTER>
<TABLE BORDER="0" CELLPADDING="5" WIDTH="95%">
  <TR>
    <TD><? print printf($fontTag, $msg);  ?> &nbsp;</TD>
    <TD ALIGN="right"><?printf($fontTag, $i18n->get("version")); ?></TD>
  </TR>
</TABLE>
</CENTER>







</BODY>
</HTML>
<?
    $serverScriptHelper->destructor();

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

