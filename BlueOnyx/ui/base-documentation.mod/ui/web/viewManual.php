<?
/*  Page to display links to manuals with n locales in a standard way
 *  Will DeHaan, Eric Braswell
 *  
 *  How to use this page:
 *  In most cases you should link to this page from the menu xml file by
 *  specifying the url and the appropriate parameters in the url attribute, eg:
 *      url="/base/documentation/viewManual.php?url=myManual \
            &amp;ext=.pdf&amp;name=My%20Manual%20English"
 *
 *  Page Parameters:
 *  
 *  docDir     - Directory in which manual(s) reside. This is mainly for search 
 *               directory mode and compatibility. You can also specify the full
 *               path in url. The default in search directory and compatibility
 *               modes is /base/documentation
 *  searchDir  - If set, docDir will be searched for files with the extension
 *               $ext.
 *  url        - Base Url of the manual, eg mymanual. Can be an array if name is 
 *               also an array.
 *               If url is not specified it is assumed to be "manual" (ie in the 
 *               current directory. This is for compatibility.
 *  ext        - File extension of manual. The default is ".pdf". 
 *  name       - Name of the manual, either a base tag name if you are deriving 
 *               locales from CCE or a string eg "My Manual in English v 1.0". 
 *               This can be an i18n tag. Can be an array if url is also an array.
 *
 *  noLocale   - (boolean) Don't derive the locales, but assume they are part of
 *               the name. Use this if, for example, you need a listing of manuals
 *               that aren't locale-specific.
 *
 *  noi18n     - If set, don't interpolate manual names. Default is false (do
 *               interpolate). This is necessary if you want to pass literal 
 *               strings for the manual names.
 *
 *  i18nDomain - Use this i18n domain to interpolate $name when noLocale is not 
 *               set or is false. Ignored if noi18n is set. With i18nDomain set, 
 *               i18n tags for names will be constructed as:
 *                  $i18nDomain + "." + $name [+ "-" + <locale> ] + $ext
 *               This can be useful if you want to stick with the default names
 *               but your strings are defined in a different domain
 *
 *  title      - Title to put in pageBlock
 *
 *
 *  Copyright 2001 Sun Microsystems, Inc., All rights reserved.
 */

 
 

/* ***********  Utility Functions               */

 
/* Make sure a tag looks and smells like an i18n tag
   This is so you can pass in flq tags from xml files and not have them
   butchered by uifc
 */
function rectifyTag($tag) {   
    global $i18nDomain;
    if(!empty($i18nDomain)) {
        $domainPiece = $i18nDomain . ".";
    }
    return("[[$domainPiece" . preg_replace("/\[\[[_a-zA-Z0-9-]\]\]$/", "\\2", $tag) . "]]");
}

/*
    Get locales from CCE and construct the urls with them
    piece - a string in which to put the locale
            you must use a %s where you want it
    interpolate - if true, i18n interpolate the full piece
*/
function getLocales($piece="", $interpolate=false) {
    if(!strstr($piece, "%s")) {
        return(array($piece));
    }
    if($interpolate){ 
        global $i18n; 
    }
    
    global $possibleLocales;
    $locales = array();
    
    // Only get locales once
    if(!isset($possibleLocales)) {
        global $serverScriptHelper;
        $cce = $serverScriptHelper->getCceClient();
        $system = $cce->getObject("System");
        $possibleLocales = stringToArray($system["locales"]);
    }
    for ($j = 0; $j < count($possibleLocales); $j++) {
        
        $locales[$j] = sprintf($piece, $possibleLocales[$j]);
        if($interpolate) {
            $locales[$j] = $i18n->get(rectifyTag($locales[$j]));
        }
    }
    return($locales);
}

/* ***********  Page Start                      */

include_once ("ServerScriptHelper.php");
include_once ("ArrayPacker.php");
include_once ("uifc/ImageButton.php");
include_once ("System.php");


$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-documentation");
$factory = $serverScriptHelper->getHtmlComponentFactory("base-documentation");

$page = $factory->getPage();
print($page->toHeaderHtml());


if(empty($title)) {
    $title = "manualPdfList";    
}
$title = rectifyTag($title);

$scrollList = $factory->getScrollList($title, array("pdfDescription", 
					"pdfSize", "pdfAction"));
$scrollList->setEntryCountTags("[[base-documentation.pdfCountSingular]]", 
				"[[base-documentation.pdfCountPlural]]");
$scrollList->setAlignments(array("left", "center", "center"));

$scrollList->setSortEnabled(false);


$cfg = new System();

$megabytes = $i18n->get("megabytes");

// get base directory from ui.cfg (/usr/sausalito in most cases)
$baseDir = $cfg->getConfig("webDir"); 
unset($cfg);

