<?php
$mailbox2 = $mailbox;
$mailbox = "";
include("./imapconnect.inc"); 
$mailbox = ereg_replace("/", "_", $mailbox2);

if (ereg("^[[:space:]]*$", $mailbox)) {
	// only whitespace
	throwError("[[base-webmail.mailboxOnlyWhitespace]]");
} else if (substr($mailbox, 0, 1) == ".") {
	// has a . in front
	throwError("[[base-webmail.mailboxInitalPeriod]]");
}

// in japanese only, create
// $imapName: utf7 encoded mailbox name for use with IMAP, from sjis user input
// $l10nOrig: SJIS encoded mailbox name for use in messages, from imap utf7 ver.
if (ereg("^ja",$localePreference) || ereg("^ja",$HTTP_ACCEPT_LANGUAGE)) {
	$tmpstr = my_imap_utf7_decode($orig);
	$sjis = new EncodingConv($tmpstr, "japanese", "utf8");
	$l10nOrig = $sjis->toSJIS();
	$utf8 = new EncodingConv($mailbox, "japanese");
	$imapName = $utf8->toUTF8(); 
	$imapName = my_imap_utf7_encode($imapName);
} else {
	$l10nOrig = $orig;
	$imapName = $mailbox;
}

if ($l10nOrig == $mailbox) {		// original mbox same as input mbox
	throwError(	"[[base-webmail.mailboxRenamingFailed]]",
			"/base/webmail/mailboxAdmin.php",
			array("oldName" => $l10nOrig, "newName" => $mailbox)
	);
}

if (strlen(imap_utf7_encode($imapName))>250) {
	$mailbox = str_replace("\"", "\\\"", $mailbox);
	throwError("[[base-webmail.mailboxNameIsTooLong]]", $HTTP_REFERER, 
		array("mailboxName" => $mailbox));
}

imapLock();
connectToImap();

if (!$orig) {
	// create with a stage name, then rename.
	// imap_createmailbox is too restrictive
	$imapStage = "imapstaged" . md5(time());

	// create with standard us-ascii chars
	if (!imap_createmailbox($connection,
			formatMailboxForIMAP($imapStage, $localePreference)))
		$failed = true; 
	
	// rename w/non us-ascii char support
	if(!$failed) {
		if (!imap_renamemailbox($connection, 
			formatMailboxForIMAP($imapStage, $localePreference), 
			formatMailboxForIMAP($imapName, $localePreference)))
			$failed = true;

		// Cover our tracks on a failed rename
		if($failed) {
			$success = imap_deletemailbox($connection, 
				formatMailboxForIMAP($imapStage, $localePreference));
		}
	}
} else {
	// rename only
	if (!imap_renamemailbox($connection,
			formatMailboxForIMAP($orig, $localePreference),
			formatMailboxForIMAP($imapName, $localePreference)))
		$failed = true;
}

imapUnlock();
if (!$failed) {
	global $serverScriptHelper;
	print $serverScriptHelper->toHandlerHtml("/base/webmail/mailboxAdmin.php", array(), "base-webmail");
?>
<SCRIPT language="Javascript">
	top.commFrame.location = "/base/webmail/genfolders.php?refresh=1";
</SCRIPT>
<?
} else {
	if (!$orig) {
		// error while creating
		throwError("[[base-webmail.mailboxCreationFailed]]", "/base/webmail/mailboxAdmin.php", array("mailbox" => $mailbox));
	} else {
		// error while renaming
		throwError("[[base-webmail.mailboxRenamingFailed]]", "/base/webmail/mailboxAdmin.php", array("oldName" => "$l10nOrig", "newName" => "$mailbox"));
	}
}
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

