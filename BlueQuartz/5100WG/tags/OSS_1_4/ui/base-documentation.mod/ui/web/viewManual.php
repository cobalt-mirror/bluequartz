<?
include ("ServerScriptHelper.php");
include ("ArrayPacker.php");
include ("uifc/ImageButton.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-documentation");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-documentation");

$page = $factory->getPage();
$scrollList = $factory->getScrollList("manualPdfList", array("pdfDescription", "pdfSize", "pdfAction"), array(0,1));
$scrollList->setEntryCountTags("[[base-documentation.pdfCountSingular]]", "[[base-documentation.pdfCountPlural]]");
$scrollList->setAlignments(array("left", "center", "center"));

$cce = $serverScriptHelper->getCceClient();

// find installed .pdf's
$baseDir = "/usr/sausalito/ui/web";
$docDir = "/base/documentation/";

$megabytes = $i18n->get("megabytes");

$system = $cce->getObject("System");
$possibleLocales = stringToArray($system["locales"]);
for ($j = 0; $j < count($possibleLocales); $j++) {
  $manfile = "$docDir" . "manual-" . $possibleLocales[$j] . ".pdf";

  // $desbugz .= "<H3>$manfile $baseDir$manfile</H3>";

  if (file_exists($baseDir . $manfile)) {
    // add table entry
    $desc_html = $factory->getTextField("", $i18n->get("pdfDesc".$possibleLocales[$j]), "r");
 
    $size = filesize($baseDir . $manfile)+1;
    $size = $size/104857.6; 
    $size = ceil($size)/10;
    
    $size_html = $factory->getTextField("", "$size $megabytes", "r");

    // $link = "<A HREF=\"$manfile\" TARGET=\"_top\" BORDER=0><IMG SRC=\"/libImage/visitWebsite.gif\" BORDER=0></A>";
    $linkButton = new ImageButton($page, "$manfile", "/libImage/visitWebsite.gif", "openPdf", "openPdf_help");
    $linkButton->setTarget('_top');

    $scrollList->addEntry( array($desc_html, $size_html, $linkButton), "", false );
  }
}

$acroreadAlt = $i18n->get("documentation_acrobat_help");

$downloadAcrobat = "<CENTER><P>";
$downloadAcrobat .= $i18n->get("documentation_acrobat");
$downloadAcrobat .= "<BR><A HREF=\"http://www.adobe.com/products/acrobat/alternate.html\" BORDER=0><IMG SRC=\"$docDir/getacro.gif\" BORDER=0 ALT=\"$acroreadAlt\"></A>";

print($page->toHeaderHtml());
print($scrollList->toHtml());
print($downloadAcrobat);
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

