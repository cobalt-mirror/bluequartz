<?php
//$bodyOnly means get the body as an attachment
//$partid means attachments #($partid) from main body
//$subpartid means subattachments #($subpartid) from attachment #($partid)

include ("./imapconnect.inc");
imapLock();
connectToImap($mailbox);
$msgStruct = imap_fetchstructure($connection, $msgid);
$part = !$msgStruct->parts[$partid] ? $msgStruct : $msgStruct->parts[$partid];

if (isset($subpartid)) {
  $part = $part->parts[$subpartid];
  $imapPartID = ($partid+1) + (($subpartid+1)/10);
} else {
  $imapPartID = ($partid+1);
}

// When the mime-type of the file being downloaded is something we know the 
// user will be able to browse (eg: text or a jpeg), we display it inline, 
// otherwise we use application/oct-stream so that the user is forced to 
// download the file.  This avoids any potential problems we could have with 
// the user simply clicking the attachment link when they have broken mime 
// handlers.  
if (part_is_browsable($part)) {
  // this is something we can view in a browser, so we view it inline, but we
  // should *still* send the filename along in case they wanted to download it.
  $mimeType = get_mime_type($part) . "/" . strtolower($part->subtype);
  $disposition = "inline";
} else {
  // this is something we can't garantee is browser viewable, so we force a 
  // download
  $mimeType = "application/octet-stream";
  $disposition = "attachment";
}

header("Content-Type: " . $mimeType .  "\r\n");
header("Content-Disposition: $disposition; filename=" . getAttachmentNameForDisplay($part). "\r\n");

switch (get_mime_encoding($part)) {
 case "base64":
   echo imap_base64(imap_fetchbody($connection, $msgid, $imapPartID));
   break;
 case "qprint":
   echo imap_qprint(imap_fetchbody($connection, $msgid, $imapPartID));
   break;
 default:
   echo imap_fetchbody($connection, $msgid, $imapPartID);
   break;
}
imapUnlock();

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

