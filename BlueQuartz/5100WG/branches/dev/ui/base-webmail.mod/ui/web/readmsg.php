<?php
// Copyright 2000, Cobalt Networks.  All rights reserved.
// $Id: readmsg.php 3 2003-07-17 15:19:15Z will $

include ("./imapconnect.inc");
include ("ArrayPacker.php");
include ("ServerScriptHelper.php");
global $serverScriptHelper;

$factory = $serverScriptHelper->getHtmlComponentFactory("base-webmail");
global $i18n;

// lock IMAP
imapLock();
// make connection
$connection = connectToImap();

// get message
$msgHeader = imap_header($connection, $id);
$msgStruct = imap_fetchstructure($connection, $id);
$headerCharset = getHeaderCharset($connection, $id);

// build array of parts
$parts = $msgStruct->parts ? $msgStruct->parts : array($msgStruct);

$bodyText = "";

for ($i = 0; $i < count($parts); $i++) {
  $part = $parts[$i];

  $attachmentName = getAttachmentNameForDisplay($part);
  $mimeType = get_mime_type($part);
  $subtype = strtoupper($part->subtype);

  // a text part?
  if ($attachmentName == "Unknown" && $mimeType == "text") {
    // if mime subtype is plain, dump body.
    if ($subtype == "PLAIN")
      $bodyText .= getBody($connection, $id, $part, $i+1);

    // if mime subtype is html, show body as "" and html as attachment
    else if ($subtype == "HTML") {
      $bodyText = $i18n->get("viewAsHTML") . $bodyText;
      $attachsV .= "/base/webmail/getattach.php?".rawurlencode("mailbox=".rawurlencode(escapeForURL($mailbox, $localePreference))."&msgid=$id&partid=".($i))."&";
      $attachsL[] = $i18n->get("viewHtml");
      $attachsT[] = "attachment";
    }
  }

  // a multipart?
  else if ($attachmentName == "Unknown" && $mimeType == "multipart") {
    // if mime subtype is plain, dump body.
    // if mime subtype is html, show body as "" and html as attachment
    for ($j = 0; $j < count($part->parts); $j++) {
      $subpart = $part->parts[$j];

      $subpartSubtype = strtoupper($subpart->subtype);

      if ($subpartSubtype == "PLAIN") {
	$textOffset = ($j+1)/10;
	$bodyText .= getBody($connection, $id, $subpart, $i+1+$textOffset);
      } else if ($subpartSubtype == "HTML") {
	$bodyText = $i18n->get("viewAsHTML") . $bodyText;
	$attachsV .= "/base/webmail/getattach.php?".rawurlencode("mailbox=".rawurlencode(escapeForURL($mailbox, $localePreference))."&msgid=$id&partid=".($i)."&subpartid=".($j))."&";
	$attachsL[] = $i18n->get("viewHtml");
	$attachsT[] = "attachment";
      }
    }
  }

  // an attachment
  else {
    $attachsV .= "/base/webmail/getattach.php?".rawurlencode("mailbox=".rawurlencode(escapeForURL($mailbox, $localePreference))."&msgid=$id&partid=".($i))."&";
    $tmp=getAttachmentNameForDisplay($part);
    if($tmp == "Unknown"){
	$tmp = $i18n->get($tmp);
    }
    $attachsL[] = $tmp;
    if (part_is_browsable($part)) {
      $attachsT[] = "attachment";
    } else {
      $attachsT[] = "";
    }
    //$attachs .= "/base/webmail/getattach.php?msgid=$id&partid=".($i+1)."&".getAttachmentNameForDisplay($part)."&";
  }
}

// build the page
$page = $factory->getPage();
$block = $factory->getPagedBlock("readMessage");

// set our column widths
$block->setColumnWidths(array("20%", "80%"));

$from = $factory->getEmailAddressList("fromAddy", arrayToString(getAddresses($msgHeader->from, $headerCharset)), "r"); 
$block->addFormField( $from, $factory->getLabel("sender"));

$to = $factory->getEmailAddressList("toAddy", arrayToString(getAddresses($msgHeader->to, $headerCharset)), "r"); 
$block->addFormField( $to, $factory->getLabel("read_to"));

$ccList = arrayToString(getAddresses($msgHeader->cc, $headerCharset));
if ($ccList != "") {
  $cc = $factory->getEmailAddressList("ccAddy", $ccList, "r"); 
  $block->addFormField( $cc, $factory->getLabel("read_cc"));
}

