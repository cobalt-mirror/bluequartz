<?
include("./imapconnect.inc");
include("ServerScriptHelper.php");
global $serverScriptHelper;
$factory = $serverScriptHelper->getHtmlComponentFactory("base-webmail", "/base/webmail/inbox.php");
$page = $factory->getPage();
$block = $factory->getPagedBlock("messageSent");

//We can't simply pass in the $toAddy_full, $ccAddy_full, and $bccAddy_full to EmailAddressList. The reason is, if an email address is like "fullname <address>", and the fullname contains a "+" in it, the + will get wiped out because of a peculiarity of urldecode(). I.E. urldecode("+") == " ". So, we have to break open the $toAddy_full into it's components, and urlencode them, so that they are later decoded correctly, then smack it back into an array. Yuck.

$addresses = formatHeaderForDisplay($toAddy_full. $ccAddy_full . $bccAddy_full);
$enc = explode("&", $addresses);
$tmpArray = array();
for($i=0; $i<count($enc); $i++){
  if($enc[$i] !=""){
    array_push($tmpArray, urlencode($enc[$i])); 
    }
}
$addresses = implode("&", $tmpArray);
$block->addFormField( 
		$factory->getEmailAddressList("sentto", $addresses, "r"), 
		$factory->getLabel("msgSent")
		);
$okButton = $factory->getButton($page->getSubmitAction(), "ok");
print $page->toHeaderHtml();
print $block->toHtml();
print "<br>";
//print $okButton->toHtml();
print $page->toFooterHtml();
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

