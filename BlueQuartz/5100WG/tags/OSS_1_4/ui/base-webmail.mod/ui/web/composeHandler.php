<?

include("./imapconnect.inc"); 
include("ServerScriptHelper.php");
include("ArrayPacker.php");

global $HTTP_ACCEPT_LANGUAGE;
global $serverScriptHelper;

global $HTTP_USER_AGENT;
if (!(strstr($HTTP_USER_AGENT, "Mac") && strstr($HTTP_USER_AGENT, "MSIE 5"))) {

 $enc = explode("&", $fileUpload);
 $tmpArray = array();
 for($i=0; $i<count($enc); $i++){
   if($enc[$i] !=""){
     array_push($tmpArray, $enc[$i]); 
   }
 }
 $fileUpload = $tmpArray;

 $fileUpload_size = stringToArray($fileUpload_size);
 $fileUpload_type = stringToArray($fileUpload_type);
 $fileUpload_name = stringToArray($fileUpload_name); 
}

$product = $serverScriptHelper->getProductCode();
$isMonterey = ereg("35[0-9][0-9]R", $product);

$localePreference = $serverScriptHelper->getLocalePreference();

function getContentType($type = "text/plain", $params = array()) {
  global $localePreference, $HTTP_ACCEPT_LANGUAGE;
  $returnHeader = "Content-Type: $type";
  // if it's a text type or subtype, add the charset
  if (ereg("^text/", $type) || ereg("/text$", $type)) {
//LOCALE SPECIFIC
    if (ereg("^ja",$localePreference) || ereg("^ja", $HTTP_ACCEPT_LANGUAGE)) {
      // attach the encoding type to the header..
      $returnHeader .= "; charset=iso-2022-jp";
    }else if(ereg("^zh",$localePreference) || ereg("^zh", $HTTP_ACCEPT_LANGUAGE)){
	if(ereg("zh[-_]TW",$localePreference) || ereg("zh[-_]TW", $HTTP_ACCEPT_LANGUAGE)){
		$returnHeader .= "; charset=big5";
	}else{
		$returnHeader .= "; charset=gb2312";
	}
    } else {
      $returnHeader .= "; charset=iso-8859-1";
    }
  }
  foreach ($params as $key => $value) {
    $returnHeader .= ";\r\n  $key=$value";
  }
  $returnHeader .= "\r\n"; 
  return $returnHeader;
}
if (!count($fileUpload)) {
	$mailheader .= getContentType();
	$msg_body = formatBodyForTransmit($body_id, $localePreference);
} else {
	$mimeBoundary = "------------" . md5(time());
	// Make sure ContentTYpe has quoted boundary or else attachments
	// Will not be seen correctly.
	$quotedMimeBoundary = "\"" . $mimeBoundary . "\"";
	$mailheader .= getContentType("multipart/mixed", array("boundary" => $quotedMimeBoundary));
	$mailheader .= "MIME-Version: 1.0\r\n";
	$msg_body = "This is a multi-part message in MIME format.\r\n";
	$msg_body .= "--$mimeBoundary\r\n";
	$msg_body .= getContentType();
	$msg_body .= "Content-Transfer-Encoding: 7bit\r\n";
	$msg_body .= "\r\n" . formatBodyForTransmit($body_id, $localePreference). "\r\n";

	if (strstr($HTTP_USER_AGENT, "Mac") && strstr($HTTP_USER_AGENT, "MSIE 5")) {
	  // browser doesn't give correct mime type sometimes
	  // default to this, it seems to work
	  if ($fileUpload_type != "") {
	    $fileUpload_type = "application/octet-stream";
	  }

	  $msg_body .= "--$mimeBoundary\r\n";
	  $msg_body .= getContentType($fileUpload_type, array("name" => "\"" . formatFilenameForTransmit($fileUpload_name, $localePreference) ."\"" ));
	  $msg_body .= "Content-Transfer-Encoding: base64\r\n";
	  $msg_body .= "Content-Disposition: inline;\r\n";
	  $msg_body .= " filename=\"" . formatFilenameForTransmit($fileUpload_name, $localePreference) . "\"\r\n";
	  $msg_body .= "\r\n";
	  
	  /* open the attachment file and base64/chunksplit it */
	  $fh = fopen($fileUpload, "r");
	  $contents = fread($fh, $fileUpload_size);
	  $encoded = chunk_split(base64_encode($contents));
	  fclose($fh);
	  
	  $msg_body .= $encoded . "\r\n";
	  $msg_body .= "--$mimeBoundary--";
	  
	} else {
	

	// Loop through the attachments...
	for ($i = 0; $i< count($fileUpload); $i++) {
		// set some local values for this attachment
		$attachment_type = $fileUpload_type[$i];
		$attachment_name = $fileUpload[$i];
		$attachment = $fileUpload_name[$i];
		$attachment_size = $fileUpload_size[$i];
		// start the attachment...
		$msg_body .= "--$mimeBoundary\r\n";
		$msg_body .= getContentType($attachment_type, array("name" => "\"" . formatFilenameForTransmit($attachment_name, $localePreference) . "\""));
		$msg_body .= "Content-Transfer-Encoding: base64\r\n";
		$msg_body .= "Content-Disposition: inline;\r\n";
		$msg_body .= " filename=\"" . formatFilenameForTransmit($attachment_name, $localePreference) . "\"\r\n";
		$msg_body .= "\r\n";
		
	 	/* open the attachment file and base64/chunksplit it */
		
		if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
		  
		$fileHandle = fopen("/tmp/$attachment", "r");
		$contents = fread($fileHandle,filesize("/tmp/$attachment"));
		fclose($fileHandle);

		// clean up
		unlink("/tmp/$attachment");

		} else {
		  
		  $fullName = "/tmp/" . $attachment;
		  
		  //check if uids are same
		  $pwnam = posix_getpwnam($PHP_AUTH_USER);
		  $uid = $pwnam["uid"];
		  $fileUid = fileowner($fullName);
		  
		  if ($uid == $fileUid) {
		    $fileHandle = fopen($fullName, "r");
		    $contents = fread($fileHandle,filesize($fullName));
		    fclose($fileHandle);
		  }
		  
		  unlink($fullName);
		}
		

		$encoded = chunk_split(base64_encode($contents));
		
		$msg_body .= $encoded . "\r\n";


		// end the attachment...

	}

	$msg_body .= "--$mimeBoundary--";
	}
}