if ($attachsV && count($attachsL) > 0) {
  $attachments = $factory->getUrlList("attachments", $attachsV, $attachsL, $attachsT, "r");
  $block->addFormField( $attachments, $factory->getLabel("attachments"));
}

$date = $factory->getTimeStamp("bleh", strtotime($msgHeader->date), "datetime", "r");
$block->addFormField( $date, $factory->getLabel("date"));


$subjectText = (!formatHeaderForDisplay($msgHeader->subject, $headerCharset)) ? $i18n->get("[[base-webmail.noSubject]]") : formatHeaderForDisplay($msgHeader->subject, $headerCharset);
$subject = $factory->getTextField("msgSubject", $subjectText, "r");
$block->addFormField( $subject, $factory->getLabel("subject"));

$body = $factory->getTextBlock("body", $bodyText, "r");
// let the browser wrap the body text
$body->setWrap(true);
$block->addFormField($body, $factory->getLabel("body"));

// construct buttons
$reply = $factory->getButton("/base/webmail/compose.php?mailbox=".rawurlencode(escapeForURL($mailbox, $localePreference))."&reply=$id", "reply");
$replyAll = $factory->getButton("/base/webmail/compose.php?mailbox=".rawurlencode(escapeForURL($mailbox, $localePreference))."&replyall=$id", "replyAll");
$forward = $factory->getButton("/base/webmail/compose.php?mailbox=".rawurlencode(escapeForURL($mailbox, $localePreference))."&forward=$id", "forward");
$remove = $factory->getButton("Javascript: confirmRemove()", "remove");
$backButton = $factory->getBackButton($HTTP_REFERER);

// set up the move to folder button..
$mailboxes = listMailboxes($connection);

// close IMAP connection
imap_close($connection);

// unlock IMAP
imapUnlock();

while (list($key, $val) = each($mailboxes)) {
  if ($val != $mailbox) {
    $moveToActions[] = "javascript: document.form.movedto.value=document.form.page$key; document.form.action='/base/webmail/movemsgs.php'; document.form.onsubmit();document.form.submit();";
    $jsPage .= "document.form.page$key=\"".rawurlencode(escapeForURL($val, $localePreference)) ."\";\n";
    $displayName = $mailboxDisplayNames{$val};
    $moveToValues[] = escapeForURL($displayName, $localePreference);
  }
}
$moveto = $factory->getMultiButton( "moveto", $moveToActions, $moveToValues);

$serverScriptHelper->destructor();

print( $page->toHeaderHtml() );
?>
<INPUT type=HIDDEN name="movedto" value="">
<INPUT type=HIDDEN name="remove<?php print $id ?>" value="1">
<INPUT type=HIDDEN name="mailbox" value="<?php print $mailbox ?>">
<SCRIPT language="javascript">
<?php print $jsPage; ?>
function confirmRemove() {
	if (confirm("<? print $i18n->get("removeMessage") ?>")) {
		 document.location="/base/webmail/removemsgs.php?mailbox=<? print rawurlencode(escapeForURL($mailbox, $localePreference))?>&remove<?print $id?>=true";
	}
}

</SCRIPT>
<?
print( "<br>");
print "<table border=0><tr><td valign=middle>";
if ($mailbox!=$IMAP_SENTMAIL) {
	print( $reply->toHtml());
	print( "</td><td valign=middle>");
	print( $replyAll->toHtml());
}
print "</td><td valign=middle>";
print( $forward->toHtml());
print( "</td><td valign=middle>");
print( $remove->toHtml());
print( "</td><td valign=middle>");
print( $moveto->toHtml());
print( "</td><td valign=middle>");
print( $backButton->toHtml());
print( "</td valign=middle></tr></table><br>");
print( $block->toHtml());
print "<br><table border=0><tr><td valign=middle>";
if ($mailbox!=$IMAP_SENTMAIL) {
	print( $reply->toHtml());
	print( "</td><td valign=middle>");
	print( $replyAll->toHtml());
}
print "</td><td valign=middle>";
print( $forward->toHtml());
print( "</td><td valign=middle>");
print( $remove->toHtml());
print( "</td><td valign=middle>");
print( $moveto->toHtml());
print( "</td><td valign=middle>");
print( $backButton->toHtml());
print( "</td valign=middle></tr></table>");
print( $page->toFooterHtml());


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

