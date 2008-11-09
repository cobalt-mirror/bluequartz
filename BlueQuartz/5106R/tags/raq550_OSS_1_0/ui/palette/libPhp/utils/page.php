<?
/**
  * Utility Library for Page Functions
  *
  * These functions are intended to output standard pages or page elements. 
  * They are designed to be small and fast and use procedural code for speed.
  *
  * $Id: page.php,v 1.1.2.1 2001/12/06 02:04:47 jcheng Exp $
  *
  * @author  Eric Braswell
  * @version $Revision: 1.1.2.1 $
  * @copyright  Copyright 2001 Sun Microsystems, Inc. All Rights Reserved.
  * @access public
  */  

/**
 * Print out a basic page with appropriate options
 *
 * @param string $onLoad    Javascript to call when page loads
 * @param string $body        Contents of the body portion of the page
 * @param string $head        Extra stuff to put in the head portion
 */
function page_printBasic($onLoad="", $body="", $head="") {
    if(!empty($onLoad)) {
        $onLoad = sprintf("onLoad=\"%s\"", $onLoad);        
    }
    if (!headers_sent()) {
        header("cache-control: no-cache");    
    }
    ?>
    <HTML>
        <HEAD>
            <META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
            <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
            <? print($head); ?>
        </HEAD>
        <BODY <? print($onLoad); ?>>
           <? print($body); ?>
        </BODY>
        <HEAD> <!-- convince IE really not to cache -->
            <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
        </HEAD>
    </HTML>
    <?  
}


?>/*
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
