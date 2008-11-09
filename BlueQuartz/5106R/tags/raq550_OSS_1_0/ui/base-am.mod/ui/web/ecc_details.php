<?
// $Id: ecc_details.php 259 2004-01-03 06:28:40Z shibuya $
// Author: Tim Hockin
// Copyright 2000, Cobalt Networks.  All rights reserved.

include_once("ServerScriptHelper.php");
include_once("base/am/am_detail.inc");
include_once("base/am/imagelib.inc");
include_once("./ecc.inc");

$serverScriptHelper = new ServerScriptHelper();
$cce = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-am");
$page = $factory->getPage();
$i18n = $serverScriptHelper->getI18n();

print($page->toHeaderHtml());

am_detail_block($factory, $cce, "ECC", "[[base-am.amECCDetails]]");

$ecc_status = ecc_status($cce);

$images_config = get_images_config();

$ecc_config = $images_config['am_ecc'];

$render_info = get_render_info($ecc_status, $ecc_config);

$name = "map1";

print image_map($name, $render_info);

print "<BR>";
// need to put random data in the URL otherwise Netscape caches the URL
print "<IMG border=0 usemap=\"#map1\" SRC=\"ecc_image.php?status=". rawurlencode(serialize($ecc_status)) . "&random=" . 
       time() . "\">";
print "<BR>";


$logButton = $factory->getButton("/base/am/ecc_logs.php", "viewEccLogs");
$back = $factory->getBackButton("/base/am/amStatus.php");
$composite = $factory->getCompositeFormField(array($logButton, $back));
$composite->setDelimiter("<IMG SRC=\"/libImage/spaceHolder.gif\" WIDTH=\"5\" HEIGHT=\"5\">");

// print it 
print("<BR>");
print($composite->toHtml());
print($page->toFooterHtml());

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
