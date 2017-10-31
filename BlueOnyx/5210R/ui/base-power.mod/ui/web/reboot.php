<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: reboot.php

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

//Only users with 'serverPower' capability should be here
if (!$serverScriptHelper->getAllowed('serverPower')) {
  header("location: /error/forbidden.html");
  return;
}

$factory = $serverScriptHelper->getHtmlComponentFactory("base-power", "/base/power/reboot.php");
$i18n = $serverScriptHelper->getI18n("base-power");
$page = $factory->getPage();

$stylist = $serverScriptHelper->getStylist();
$style = $stylist->getStyle("Page");
$fontTag = "<FONT STYLE=\"" . $style->toTextStyle() . "\">%s</FONT>";

//create the button
$button = $factory->getButton("javascript: confirmReboot()", "reboot");

//confirmation string
$confirm =  $i18n->get("askRebootConfirmation");

//create the script
$rebootScript = <<<HERE
<SCRIPT LANGUAGE="javascript">
function confirmReboot()
{
  if (confirm("$confirm"))
  {
    location = "/base/power/rebootHandler.php"
  }
}
</SCRIPT>
HERE;


//create the page
print($page->toHeaderHtml());
//  print $block->toHtml();

//setup table
print '<TABLE WIDTH="550"><TR><TD>';
//rebooting title
print '<H1>';
printf($fontTag, $i18n->get("reboot_title"));
print '</H1>';

//print reboot message
$rebootmsg = $i18n->getJs("rebootMessage");
printf($fontTag, $rebootmsg);

//print reboot button
print($rebootScript);
print "<BR><BR><CENTER>";
print($button->toHtml()); 
print "</CENTER>";


//shutdown title
print '<BR>';
print '<BR>';
print '<BR>';
print '<H1>';
printf($fontTag, $i18n->get("shutdown_title"));
print '</H1>';
?>

        <?php printf($fontTag, $i18n->get("shutdownMessage")); ?>
<P>
<TABLE WIDTH="100%">
	<TR>
		<TD>
<?php printf($fontTag, $i18n->get("shutdownInstructions")); ?>
		</TD>
	</TR>
</TABLE>

		</TD>
	</TR>
</TABLE>

<?
print($page->toFooterHtml());
$serverScriptHelper->destructor();
exit;


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
