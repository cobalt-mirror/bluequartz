<?php
/**
  * Utility Library for Browser Compatiblity
  *
  * These functions are intended to simply dealing with browser 
  * compatibility issues. 
  *
  * $Id: browser.php 201 2003-07-18 19:11:07Z will $
  *
  * @version $Revision: 201 $
  * @copyright  Copyright 2001 Sun Microsystems, Inc. All Rights Reserved.
  * @access public
  * @module Browser
  * @modulegroup Utils
  */  

/** 
 * Output the headers to have the browser take the output and 
 * save it to a file called $filename of type $type 
 *
 * NOTE: This does NOT output a "Cache-Control: no-cache"
 *       header. Outputing this header will cause downloads
 *       to fail on IE over SSL.
 **/
function browser_headers_for_download($filename, $type = '') {
  // Attempt to determine if we are connected to an IE browser
  global $HTTP_USER_AGENT;
  $is_msie = strpos(strtoupper($HTTP_USER_AGENT), 'MSIE');
  $is_mac = strpos(strtoupper($HTTP_USER_AGENT), 'MAC');

  // DON'T CHANGE THESE MIMETYPES UNLESS YOU KNOW WHAT YOU'RE DOING!

  // as of Dec 5, 2001, these work for saving plain text files on:
  // Mac OS9 IE5, NS4.78
  // Max OSX IE5.1
  // Win IE5.5 IE6 NS4.78
  // All with and without SSL.

  if ($is_mac !== false) {
    // Macintosh IE and Netscape take a look at the plain text
    // and displays it in the browser instead of saving it.
    // Even returning text as application/octet-stream doesn't work.
    // So we have to throw an alternative mimetype so the browser will
    // prompt the user.
    switch ($type) {
      case 'text':
        $mimetype = 'application/text';
        $disposition = 'attachment';
        break;
      case 'tsv':
        $mimetype = 'text/tab-separated-values';
        $disposition = 'attachment';
        break;
      default:
        $mimetype = 'application/octet-stream';
        $disposition = 'attachment';
        break;
    }
  } else if ($is_msie !== false) {
    // This will be IE on Windows
    // When we send a Content-Disposition header, IE is supposed to show a 
    // Save As dialog and save the text to a file.
    // But it has a bug, and so it doesn't.
    // So we force it to think we're an octet-stream, and throw a weird 
    // Disposition to make it do the right thing.
    // See http://support.microsoft.com/support/kb/articles/Q267/9/91.ASP
    switch ($type) {
      case 'tsv':
        $mimetype = 'text/tab-separated-values';
        $disposition = 'attachment';
        break;
      default:
        $mimetype = 'application/download';
        $disposition = 'anything';
        break;
    }
  } else {
    // Netscape on Windows
    // It too doesn't show a Save As dialog box when it sees a text file.
    // So we have to lie and say it's an octet-stream.
    switch ($type) {
      case 'tsv':
        $mimetype = 'text/tab-separated-values';
        $disposition = 'attachment';
        break;
      default:
        $mimetype = 'application/octet-stream';
        $disposition = 'attachment';
        break;
    }
  }

  header("Content-Type: $mimetype; name=\"$filename\"");
  header("Content-Disposition: $disposition; filename=\"$filename\"");
}

/**
* Return a standardized comma-separated list of locales that the 
* browser will accept.
* If $locales is omitted, the Apache environment variable
* HTTP_ACCEPT_LANGUAGE is used.
*
* Browsers populate the Accept-Language header differently:
* IE 5 MacOS X:	en, eu;q=0.8, da;q=0.6, hr;q=0.4, fo;q=0.2
* IE 6 NT: en-us, ar-jo;q=0.8,sa;q=0.5,en-tt;q=0.3
* Mozilla 0.99 MacOS X: en-US, en;q=0.75, zh-TW;q=0.50, zh-CN;q=0.25
* OmniWeb 4.1 beta 1: en, de, fr, nl, it, ja, es, zh-cn, zh-tw
* Konqueror: en, en_US
* 
* This function returns a list like:
*  en, en_US, fr, zh_TW, zh_CN, da, ar_JO
*
* @param	string	$locales	Browser HTTP_ACCEPT_LANGUAGE string
*/
function getBrowserAcceptLocales($locales="") 
{
	if(empty($locales)) {
		$locales = $_SERVER["HTTP_ACCEPT_LANGUAGE"];
	}		
	$locales = str_replace(" ", "", $locales);
	// convert to array
	$locales = explode(",", $locales);
	for($i=0; $i < sizeof($locales); $i++) {
		// remove q= thing
		$locales[$i] = preg_replace("/;q=(\d)+(\.)?(\d)*/", "", $locales[$i]);		
		// uppercase country code
		$locales[$i] = preg_replace("/(.{1,2})(-|_)(.*)/e", "'\\1_' . strtoupper('\\3')", $locales[$i]);
	}
	 return(implode(",", $locales));
}


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