$defaultDocDir = "/base/documentation/"; 


// determine if we are making assumptions for compatibility

if(!isset($url) && !isset($name) && !isset($searchDir) && !isset($docDir)) {
    $compat = true;
    $docDir =& $defaultDocDir;   
}


// Be specific about noi18n flag
if(!isset($noi18n)) {
    $noi18n = false;
}

// Default i18n Tag for display
if(!isset($name) && !$noi18n) {
    $name = "pdfDesc";  
}

// Also be specific about noLocale flag
if(!isset($noLocale)) {
    $noLocale = false;
}

// Default file extension
if(!isset($ext)) {
    $ext = ".pdf";
}


/*
    Build url for Search Directory mode
*/
if(isset($searchDir)){
    
    // Ignore locale stuff for both url and name
    $noLocale = true;
    $name = "";
    $noi18n = true;
        
    if(empty($docDir)) {
           $docDir =& $defaultDocDir;   
    }
    // Read in urls from directory
    if($dir = @opendir($baseDir . $docDir)) {
        while($file = readdir($dir)) {
            // filter on extension if specified & ignore hidden files/ . .. 
            if((@strstr($file, $ext) or empty($ext)) and 
                    (strpos($file, ".") === false or !strpos($file, ".") == 0)) {
                $url[] = $file;
            }
        }
    closedir($dir);
    }

    // throw away extension now
    $ext = "";
}


// Default base name for manual if we haven't set anything yet
if(!isset($url)) {
    $url = "manual";    
}

// If we are deriving locales for names, make a string for adding it
if(!$noLocale) {
    $insertLocale = "-%s";    
    $insertLocaleName = "%s"; //silly, but for compat    
}

// convert a string url to array for processing
if(!is_array($url)){
    $url = array($url);
}    
    
for($a = 0; $a < count($url); $a++) {
    $base = $docDir . $url[$a] . $insertLocale . $ext;
    
    // add locales for this manual
    // If $noLocale is set, the insertLocale piece won't contain "%s"
    // so getLocales will just return
    
    $localedFiles = getLocales($base);
    
    $files = array();
    $files = array_merge($files, $localedFiles);

    // Deal with displayed name(s)
    if(is_array($name)) {
        
        if(!$noLocale){
             // We are doing locales and $name is an array
             // so add a name for each locale of $url[$a]. Everything _should_
             // end up in the right order

            $names = array_merge($names, getLocales($name[$a] . $insertLocaleName, $noi18n ? false : true));
            
            
        } else {
            // no locales
            // One-to-one assignment of name to url
            if($noi18n) {
                // treat name as a literal string
                $names[$a] = $name[$a];
            } else {
                // interpolate name
                $names[$a] = $i18n->get(rectifyTag($name[$a]));
            }              
        }

    }else{
        
        if($noi18n) {
            // if we're not doing i18n, use file names
            $base = $url[$a] . $insertLocale . $ext;
        }else{
            // $name is the base for making the names for each url
            $base = $name . $insertLocaleName;
        }
        
        // get locales as appropriate, do i18n depends on noi18n flag
        $localedNames = getLocales($base, $noi18n ? false : true);
	$names = array();
        $names = array_merge($names, $localedNames);
    }
}  

for($i = 0; $i < count($files); $i++) {
    $manfile =& $files[$i];
    
    if (file_exists($baseDir . $manfile)) {
        // add table entry
        $desc_html = $factory->getTextField("", $names[$i], "r");
        
        $size = filesize($baseDir . $manfile)+1;
        $size = $size/104857.6; 
        $size = ceil($size)/10;
        
        $size_html = $factory->getTextField("", "$size $megabytes", "r");
        
        // $link = "<A HREF=\"$manfile\" TARGET=\"_top\" BORDER=0><IMG SRC=\"/libImage/visitWebsite.gif\" BORDER=0></A>";
        
        $linkButton = new ImageButton($page, "$manfile", 
                       "/libImage/visitWebsite.gif", "openPdf", "openPdf_help");
        $linkButton->setTarget('_top');
        
        $scrollList->addEntry( array($desc_html, $size_html, $linkButton), 
                                "", false );
    } else {
        error_log("viewManual: " . $files[$i] . " does not exist");   
    }
    
}

print($scrollList->toHtml());

$acroreadAlt = $i18n->get("documentation_acrobat_help");
?>
<CENTER><P>

<? print($i18n->get("documentation_acrobat")); ?>

<BR><A HREF="http://www.adobe.com/products/acrobat/alternate.html" BORDER=0>
<IMG SRC="/base/documentation/getacro.gif" BORDER=0 ALT="<? echo $acroreadAlt; ?>"></A>

</CENTER>

<?

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
