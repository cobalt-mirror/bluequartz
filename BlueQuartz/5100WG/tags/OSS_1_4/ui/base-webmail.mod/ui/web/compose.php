<?php
set_time_limit(60);
include("ServerScriptHelper.php");
include("uifc/MultiFileUpload.php");
include("./Attachments.php");
include("./imapconnect.inc");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-webmail");

$product = $serverScriptHelper->getProductCode();
$isMonterey = ereg("35[0-9][0-9]R", $product);

global $_BUTTON_ID;
global $_LABEL_ID;


if ($reply || $replyall || $forward) {
	imapLock();
	connectToImap($mailbox);
	$header = imap_header($connection, $reply | $replyall | $forward);
	$headerCharset = getHeaderCharset($connection, $reply | $replyall | $forward);

	if (!$header) {
		throwError("[[base-webmail.errorFetchingHeader]]");
	}
	$msgStruct = imap_fetchstructure($connection, $reply | $replyall | $forward);
	imapUnlock();
	if (!$msgStruct) {
		throwError("[[base-webmail.errorFetchingStructure]]");
	}
	if ($reply) {
		//do the reply bit
		if ($header->reply_to) {
			$toAddy = formatImapEmailListForDisplay($header->reply_to);
		} else {
			$toAddy = formatImapEmailListForDisplay($header->from);
		}	
	} else if ($replyall) {
		//do the replyAll bit
		$toAddy = formatImapEmailListForDisplay($header->to);
		//switch self with sender
		if ($header->reply_to) {
			$switchWith = $header->reply_to;
		} else {
			$switchWith = $header->from;
		}
		$userDomain = getUserDomain($IMAP_username);

		$toArray = explode("&", $toAddy);
		if (strstr($toAddy,$IMAP_username)) {
		  //If we're in the address list
		  $matchSubstring = 1;
		  $toArray = swapEmailAddresses($toArray, $IMAP_username, formatImapEmailListForDisplay($switchWith));
		  $toArray = swapEmailAddresses($toArray, "$IMAP_username@$userDomain", formatImapEmailListForDisplay($switchWith), $matchSubstring);
		} else {
		  //We're not in the address list. Means we're part of a BCC list or a mailing list.
		  $toArray[] = formatImapEmailListForDisplay($switchWith);
		}
		$toAddy = implode(", ", $toArray);
		$ccAddy = formatImapEmailListForDisplay($header->cc);
		
	}

	$msgSubject = (!formatHeaderForDisplay($header->subject, $headerCharset)) ? $i18n->get("[[base-webmail.noSubject]]") : formatHeaderForDisplay($header->subject, $headerCharset);

	if ($forward) {
		$pos = strpos($msgSubject, $i18n->get("fwd"));
		if ($pos === false) 
			$msgSubject = $i18n->get("fwd") . $msgSubject;
	} else {
		$pos = strpos($msgSubject, $i18n->get("re"));
		if ($pos === false)
			$msgSubject = $i18n->get("re") . $msgSubject;
	}
	//quote the body
	$numParts = !$msgStruct->parts ? "1" : count($msgStruct->parts);
	$htmlFlag = 0;
	for ($i=0;$i<$numParts;$i++) {
		$part = !$msgStruct->parts[$i] ? $msgStruct : $msgStruct->parts[$i];
		if (getAttachmentNameForDisplay($part) == "Unknown" && get_mime_type($part) == "text") {
		  //if subpart = plain, dump body.
		  if (strtoupper($part->subtype)=="PLAIN") {
		    $bodyText .= getBody($connection, $reply|$replyall|$forward, $part, $i+1);
		  } 
		} else if (getAttachmentNameForDisplay($part) == "Unknown" && get_mime_type($part) == "multipart") {
		  //if subpart = plain, dump body.
		  //if subpart = html, body = "", html as attachment
		  for ($j=0;$j<count($part->parts);$j++) {
		    $subpart = $part->parts[$j];
		    if (strtoupper($subpart->subtype)=="PLAIN") {
		      $textOffset = ($j+1)/10;
		      $bodyText .= getBody($connection, $reply|$replyall|$forward, $subpart, $i+1+$textOffset);
		    } 
		  }
		// attach messages if forwarding
		} else if ($forward) {
			// Need to get attachments and save to temp file
			$imapPartID = ($i + 1);
			switch(get_mime_encoding($part)){
			case "base64":
			   $attach_string = imap_base64(imap_fetchbody($connection, $forward , $imapPartID));
			    break;
			case "qprint":
			   $attach_string = imap_qprint(imap_fetchbody($connection, $forward, $imapPartID));
			   break;
			default:
			   $attach_string = imap_fetchbody($connection, $forward, $imapPartID);
			   break;
			}
			$mimeType = get_mime_type($part) . "/" .
					strtolower($part->subtype);
			$attachDisplayName = getAttachmentNameForDisplay($part);
			if (!$mimeType) {
				$mimeType = "application/octet-stream";
			}
			$attachFile = tempnam("/tmp", "attch");

			$attach_fp = fopen($attachFile, "w");
			fputs($attach_fp, $attach_string);
			fclose($attach_fp);
	
			$jsString .= makeFileUploadJs(
				$attachDisplayName, basename($attachFile),
				filesize($attachFile), $mimeType
			);
		}
		
	}
	if (!$forward) {
	  $originalSeparator = $i18n->get("origMsgSeparator");
	} else if ($forward) {
	  $originalSeparator = $i18n->get("forwardMsgSeparator");
	}
	  $forwardHeaders = "";
	  if ($header->Date)
	    $originalHeaders .= $i18n->get("date") . ": " .  $header->Date . "\n";
	  if ($header->from)
	    $originalHeaders .= $i18n->get("from") . ": " . formatEmailListWithCommas(formatImapEmailListForDisplay($header->from)) . "\n";
	  if ($header->reply_to)
	    $originalHeaders .=  $i18n->get("replyto") . ": " . formatEmailListWithCommas(formatImapEmailListForDisplay($header->reply_to)) . "\n";
	  if ($header->to)
	    $originalHeaders .=  $i18n->get("to") . ": " . formatEmailListWithCommas(formatImapEmailListForDisplay($header->to)) . "\n";
	  if ($header->cc)
	    $originalHeaders .=  $i18n->get("cc") . ": " . formatEmailListWithCommas(formatImapEmailListForDisplay($header->cc)) . "\n";
	  if ($header->Subject) {
	    $sub = (!formatHeaderForDisplay($header->Subject, $headerCharset)) ? $i18n->get("[[base-webmail.noSubject]]") : formatHeaderForDisplay($header->Subject, $headerCharset);
	    $originalHeaders .= $i18n->get("subject") . ": " . $sub . "\n";
	  }
	  $originalHeaders .= "\n";
	  $bodyText = $originalSeparator . $originalHeaders . $bodyText;

} else if ($srcid) {
	//filling up for reply to mailling list
  //FIXME
	$bodyText = rawurldecode($body);
	$bodyText = "> " . str_replace("\n", "\n> ", 
		$bodyText);
	$msgSubject = $i18n->get("re") . $subject;
	// default is reply to sender
	$toArray[] = $fromAddy; 
	// optional is reply to all, i.e. mailing list AND sender
        if ($maillistall) {
	  $toArray[] = $listaddy;
        }
        $toAddy = implode(", ", $toArray);
}
$factory = $serverScriptHelper->getHtmlComponentFactory("base-webmail", "/base/webmail/composeHandler.php");
$page = $factory->getPage();
$page->setSubmitTarget("commFrame");
$block = $factory->getPagedBlock($reply?"replyMsg":($replyall?"replyAllMsg":($forward?"forwardMsg":"composeMsg")));

