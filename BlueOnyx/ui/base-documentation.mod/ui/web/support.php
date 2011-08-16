<?php
// Copyright 2001, Sun Microsystems, Inc.  All rights reserved.
// $Id: support.php 1136 2008-06-05 01:48:04Z mstauber $

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$factory = $serverScriptHelper->getHtmlComponentFactory("base-documentation", "");
$i18n = $serverScriptHelper->getI18n("base-documentation");
$page = $factory->getPage();
print($page->toHeaderHtml());

// Netscape doesn't honor body text styles in tables
$fontTag = "<FONT STYLE=\"font-family:Arial,Helvetica,sans-serif;font-size:12px;\">%s</FONT>";
$smallFontTag = "<FONT STYLE=\"font-family:Arial,Helvetica,sans-serif;font-size:12px;\">%s</FONT>";
?>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="5" WIDTH="530">
  <TR>
    <TD COLSPAN="2"><IMG SRC="/libImage/splashPersonal.jpg" ALT="" BORDER="1"></TD>
  </TR>
  <TR>
    <TD VALIGN="TOP" ALIGN="CENTER" WIDTH="*"><H1><?php printf($fontTag, $i18n->get("support")); ?></H1></TD>
  </TR>
  <TR>
    <TD VALIGN="BOTTOM" COLSPAN="2">
    <BR><BR><?php printf($smallFontTag, $i18n->get('[[base-documentation.supportText]]')); ?><BR></TD>
  </TR>
</TABLE>

<?php print($page->toFooterHtml()); ?>
<?php $serverScriptHelper->destructor();

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
