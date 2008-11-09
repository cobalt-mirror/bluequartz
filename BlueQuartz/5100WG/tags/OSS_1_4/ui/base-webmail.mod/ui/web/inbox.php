<?php
set_time_limit(60);
include("./imapconnect.inc");
include("ServerScriptHelper.php");
include("uifc/ScrollList.php");
class MyScrollList extends ScrollList {
	function sortEntries(&$entries) {
		return true;
	}
}

global $serverScriptHelper;
global $i18n;

$factory = $serverScriptHelper->getHtmlComponentFactory("base-webmail");
$page = $factory->getPage();

$labelAr[] = $factory->getLabel("status");
$labelAr[] = $factory->getLabel("subject");
if ($mailbox == $IMAP_SENTMAIL) {
  $labelAr[] = $factory->getLabel("sentto");
  $labelAr[] = $factory->getLabel("dateSent");
} else {
  $labelAr[] = $factory->getLabel("sender");
  $labelAr[] = $factory->getLabel("date");
}

imapLock();
connectToImap($mailbox);
$mailboxes = listMailboxes($connection);
//set up for the move to multiButton:
while (list($key, $val) = each($mailboxes)) {
	if ($val!=$mailbox) {
		$moveToActions[] = "Javascript: document.form.movedto.value=document.form.page$key; document.form.action='/base/webmail/movemsgs.php'; document.form.onsubmit();document.form.submit();";
		$jsPage .= "document.form.page$key=\"".rawurlencode(escapeForURL($val, $localePreference)) ."\";\n";
		$displayName = $mailboxDisplayNames{$val};
		$moveToValues[] = escapeForURL($displayName, $localePreference);
	}
}

if(!$mailbox || strtoupper($mailbox)=="INBOX"){
  $label="inboxFolder";
}else{
  $displayName = $mailboxDisplayNames{$mailbox};
  $label=escapeForURL($displayName, $localePreference);
}

$scrollList = new MyScrollList ($page, "mailbox", $factory->getLabel($label, ""),$labelAr,array(3,1,2)); // start with 3 so that column 3 (date) is default sort index
$scrollList->setAlignments(array("center", "left", "left", "left"));
$scrollList->setColumnWidths(array("*", "250", "*", "150"));
$scrollList->setSelectAll(true);
$scrollList->addButton($factory->getButton("Javascript: confirmRemove()", "removeMessages"));

$moveto = $factory->getMultiButton( "moveto", $moveToActions, $moveToValues);
$scrollList->addButton($moveto);


// Set the values to be used for sort order
if ($scrollList->getSortOrder()=="descending") 
	$sortedOrder = true;