$to = $factory->getEmailAddressList("toAddy", "$toAddy", "rw");

if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
  $to->setImport(true, "top.code.EmailAddressList_LaunchAddressBook");
}

$to->setFormat("singleLine");
$to->DEFAULT_WIDTH=55;
$block->addFormField( $to, $factory->getLabel("cmpTo"));

$cc = $factory->getEmailAddressList("ccAddy", "$ccAddy", "rw");
$cc->setOptional(true);
$cc->setFormat("singleLine");
if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
  $cc->setImport(true, "top.code.EmailAddressList_LaunchAddressBook");
}
$cc->DEFAULT_WIDTH=55;
$block->addFormField( $cc, $factory->getLabel("cmpCc"));

$bcc = $factory->getEmailAddressList("bccAddy", "$bccAddy", "rw");
if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
  $bcc->setImport(true, "top.code.EmailAddressList_LaunchAddressBook");
}
$bcc->setFormat("singleLine");
$bcc->setOptional(true);
$bcc->DEFAULT_WIDTH=55;
$block->addFormField( $bcc, $factory->getLabel("cmpBcc"));

global $HTTP_USER_AGENT;
if (strstr($HTTP_USER_AGENT, "Mac") && strstr($HTTP_USER_AGENT, "MSIE 5")) {

  $attachments = $factory->getFileUpload("fileUpload");
  $attachments->setOptional("silent");
  $attachLabel = "cmpAttachmentsSingle"; // different help text for single file upload
} else {

  $attachments = new Attachments($page, "fileUpload");
  $attachments->setAddLabel("cmpAddAttachment");
  $attachments->setRemoveLabel("cmpRemoveAttachment");
  $attachLabel = "cmpAttachments"; // different help text for multi file upload
  $attachments->setOptional("silent");
}

