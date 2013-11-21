<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: license.php 1028 2007-06-25 16:57:31Z shibuya $

include_once("ServerScriptHelper.php");
include_once("Product.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$product = new Product( $cceClient );
$factory = $serverScriptHelper->getHtmlComponentFactory("base-wizard", "/base/wizard/licenseHandler.php");
$i18n = $serverScriptHelper->getI18n("base-wizard");
$page = $factory->getPage();
$page->setOnLoad('top.code.flow_setPageLoaded(true);');

$next = ( $product->isRaq() ) ? "base_wizardOnepage" : "base_wizardAdmin";

$accept = $factory->getButton("javascript: top.code._flow_showNavigation=1;document.form.accept.value='accept';top.code.flow_goNext()", "accept");
$decline = $factory->getButton("javascript: top.code._flow_showNavigation=1;document.form.accept.value='decline';top.code.flow_goNext()", "decline");

print($page->toHeaderHtml()); 
?>

<SCRIPT LANGUAGE="javascript">
// setup wizard will not expire for a whole day
if(top.code.session_keepAlive) {
    top.code.session_keepAlive(24*60);
}

// A precaution, and IE 5.1.2 for MacOSX doesn't
// see the method if you don't refer to it somehow first
if(top.code.flow_showNavigation) {
    top.code.flow_showNavigation(false);
}

function flow_getNextItemId() {
	return "<?php print($next); ?>";
}
</SCRIPT>

<INPUT TYPE="HIDDEN" NAME="accept" VALUE="0">

<TABLE WIDTH="75%">
<TR>
	<TD WIDTH="100%" COLSPAN="2">
		<B><?php print($i18n->get("licenseClick")); ?></B>
		<HR ALIGN="center" NOSHADE SIZE="1">
	</TD>
</TR>
<TR>
	<TD COLSPAN="2">
		<?php print($i18n->get("license")); ?>
	</TD>
</TR>
<TR>
	<TD COLSPAN="2">
		<BR>
	</TD>
</TR>
<TR>
    <TD ALIGN="right">
        <?php print $accept->toHtml(); ?>
    </TD>
    <TD ALIGN="left">
        <?php print $decline->toHtml(); ?>
    </TD>
</TR>
</TABLE>

<?php print($page->toFooterHtml()); ?>
<?php
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