switch ($scrollList->getSortedIndex()) {
	case 1: 
		$sortedIndex = SORTSUBJECT;
		break;
	case 2:
		$sortedIndex = SORTFROM;
		break;
	case 3:
	default:
		$sortedIndex = SORTARRIVAL;
		$sortedOrder = !$sortedOrder;
		
} 
$msg_array = imap_sort($connection,$sortedIndex,$sortedOrder);
$num = imap_num_msg($connection);
$teststat = "";
if ($num < 0) {
	//NO MSGS!!!
} else {
	$lengthMsgs = $scrollList->getLength();
	$startingMsgs = $scrollList->getPageIndex() * $lengthMsgs;
	$finishingMsgs = (1+$scrollList->getPageIndex()) * $lengthMsgs;
	$finishingMsgs = ($finishingMsgs > $num) ? $num : $finishingMsgs;
	$msgArray = array_slice( $msg_array, $startingMsgs, $lengthMsgs);
	$shown = $i18n->interpolate("[[base-webmail.shownMessage]]", array("showing" => ($finishingMsgs < $lengthMsgs ? $i18n->interpolate("[[base-webmail.showingAll]]") : $finishingMsgs - $startingMsgs)));
	$scrollList->setEntryCountTags("[[base-webmail.singularMessage]]".$shown, "[[base-webmail.pluralMessages]]".$shown);
	$msgseq = implode(",", $msgArray);

	 $blankEntry = array(
			);
	
	$blankLabel = "remove";
	for ($i=0;$i<$startingMsgs;$i++) {
		$scrollList->addEntry($blankEntry, $blankLabel);
	}
	for ($i=$startingMsgs;$i<$finishingMsgs;$i++) {
	  // actual entry
	  	$msgid = $msg_array[$i];
		$header = imap_header($connection, $msgid);	
		$headerCharset = getHeaderCharset($connection, $msgid);

		$subject = (!formatHeaderForDisplay($header->subject, $headerCharset)) ? $i18n->get("[[base-webmail.noSubject]]") : formatHeaderForDisplay($header->subject, $headerCharset);
		// collate a list of addressed in To, CC, and Bcc
		if ($mailbox == $IMAP_SENTMAIL) {
			$addressed = "&";
			if ($header->to) while (list($key, $val) = each ($header->to)) 
				$addressed .= urlencode(formatHeaderForDisplay(getEmailFromArray($val),$headerCharset)) . "&";
					
			if ($header->cc) while (list($key, $val) = each ($header->cc))
				$addressed .= urlencode(formatHeaderForDisplay(getEmailFromArray($val), $headerCharset)) . "&";
		} else {
		  // just the sender please
		  $addressed = "&" . urlencode(formatHeaderForDisplay(getEmailFromArray($header->from[0]), $headerCharset));
		}

// smart code :)
		$date_widget = $factory->getTimeStamp("date_$msgid", strtotime($header->date), "datetime", "r");
		$date_widget->setFormat("datetime");
		$status = $factory->getStatusSignal($header->Answered == 'A'?"replied":($header->Unseen == 'U' || $header->Recent == 'N'?"new":"old"));
		$status->setDescribed(true);
		$scrollList->addEntry(array(   
		   $status,
		   $factory->getUrl("url_".trim($header->Msgno),
				    "/base/webmail/readmsg.php?mailbox=".rawurlencode(escapeForURL($mailbox, $localePreference))."&id=".trim($header->Msgno),
				    $subject, 
				    "", 
				    "r"),
		   $factory->getEmailAddressList("address_".$msgid, $addressed, "r"),
		   $date_widget
		   ),
		"remove".trim($header->Msgno));
	}
	imapUnlock();
	for ($i=$finishingMsgs;$i<$num;$i++) {
		$scrollList->addEntry($blankEntry, $blankLabel);
	}
}


$serverScriptHelper->destructor();

print( $page->toHeaderHtml() );
?>
<input type="hidden" name="movedto" value="">
<input type="hidden" name="mailbox" value="<?print rawurlencode(escapeForURL($mailbox, $localePreference))?>">
<input type="hidden" name="allSelected" value="">
<SCRIPT LANGUAGE="javascript">
<? print $jsPage ?>
function confirmRemove() {
  // TODO:  add a no messages selected statement
  if (confirm("<?php print($i18n->get("removeMessagesConfirm")) ?>" )) {
	document.form.action = "/base/webmail/removemsgs.php";
	document.form.submit();
  }
}
</SCRIPT>


<?php
$keyTitle = $factory->getLabel("keyTitle", false);
$repliedStatus = $factory->getStatusSignal("replied");
$repliedLabel = $factory->getLabel("keyReplied", false);
$newStatus = $factory->getStatusSignal("new");
$newLabel = $factory->getLabel("keyNew", false);
$oldStatus = $factory->getStatusSignal("old");
$oldLabel = $factory->getLabel("keyOld", false);


print("<BR><TABLE>");
print("<TR><TD ROWSPAN=\"4\" WIDTH=\"60\">"
      . $keyTitle->toHtml() . "</TD>"
      . "<TD>" . $repliedStatus->toHtml() . "</TD>"
      . "<TD>" . $repliedLabel->toHtml() . "</TD></TR>");
print("<TR><TD>" . $newStatus->toHtml() . "</TD>"
      . "<TD>" . $newLabel->toHtml() . "</TD></TR>");
print("<TR><TD>" . $oldStatus->toHtml() . "</TD>"
      . "<TD>" . $oldLabel->toHtml() . "</TD></TR>");
print("</TABLE></P>");


print ( $scrollList->toHtml());

print ( $page->toFooterHtml());


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