$block->addFormField( $attachments, $factory->getLabel($attachLabel));

$subject = $factory->getTextField("subject", "$msgSubject", "rw");
$subject->setOptional("silent");
$subject->setSize(55);
$block->addFormField( $subject, $factory->getLabel("cmpSubject"));

$bodyText = $i18n->interpolateHtml("[[VAR.foo]]", array( 'foo' => $bodyText ));
$body = $factory->getTextBlock("body_id", "$bodyText", "rw");
$body->setOptional("silent");
$body->setHeight(12);
$body->setWidth(55);
$body->setWrap(true);
$block->addFormField( $body, $factory->getLabel("cmpBody"));

$block->addButton( $factory->getButton("Javascript: confirmSend()", "send"));

if (ereg("MSIE 5.5", $HTTP_USER_AGENT)) {
  if (!eregi("genfolders.php", $HTTP_REFERER) && $HTTP_REFERER != "") {
    $block->addButton($factory->getCancelButton($HTTP_REFERER));
  }
} else if (!eregi("jslibrary.php", $HTTP_REFERER) && $HTTP_REFERER != "") {
  $block->addButton($factory->getCancelButton($HTTP_REFERER));
}
    
print( $page->toHeaderHtml());
?>

<SCRIPT language="Javascript">

function launchAttachmentWindow() {
	attachWindow = window.open("/nav/single.php?root=webmail_attachments","attachWindow");
	if (attachWindow.opener == null)
		attachWindow.opener = self;
}
function confirmSend() {
	if(document.form.onsubmit()){
		document.form.submit();
	}
}
</SCRIPT>
<input type=hidden name="mailbox" value="<? print $mailbox ?>">
<input type=hidden name="answering" value="<? print ($reply | $replyall | $forward) ?>">
<?php print( $block->toHtml()); ?>

<SCRIPT language="Javascript">
	var fileUpload;
	fileUpload = document.form._fileUpload_SelectField;
	<?php print $jsString; ?>
</SCRIPT>

<?php
print( $page->toFooterHtml());
$serverScriptHelper->destructor();

// generate js to add file attachments to the [multi]FileUpload widget
function makeFileUploadJs ($filename,$tmpfile,$size,$mime)
{
	return "top.code.MultiFileUpload_addToList(fileUpload,\"$filename\",
			\"$tmpfile\", \"$size\", \"$mime\")" . ';' . "\n\t";

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

