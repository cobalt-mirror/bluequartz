<?
include ("ServerScriptHelper.php");
$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("palette");
$page = $factory->getPage();
$i18n = $page->getI18n();

$product = $serverScriptHelper->getProductCode();
$isMonterey = ereg("35[0-9][0-9]R", $product);

if ($fileUpload == "none") {
	/* file never got uploaded or wasn't valid */
	$scrollList = $factory->getScrollList("errorGettingFile", array("message"));
	$scrollList->setEntryCountHidden(true);
	$scrollList->setHeaderRowHidden(true);
	$scrollList->addEntry(array($factory->getTextField("nothing", $i18n->get("errorOccuredWhilereceivingFile"), "r")));
	$backButton = $factory->getBackButton($HTTP_REFERER);
	print $page->toHeaderHtml();
	print $scrollList->toHtml();
	print "<br>".$backButton->toHtml();
	print $page->toFooterHtml();
} else {
	/* file seems good */
  // netscape doesn't give correct mime type sometimes
  // default to this, it seems to work
  if (!$fileUpload_type) {
    $fileUpload_type = "application/octet-stream";
  }

  if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
	/* get the users home directory */
	$pwnam = posix_getpwnam($loginName);
	$homedir = $pwnam["dir"];
	
	/* Create the users temp directory if it doesn't exist */
	#$serverScriptHelper->shell("/bin/mkdir -p --mode=700 $homedir/Temp", $output);
	
	$fileName = tempnam("/tmp","attch");
	copy($fileUpload, $fileName);
	chmod($fileName, 0600);

	ereg("^.*\/(.*)$", $fileName, $regs);
	$stripedFileName  = $regs[1];

	/* throw some script back to the client */
  } else {
    // get uid
    $pwnam = posix_getpwnam($PHP_AUTH_USER);
    $uid = $pwnam["uid"];
    // get filename
    $baseName = base64_encode(time());
    $fullName = "/tmp/" . $baseName;
    
    // copy file
    $copySuccess = copy($fileUpload, $fullName);

    if ($copySuccess) {
      // set file owner and perms to uid, r/w user only
      chown($fullName, $uid); // FIXME what if fails?
      chmod($fullName, 0600); // FIXME what if fails?
    } else {
      return "error"; // FIXME error messages?
    }
    
    $stripedFileName = $baseName;

  } 

      ?>
<SCRIPT language="javascript">
var fileList;
	fileList = top.opener.fileList;

	top.code.MultiFileUpload_addToList(fileList, "<? print $i18n->interpolateJs($fileUpload_name) ?>", "<? print $stripedFileName ?>", "<? print $fileUpload_size ?>", "<? print $fileUpload_type?>");

	top.close();
</SCRIPT>	
    <?
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
