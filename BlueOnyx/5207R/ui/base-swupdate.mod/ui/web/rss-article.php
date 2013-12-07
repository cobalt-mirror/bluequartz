<?php

// Author: Michael Stauber
// Copyright 2006-2013, Stauber Multimedia Design. All rights reserved.
// Copyright 2008-2013, Team BlueOnyx. All rights reserved.

include_once("ServerScriptHelper.php");
include_once("uifc/ImageButton.php");

$serverScriptHelper = new ServerScriptHelper();

// Only 'managePackage' should be here and we should have an article ID:
if ((!$serverScriptHelper->getAllowed('managePackage')) && (!$id)) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-yum");
$i18n = $serverScriptHelper->getI18n("base-yum");

// Location (URL) of the RSS feed:
$rsslocation = 'http://www.blueonyx.it/index.php?mact=CGFeedMaker,cntnt01,default,0&cntnt01feed=BlueOnyx-News&cntnt01showtemplate=false';

// Check if we are online:
if (areWeOnline($rsslocation)) {
    $online = "1";
}
else {
  header("location: /base/swupdate/rss-news.php");
  return;
}

// Start Output generation:
$page = $factory->getPage();
print($page->toHeaderHtml());

// Build the string from which we fetch the detailed news:
$webpage = 'http://www.blueonyx.it/index.php?mact=News,cntnt01,detail,0&cntnt01articleid=' . $id . '&cntnt01origid=54&cntnt01pagelimit=100000&cntnt01returnid=54';

// Read the data from the URL in:
$xcontent = get_data($webpage);

// Extract those parts from the page that we want:
$newsDetailTitle = extract_id($xcontent, 'NewsPostDetailTitle');
$newsDetailSummary = extract_id($xcontent, 'NewsPostDetailSummary');
$newsDetailContent = extract_id($xcontent, 'NewsPostDetailContent');

// Wrap news summary and content into SimpleText objects:
$simple_summary = $factory->getSimpleText($newsDetailSummary);
$simple_content = $factory->getSimpleText($newsDetailContent);

// Generate the Back button to return to the news listing:
$backButton = $factory->getBackButton("/base/swupdate/rss-news.php");

// Generate the HTML framework of the newspage and fill in the blanks:
?>

<TABLE BORDER="0" CELLSPACING="0" CELLPADDING="0" WIDTH="530">
  <TR>
    <TD COLSPAN="2"><IMG SRC="/libImage/splashPersonal.jpg" ALT="" BORDER="1"></TD>
  </TR>
  <TR>
    <TD VALIGN="TOP" ALIGN="CENTER" WIDTH="*"><br><H3><?php print($newsDetailTitle); ?></H3></TD>
  </TR>
  <TR>
    <TD COLSPAN="2"><?php print($simple_summary->toHtml()); ?></TD>
  </TR>
  <TR>
    <TD VALIGN="BOTTOM" COLSPAN="2">
    <?php print($simple_content->toHtml()); ?><BR></TD>
  </TR>
</TABLE>
<?php

// Out with the back button:
print($backButton->toHtml());

// Rest of the page output:
print($page->toFooterHtml());
$serverScriptHelper->destructor();

// The useful functions that we need:

function extract_id( $content, $id ) {
	// use mb_string if available
	if ( function_exists( 'mb_convert_encoding' ) )
		$content = mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8');
	$dom = new DOMDocument();
	$dom->loadHTML( $content );
	$dom->preserveWhiteSpace = false;
	$element = $dom->getElementById( $id );
	$innerHTML = innerHTML( $element );
	return( $innerHTML ); 
}

/**	 
 * Helper, returns the innerHTML of an element
 *
 * @param object DOMElement
 *
 * @return string one element's HTML content
 */

function innerHTML( $contentdiv ) {
	$r = '';
	$elements = $contentdiv->childNodes;
	foreach( $elements as $element ) { 
		if ( $element->nodeType == XML_TEXT_NODE ) {
			$text = $element->nodeValue;
			// IIRC the next line was for working around a
			// WordPress bug
			//$text = str_replace( '<', '&lt;', $text );
			$r .= $text;
		}	 
		// FIXME we should return comments as well
		elseif ( $element->nodeType == XML_COMMENT_NODE ) {
			$r .= '';
		}	 
		else {
			$r .= '<';
			$r .= $element->nodeName;
			if ( $element->hasAttributes() ) { 
				$attributes = $element->attributes;
				foreach ( $attributes as $attribute )
					$r .= " {$attribute->nodeName}='{$attribute->nodeValue}'" ;
			}	 
			$r .= '>';
			$r .= innerHTML( $element );
			$r .= "</{$element->nodeName}>";
		}	 
	}	 
	return $r;
}


function get_data($url) {
  $ch = curl_init();
  $timeout = 5;
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

function areWeOnline($domain) {
    // Check to see if we're online and if the desired URL is reachable.
    // Returns true, if URL is reachable, false if not

   // Initialize curl:
   $curlInit = curl_init($domain);
   curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
   curl_setopt($curlInit,CURLOPT_HEADER,true);
   curl_setopt($curlInit,CURLOPT_NOBODY,true);
   curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);
        
   // Get answer
   $response = curl_exec($curlInit);
        
    // Close curl:
   curl_close($curlInit);
        
    // Generate response:
   if ($response) return true;
       return false;
}

/*
Copyright (c) 2013 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2013 Team BlueOnyx, BLUEONYX.IT
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