$mailheader .= "X-Mailer: Cobalt Webmail\r\n";
$mailheader .= "Date: " . date ("D, d M Y H:i:s O") . "\r\n";

// Take care of encodings for To,CC,BCC and From
if ($toAddy_full) $to_sending = formatEmailListForTransmit($toAddy_full, $localePreference);
if ($ccAddy_full) $cc_sending = formatEmailListForTransmit($ccAddy_full, $localePreference);
if ($bccAddy_full) $bcc_sending = formatEmailListForTransmit($bccAddy_full, $localePreference);

$emailDomain = getUserDomain($IMAP_username);
$returnPath = "$IMAP_username@$emailDomain";
$failIfLocalUserInvalid = 0;

$cceClient = $serverScriptHelper->getCceClient();
$userObject = $cceClient->getObject("User", array("name" => $IMAP_username));
$fromAddress = $userObject["fullName"]." <$IMAP_username@$emailDomain>";
$mailheader .= "From: ".formatEmailListWithCommas(formatEmailListForTransmit($fromAddress, $localePreference));

if ($rv = imap_mail2(formatEmailListWithCommas($to_sending), 
		     formatHeaderForTransmit($subject, $localePreference), 
		     $msg_body, 
		     $mailheader, 
		     formatEmailListWithCommas($cc_sending), 
		     formatEmailListWithCommas($bcc_sending), $returnPath, $failIfLocalUserInvalid)) {
	// change the array into a format that will look good
	$failedEmails = implode(", ", $rv);
	if (count($rv) == 1) {
		throwError("[[base-webmail.errorSendingMessageSingle]]","javascript: void(0)", array("emails"=>$failedEmails));
	} else { 
		throwError("[[base-webmail.errorSendingMessagePlural]]", "javascript: void(0)",array("emails"=>$failedEmails));
	}
}

if (isServerUp()) {
imapLock();
connectToImap($mailbox);
// append the msg to the sent-mail mailbox

 $fullMsg = $mailheader . "\r\n" .
   "To: ".formatEmailListWithCommas($to_sending)."\r\n" .
   "Cc: ".formatEmailListWithCommas($cc_sending)."\r\n" . 
   "Bcc: ".formatEmailListWithCommas($bcc_sending)."\r\n" .
   "Subject: ".formatHeaderForTransmit($subject, $localePreference)."\r\n\r\n".
   $msg_body;
 $folder = formatMailboxForIMAP($IMAP_SENTMAIL, $localePreference);
if (!imap_append($connection, $folder, $fullMsg)) {
	throwError("[[base-webmail.errorArchivingMessage]]");
}

//set the answered flag if neccesary
if ($answering) {
	if (!imap_setflag_full($connection, $answering, "\\Answered")) {
		throwError("[[base-webmail.errorSettingAnsweredFlag]]");
	}
}
imap_reopen($connection, $folder);
imap_setflag_full($connection, imap_num_msg($connection), "\\Answered");
imapUnlock();
}
?>
<SCRIPT language="javascript">
top.mainFrame.location = "/base/webmail/composeHandlerPage.php?&bccAddy_full=<?php print rawurlencode($bcc_sending)?>&toAddy_full=<?php print rawurlencode($to_sending) ?>&ccAddy_full=<?php print rawurlencode($cc_sending) ?>";
</SCRIPT>
<?$serverScriptHelper->destructor();
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

