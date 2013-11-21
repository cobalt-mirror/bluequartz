<?php
// Author: Kevin K.M. Chiu
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: splashSoftware.php 1136 2008-06-05 01:48:04Z mstauber $

include_once("ServerScriptHelper.php");
include_once("System.php");

$serverScriptHelper = new ServerScriptHelper();

$stylist = $serverScriptHelper->getStylist();
$style = $stylist->getStyle("Page");
$style->setProperty("backgroundColor", "", "#FFFFFF");
$style->setProperty("backgroundImage", "", "");
$stylist->setStyle($style);

$splash = $stylist->getStyle("splash");
$splashImage = $splash->getProperty("swupdate");

if(empty($splashImage)) {
	$splashImage = "/libImage/splashUpdates.jpg";
}

# the following way of finding product specific image is not the cleanest, but
# considering previous work and backward compatibility, this is the way to go

# find out product specific image name
$productImageName = "product_".basename($splashImage);

# use product specific image if it exists
$system = new System();
$imageDir = $system->getConfig("imageDir");
if(file_exists("$imageDir/$productImageName"))
  $splashImage = dirname($splashImage)."/".$productImageName;


$factory = new HtmlComponentFactory($stylist, $serverScriptHelper->getI18n("base-swupdate"), "");
$i18n = $serverScriptHelper->getI18n("base-swupdate");
$page = $factory->getPage();
print($page->toHeaderHtml($style)); 

// Netscape doesn't honor body text styles in tables
$fontTag = "<FONT STYLE=\"" . $style->toTextStyle() . "\">%s</FONT>";

?>

<TABLE BORDER="0" CELLPADDING="5" WIDTH="530">
  <TR>
    <TD COLSPAN="2"><IMG SRC="<?php echo $splashImage; ?>" ALT=""></TD>
  </TR>
  <TR>
    <TD ALIGN="LEFT" VALIGN="TOP" WIDTH="60%"><?php printf($fontTag, $i18n->get("software_help")); ?></TD>
    <TD WIDTH="*"></TD>
  </TR>
</TABLE>

<?php print($page->toFooterHtml());  ?>
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
