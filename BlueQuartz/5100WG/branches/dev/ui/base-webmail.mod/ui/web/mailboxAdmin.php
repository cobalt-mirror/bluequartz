<?PHP
include("./imapconnect.inc"); 
include("ServerScriptHelper.php");
global $serverScriptHelper;
$factory = $serverScriptHelper->getHtmlComponentFactory("base-webmail");
global $i18n;
$page = $factory->getPage();
$form = $page->getForm();
$formId = $form->getId();
$scrollList = $factory->getScrollList("mailboxes", array(
			"mailboxName",
			"mailboxAction"), array(0));
$scrollList->addButton($factory->getButton("/base/webmail/createmailbox.php", "addMailbox"));
$scrollList->setAlignments(array("left", "center"));
$scrollList->setColumnWidths(array("", "1%"));

imapLock();
connectToImap();
$mailboxList = listMailboxes($connection);
imapUnlock();

function escapeForJs($string){
	$string=preg_replace("/\\\\/",'\\\\',$string);
	$string=preg_replace("/'/", "\\'", $string);
	return $string;
}


for ($i=0;$i<count($mailboxList);$i++) {
	$mailbox = $mailboxList[$i];
	$mailboxLabel = $mailboxDisplayNames{$mailbox};
	$comp = $factory->getCompositeFormField(array());
	$modifyButton = $factory->getModifyButton("createmailbox.php?orig=". rawurlencode($mailbox));

	// make a Javascript and PHP safe mailbox name
	// Since we differentiate between display name, and the actual imap name, we need to pass both along to the javascript.
	$escapedMailboxDisplay = escapeForJs($mailboxLabel);
	$escapedMailbox = escapeForJs($mailbox);
	// this is to prevent backslash at the end of SJIS strings to escape
	// the ending quote
	$extraSpaceDisplay = "false";
	$extraSpace = "false";
	if(substr($escapedMailboxDisplay, -1, 1) == "\\") {
		$escapedMailboxDisplay .= " ";
		$extraSpaceDisplay = "true";
	}
	if(substr($escapedMailbox, -1, 1) == "\\") {
		$escapedMailbox .= " ";
		$extraSpace = "true";
	}
	$encoded=rawurlencode($mailbox);
	$removeButton = $factory->getRemoveButton("javascript: confirmRemove('$escapedMailbox', $extraSpace, '$escapedMailboxDisplay', $extraSpaceDisplay, '$encoded')"); 

	if ($mailbox==$IMAP_INBOX||$mailbox==$IMAP_SENTMAIL) {
		$modifyButton->setDisabled(true);
		$removeButton->setDisabled(true);
	}
	$comp->addFormField($modifyButton);
	$comp->addFormFIeld($removeButton);

	$scrollList->addEntry( array(
		$factory->getTextField("", $mailboxLabel,"r"),
		$comp
	));
}

print $page->toHeaderHtml();

// make a hidden field to store folder name
$folderName = $factory->getTextField("name", "", "");
print($folderName->toHtml());
?>
<SCRIPT language="javascript">
function confirmRemove(name, extraSpace, nameDisplay, extraSpaceDisplay, encoded) {
	if(extraSpace)
		name = name.substr(0, name.length-1);
	if(extraSpaceDisplay)
		name = nameDisplay.substr(0, nameDisplay.length-1);

	if (confirm(top.code.string_substitute("<? print $i18n->getJs("confirmRemoveMailbox") ?>", "[[VAR.name]]", nameDisplay))) {
		var form = document.<?php print($formId); ?>;
		form.name.value = encoded;
		form.action = '/base/webmail/dropmailbox.php';
		form.submit();
		//location="/base/webmail/dropmailbox.php?name="+encoded;
	}
}
</SCRIPT>
<?
print $scrollList->toHtml();
print $page->toFooterHtml();

$serverScriptHelper->destructor();
exit;

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

