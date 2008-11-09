<?php
include("./imapconnect.inc"); 

imapLock();
connectToImap($mailbox);
$num = imap_num_msg($connection);

$msg_array = imap_sort($connection, 1,1);
for ($i=0;$i<$num;$i++) {
	if (${"remove".$msg_array[$i]}) {
		//msg selected
		if ($msglist) {
			$msglist .= ",";
		}
		$msglist .= $msg_array[$i];
		$movedSome = 1;
	}
}

if (!$movedSome) 
	throwError("[[base-webmail.errorMovingMessageNoneSelected]]");

$newbox = $movedto=="Inbox" ? "INBOX" : $IMAP_MAILFOLDER.$movedto;
$newbox = rawurldecode($newbox);
$newbox = unescapeFromURL($newbox);

//This function takes the folder name, not {server.ext/type:port}folder
if (!imap_mail_move($connection, $msglist, $newbox)) {
	throwError("[[base-webmail.errorMovingMessage]]");
}
imap_expunge($connection);
imapUnlock();

global $serverScriptHelper;
print ($serverScriptHelper->toHandlerHtml("/base/webmail/inbox.php?mailbox=" . rawurlencode(escapeForURL($mailbox, $localePreference)), $errors, "base-webmail"));
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

