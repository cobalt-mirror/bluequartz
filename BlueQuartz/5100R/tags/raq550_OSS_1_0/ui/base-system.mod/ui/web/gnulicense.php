<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: gnulicense.php 259 2004-01-03 06:28:40Z shibuya $

include("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

$stylist = $serverScriptHelper->getStylist();
$style = $stylist->getStyle("Page");

$factory = new HtmlComponentFactory($stylist, $serverScriptHelper->getI18n("base-system"), "");
$i18n = $serverScriptHelper->getI18n("base-system");
$page = $factory->getPage();
print($page->toHeaderHtml($style));

$block = $factory->getPagedBlock("gnuTitle");

print($block->toHtml());

?>

<TABLE BORDER="0" WIDTH="550">

<TR><TD WIDTH="550">
&nbsp;<P><? echo $i18n->get("gnuLicense"); ?><P>&nbsp;<P>
</TD></TR>

</TABLE>

<?php

$button = $factory->getBackButton("/base/system/credits.php");
print($button->toHtml());
print($page->toFooterHtml()); 
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
