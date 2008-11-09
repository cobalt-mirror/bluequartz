<?
/**
  * Utility Library for Browser Compatiblity
  *
  * These functions are intended to simply dealing with browser 
  * compatibility issues. 
  *
  * $Id: browser.php 259 2004-01-03 06:28:40Z shibuya $
  *
  * @author  James Cheng
  * @version $Revision: 259 $
  * @copyright  Copyright 2001 Sun Microsystems, Inc. All Rights Reserved.
  * @access public
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
    default:
      $mimetype = 'application/octet-stream';
      $disposition = 'attachment';
    }
  } else if ($is_msie !== false) {
    // This will be IE on Windows
    // When we send a Content-Disposition header, IE is supposed to show a 
    // Save As dialog and save the text to a file.
    // But it has a bug, and so it doesn't.
    // So we force it to think we're an octet-stream, and throw a weird 
    // Disposition to make it do the right thing.
    // See http://support.microsoft.com/support/kb/articles/Q267/9/91.ASP
    $mimetype = 'application/download';
    $disposition = 'anything';
  } else {
    // Netscape on Windows
    // It too doesn't show a Save As dialog box when it sees a text file.
    // So we have to lie and say it's an octet-stream.
    switch ($type) {
    default:
      $mimetype = 'application/octet-stream';
      $disposition = 'attachment';
      break;
    }
  }

  header("Content-Type: $mimetype; name=\"$filename\"");
  header("Content-Disposition: $disposition; filename=\"$filename\"");
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
