<?php
include("ServerScriptHelper.php");
include("Error.php");
include("ArrayPacker.php");
include("EncodingConv.php");

$serverScriptHelper = new ServerScriptHelper();
if ($serverScriptHelper->hasCCE()) {
  include("CceError.php");
}
$product = $serverScriptHelper->getProductCode();
$isMonterey = ereg("35[0-9][0-9]R", $product);
$localePreference = $serverScriptHelper->getLocalePreference();


$IMAP_SERVER=exec("hostname");
$IMAP_SERVERPORT="143";
//get the default folders....
$i18n = $serverScriptHelper->getI18n("base-webmail");
$IMAP_MAILFOLDER = "";// NOT $i18n->get("mailFolder"), since we've changed IMAP to work correctly
$IMAP_INBOX="INBOX";
$IMAP_SENTMAIL = "sent-mail";
$mailboxDisplayNames = array();

$mailbox = rawurldecode($mailbox);
$mailbox = unescapeFromURL($mailbox);

if (needLogin()) {
  error_log("i wonder if i'll ever reach this code, in imap_connect.inc");
  login_prompt();
} else {
  $IMAP_username = getUsername();
  $IMAP_password = getPassword();
}

$imapLockFile = "/tmp/imap" . md5($IMAP_username);
$imapLockDescriptor = fopen($imapLockFile, "a");
if (!$imapLockDescriptor) {
  error_log("imap lock: couldn't open file");
}
$connection = "";


function isServerUp() {
  global $IMAP_SERVER, $IMAP_SERVERPORT;
  $serverSocket = fsockopen ($IMAP_SERVER, $IMAP_SERVERPORT);
  $serverUp = $serverSocket ? true: false;
  if ($serverSocket)
    fclose($serverSocket);
  return $serverUp;
}

function connectToImap($mailbox = "INBOX") {
  global $localePreference;
  if (isServerUp()) {
    global $connection, $IMAP_SERVER, $IMAP_SERVERPORT, $mailbox, $IMAP_MAILFOLDER, $IMAP_username, $IMAP_password, $IMAP_SENTMAIL;
    error_reporting(error_reporting() - 2);
    $connection = imap_open(formatMailboxForIMAP($mailbox, $localePreference), $IMAP_username, $IMAP_password);
    error_reporting(error_reporting() + 2);
    if (!$connection) {
      print "<SCRIPT>\n";
      print "top.mainFrame.location = \"noImapAccount.php\";\n";
      print "</SCRIPT>\n";
      exit;
    } 
    imap_createmailbox($connection, formatMailboxForIMAP($IMAP_SENTMAIL, $localePreference));
  } else {
    print "<SCRIPT>\n";
    print "top.mainFrame.location = \"imapServerDown.php\";\n";
    print "</SCRIPT>\n";
    exit;
  }

  return $connection;
}
    
function login_prompt() {
  error_log("do we need this code? in imapconnect.inc");
}

function openMailbox($con, $mailbox) {
  global $IMAP_SERVERPORT, $IMAP_SERVER;
  global $localePreference;
  return (imap_reopen($con, formatMailboxForIMAP($mailbox, $localePreference), 0));
}

function getEmailFromArray($ar) {
  global $i18n;
  if (!$ar->mailbox) {
    $email = $i18n->get("[[base-webmail.noSender]]");
  } else {
    $host = ($ar->host == "UNKNOWN") ? 
	     ""
	     : "@" . $ar->host;
    $email = $ar->mailbox . $host;
    if ($ar->personal) {
      $email = $email . " (" . $ar->personal . ")";
    }
  }
  return $email;
}

function formatEmailList($ar) {
	$arParts = explode("&", $ar);
	for ($i=1;$i<(count($arParts)-1);$i++){
		if ($i!=1) {
			$ret .= ",";
		}
		$ret .= rawurldecode($arParts[$i]);
	}
	return $ret;	
}

function getAttachmentNameForDisplay($de_part)
{
  global $serverScriptHelper;
	$att_name = "Unknown";
	for ($i = 0; $i < count($de_part->parameters); $i++) {
		$param = $de_part->parameters[$i];
		if (strtoupper($param->attribute) == "NAME") {
			$att_name = $param->value;
		}
	}
	$att_name = formatFilenameForDisplay($att_name);
	return $att_name;
}

function get_mime_type($de_part)
{
	switch ($de_part->type)
	{
	        case TYPETEXT:		$mime_type = "text"; break;
	        case TYPEMULTIPART:     $mime_type = "multipart"; break;
		case TYPEMESSAGE:	$mime_type = "message"; break;
		case TYPEAPPLICATION:	$mime_type = "application"; break;
		case TYPEAUDIO:		$mime_type = "audio"; break;
		case TYPEIMAGE:		$mime_type = "image"; break;
		case TYPEVIDEO:		$mime_type = "video"; break;
		case TYPEMODEL:		$mime_type = "model"; break;

		default:		$mime_type = "unknown";
	}
	return $mime_type;
}

function get_mime_encoding($de_part)
{
	switch ($de_part->encoding)
	{
		case ENCBASE64:			$mime_encoding = "base64"; break;
		case ENCQUOTEDPRINTABLE:	$mime_encoding = "qprint"; break;
		case ENCOTHER:			$mime_encoding = "other"; break;
		default:			$mime_encoding = "other";
	}
	return $mime_encoding;
}

function listMailboxes($connection) {
	global $IMAP_SERVER, $IMAP_SERVERPORT, $IMAP_MAILFOLDER;
	global $IMAP_INBOX, $IMAP_SENTMAIL;
	global $HTTP_ACCEPT_LANGUAGE;
	global $mailboxDisplayNames;
	global $i18n;
	global $serverScriptHelper;
	global $localePreference;

	$mailboxes = imap_listmailbox($connection, "\{$IMAP_SERVER:$IMAP_SERVERPORT}$IMAP_MAILFOLDER*", "*");
	if (is_array($mailboxes) && count($mailboxes)!=1) {
		while (list($key, $mailbox) = each($mailboxes)) {
		  $displayName = "";
		  $mailbox = substr( $mailbox, strlen("\{$IMAP_SERVER:$IMAP_SERVERPORT}$IMAP_MAILFOLDER"), strlen($mailbox)); 
//LOCALE SPECIFIC
		  if (ereg("^ja",$localePreference) || ereg("^ja",$HTTP_ACCEPT_LANGUAGE)) {
		    $displaybox =  my_imap_utf7_decode($mailbox);
		    $j = new EncodingConv($displaybox, "japanese", "utf8");
		    $displayName = $j->toSJIS();
		  } else {
		    $displayName = $mailbox;
		  }

		    
		  if ($mailbox == "INBOX") {
		    $displayName = $i18n->get("inboxFolder");
		    array_unshift($retDefault, $IMAP_INBOX);
		  } else if ($mailbox == $IMAP_SENTMAIL) {
		    $displayName =  $i18n->get("sentMailFolder");
		    $retDefault[] = $IMAP_SENTMAIL;
		    $sentMailFlag = true;
		  } else if (strncmp($mailbox, ".", 1)) {
		    $displayName = $displayName;
		    $retPersonal[] = $mailbox;
		  }
		  $mailboxDisplayNames[$mailbox] = $displayName;
		}

	} 
	if (!$sentMailFlag) {
	  //sent-mail mailbox doesn't exist
	  imap_createmailbox($connection, formatMailboxForIMAP($IMAP_SENTMAIL, $localePreference));
	  $displayname =  $i18n->get("sentMailFolder");
	  $mailboxDisplayNames[$IMAP_SENTMAIL] = $displayName;
	  $retDefault[] = $IMAP_SENTMAIL;
	} 
	
	$ret = array_merge($retDefault, $retPersonal);
	return $ret;
}

// not used anymore
function SJIS2JIS($string) {
  global $serverScriptHelper;
  $fileName = tempnam("/tmp", "webmail");

  if ($fileHandle = fopen($fileName, "w")) {
    fputs($fileHandle, $string);
    fclose($fileHandle);
    // have to chmod because non-admin users need to have permission to this
    // file
    chmod($fileName, 0666);
    $serverScriptHelper->shell("/usr/local/sbin/nkf -n $fileName", $newString);
    unlink($fileName);
    $newString = chop($newString);
    return $newString;
  } else {
    return $string;
  }

}

// not used anymore
function JIS2SJIS($string) {
  global $serverScriptHelper;
  $fileName = tempnam("/tmp", "webmail");

  if ($fileHandle = fopen($fileName, "w")) {
    fputs($fileHandle, $string);
    fclose($fileHandle);
    // have to chmod because non-admin users need to have permission to this
    // file
    chmod($fileName, 0666);
    $serverScriptHelper->shell("/usr/local/sbin/nkf -s $fileName", $newString);
    unlink($fileName);
    $newString = chop($newString);
    return $newString;
  } else {
    return $string;
  }

}
    
		
function matchQuotes($name) {
	// drop a double quote if it is at the end of a name part, and unmatched
	if (ereg("^(\"[^\"]*\")$|^([^\"]*)\"$", trim($name), $regs))
		if ($regs[1])
			return $regs[1];
		else
			return $regs[2];

	// match a double quote, even though imap drops it
	return ereg_replace("([\"])([^\"]*)$", "\\1\\2\\1", trim($name));
}


function unprintHtmlTags($string) {
  $new = str_replace("&lt;","<",$string);
  return str_replace("&gt;",">",$new);
}

function printHtmlTags($string) {
  $new = str_replace("<", "&lt;", $string);
  return str_replace(">", "&gt;", $new);
}

function throwError($error, $page = "", $vars = array()) {
  global $i18n;
  global $serverScriptHelper;
  if (!$page) {
    global $HTTP_REFERER;
    $page = ($HTTP_REFERER) ? $HTTP_REFERER : "/nav/blank.php";
  }
  $authError = new Error($error, $vars);
  print($serverScriptHelper->toHandlerHtml($page, array( $authError)));
  $serverScriptHelper->destructor();
  exit;
}


function needLogin() {
  if (!usernameExists()) {
    return true;
  } else if (!passwordExists()) {
    return true;
  } else {
    return false;
  }
}

function usernameExists() {
  global $PHP_AUTH_USER;
  global $loginName;
  global $serverScriptHelper;
  global $isMonterey;
  if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
    if ($loginName) {
      return true;
    } else {
      return false;
    }
  } else {
    if ($PHP_AUTH_USER) {
      return true;
    } else {
      return false;
    }
  }
}

function passwordExists() {
  //see if password hash cookie set, if so return true
  //DOES NOT CHECK IF IT'S A VALID PASSWORD
  global $PHP_AUTH_PW;
  global $sessionId;
  global $serverScriptHelper;
  global $isMonterey;
  if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
    if ($sessionId) {
      return true;
    } else {
      return false;
    }
  } else {
    if ($PHP_AUTH_PW) {
      return true;
    } else {
      return false;
    }
  }
}

function getUsername() {
  //return username from environment
  //return $REMOTE_USER
  //DOES NOT CHECK IF IT'S A VALID USERNAME
  global $PHP_AUTH_USER;
  global $loginName;
  global $serverScriptHelper;
  global $isMonterey;
  if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
    return $loginName;
  } else {
    return $PHP_AUTH_USER;
  }
}
function getPassword($encryptedPassword = "") {
  global $PHP_AUTH_PW;
  global $sessionId;
  global $serverScriptHelper;
  global $isMonterey;
  if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
    return $sessionId;
  } else {
    return $PHP_AUTH_PW;
  }
}

function getUserDomain($user) {
  global $serverScriptHelper;
  global $isMonterey;
  global $IMAP_SERVER;
  if ((!$isMonterey) && $serverScriptHelper->hasCCE()) {
    return $IMAP_SERVER;
  } else {
    $path = "perl getVirtUser.pl $user 2>&1";
    $email = exec($path);
    if ($email) {
      $pos = strpos($email, "@");
      return substr($email, $pos+1);
    } else {
      return;
    }
  }
}

function imapLock() {
  global $SCRIPT_NAME;
  global $imapLockFile;
  global $imapLockDescriptor;
  global $i18n;
  $trialNum = 1;
  $totalTrys = 5;  
  
  while ($trialNum <= $totalTrys) {
    if (!flock($imapLockDescriptor, LOCK_EX+LOCK_NB)) {
      sleep(1);
    } else {
      return;
    }
    $trialNum++;
  }

  print "<SCRIPT>\n";
  print "top.mainFrame.location = \"imapCannotConnect.php\";\n";
  print "</SCRIPT>\n";
  flush();
  exit;
}

function imapUnlock() {
  global $imapLockDescriptor;
  global $SCRIPT_NAME;

  $ret = flock($imapLockDescriptor, LOCK_UN);
}

function swapEmailAddresses($array, $origAddress, $replaceWithAddress, $substring = 0) {
  if (count($array) == 0 && strstr($array, $origAddress)) {
    $array = $replaceWithAddress;
  }
    
  for ($i=0;$i<count($array);$i++) {
    if ($substring && strstr($array[$i], $origAddress)) {
      $array[$i] = $replaceWithAddress;
    } else if (!$substring && ($array[$i] == $origAddress)) {
      $array[$i] = $replaceWithAddress;
    }
  }
  return $array;
}

//Obseletes unformatSubject()
function formatHeaderForDisplay($header, $charset = "") {
  $elements = imap_mime_header_decode($header);
  $returnString = "";

  for($i = 0; $i < count($elements); $i++) {
    $text = $elements[$i]->text;
    $headerCharset = strtoupper($elements[$i]->charset);

    // cannot determine charset?
    if($headerCharset == "DEFAULT")
      $headerCharset = $charset;

//LOCALE SPECIFIC

    $headerCharset = strtoupper($headerCharset);
    if($headerCharset == "ISO-2022-JP") {
      $j = new EncodingConv($text, "japanese");
      $returnString .= $j->toSJIS();
    } else if($headerCharset == "EUC_JP" || $headerCharset == "EUC-JP") {
      $j = new EncodingConv($text, "japanese");
      $returnString .= $j->toSJIS();
    } else {
    // nothing to do for SJIS, 8859-1 or 2022-CN
      $returnString .= $text;
    }
  }

  return $returnString;
}

//Obseletes formatSubject()
function formatHeaderForTransmit($header, $lang) {
  if($header == "")
	return $header;

  global $HTTP_ACCEPT_LANGUAGE;
  global $serverScriptHelper;

//LOCALE SPECIFIC
  if (ereg("^ja",$lang) || ereg("^ja",$HTTP_ACCEPT_LANGUAGE)) {
    $j = new EncodingConv($header, "japanese");
    $returnHeader = "=?ISO-2022-JP?B?" . base64_encode($j->toISO2022()) . "?=";
  }else if(ereg("^zh",$lang) || ereg("^zh",$HTTP_ACCEPT_LANGUAGE)){
    if(ereg("zh[-_]TW",$lang) || ereg("zh[-_]TW",$HTTP_ACCEPT_LANGUAGE)){
        $returnHeader = "=?BIG5?B?".base64_encode($header)."?=";
    }else{
        $returnHeader= "=?GB2312?B?".base64_encode($header)."?=";
    }
  } else {
    // decorate headers if they contain chars in dec range 128-255
    $low = chr(128);	// euro symbol
    $high = chr(255);
    if (preg_match("/[$low-$high]/",$header)) {
     $returnHeader =  "=?ISO-8859-1?B?" . base64_encode($header) . "?=";
    } else {
      $returnHeader = $header;
    }
  }
  return $returnHeader;
}


//Obseletes collateEmailList();
function formatImapEmailListForDisplay($ar) {
  global $IMAP_SERVER;
  global $serverScriptHelper;

  $addrList = "";
  for ($i=0;$i<count($ar);$i++) {
    //collate a list of 'to'
    if ($i) {
      $addrList .= "&";
    }
    
    $emailAddress = $ar[$i]->mailbox . ($ar[$i]->host=="UNKNOWN" ? "" :  "@" . $ar[$i]->host);

    if ($ar[$i]->personal) {
      $addrList .= formatHeaderForDisplay($ar[$i]->personal) . " <" . $emailAddress . ">";
    } else {
      $addrList .= $emailAddress;
    }

  }
  return $addrList;
}

//Obseletes formatAddresses()
function formatEmailListForTransmit($addresses, $lang) {
  $enc = explode("&", $addresses);
  $tmpArray = array();
  for($i=0; $i<count($enc); $i++){
    if($enc[$i] !=""){
      array_push($tmpArray, $enc[$i]); 
    }
  }
  while (list($key, $val) = each($tmpArray))
    if (strchr($val, "<")) {
      ereg("^(.*)<(.*)>.*$", $val, $regs);
      $ret[] = trim(formatHeaderForTransmit(matchQuotes($regs[1]),$lang)) . " <" . $regs[2] . ">";
      $regs = NULL;
    } else {
      $ret[] = trim($val);
      $regs = NULL;
    };
  return "&". implode("&", $ret) . "&";
}

//Obseletes formatEmailList()
//Obseletes uncollateEmailList()
function formatEmailListWithCommas($emailList) {
	$enc = explode("&", $emailList);
	$array = array();
	for($i=0; $i<count($enc); $i++){
		if($enc[$i] !=""){
			array_push($array, $enc[$i]); 
		}
}
    $value = implode(", ", $array);;
  return $value;
}

//To be used inside getImapBodyForDisplay
function formatBodyForDisplay($body, $lang) {
  global $serverScriptHelper;
  global $HTTP_ACCEPT_LANGUAGE;
//LOCALE SPECIFIC
  if (ereg("^ja",$lang) || ereg("^ja",$HTTP_ACCEPT_LANGUAGE)) {
    $j = new EncodingConv($body, "japanese");
    $returnBody = $j->toSJIS();
  } else {
    $returnBody = $body;
  }
  return $returnBody;
}

function formatBodyForTransmit($body, $lang) {
  global $serverScriptHelper;
  global $HTTP_ACCEPT_LANGUAGE;
//LOCALE SPECIFIC
  if (ereg("^ja",$lang) || ereg("^ja",$HTTP_ACCEPT_LANGUAGE)) {
    $j = new EncodingConv($body, "japanese");
    $returnBody = $j->toISO2022();
  } else {
    $returnBody = $body;
  }
  return $returnBody;
}

function formatFilenameForTransmit($filename, $lang) {
  return formatHeaderForTransmit($filename, $lang);
}

function formatFilenameForDisplay($filename) {
  return formatHeaderForDisplay($filename);
}

function formatMailboxForIMAP($mailbox, $lang) {
  global $IMAP_SERVER, $IMAP_SERVERPORT, $IMAP_MAILFOLDER;
  global $HTTP_ACCEPT_LANGUAGE;
//LOCALE SPECIFIC
  $mailbox = ereg_replace("\\.\\.", "", $mailbox);
  if ($mailbox[0] == '/') 
  	$mailbox= "." . $mailbox;
  
  if (ereg("^ja",$lang) || ereg("^ja",$HTTP_ACCEPT_LANGUAGE)) {
    $retBox = "\{$IMAP_SERVER:$IMAP_SERVERPORT}$IMAP_MAILFOLDER" . $mailbox;
  } else {
    $retBox = "\{$IMAP_SERVER:$IMAP_SERVERPORT}$IMAP_MAILFOLDER" . $mailbox;
  }
  return $retBox;
}

function formatMailboxForDisplay($mailbox, $lang) {
  global $HTTP_ACCEPT_LANGUAGE;
  $retBox = my_imap_utf7_decode($mailbox);
  $retBox = substr( $retBox, strlen("\{$IMAP_SERVER:$IMAP_SERVERPORT}$IMAP_MAILFOLDER"), strlen($retBox)); 
//LOCALE SPECIFIC
  if (ereg("^ja",$lang) || ereg("^ja",$HTTP_ACCEPT_LANGUAGE)) {
    $j = new EncodingConv($retBox, "japanese");
    $retBox = $j->toSJIS();
  } else {
    $retBox = $mailbox;
  }
  return $retBox;
}

function escapeForURL($string, $lang) {
  global $HTTP_ACCEPT_LANGUAGE;
  $returnString = "";
//LOCALE SPECIFIC
  if (ereg("^ja",$lang) || ereg("^ja",$HTTP_ACCEPT_LANGUAGE)) {
    //Escape slashes. Only escape slash if it's not the trailing byte of a double-byte japanese 5c character
    while (($slashPos = strpos($string, "\\")) !== false) {
      $returnString .= substr($string, 0, $slashPos+1);
      if ($slashPos == 0) {
	$returnString .= "\\";
      } else if ($slashPos > 0 && !is5cCharacter(substr($string, $slashPos-1, 2))) {
	$returnString .= "\\";
      }
      $string = substr($string, $slashPos+1);
    }
    $returnString .= $string;
    //Escape double quotes
    $returnString = str_replace("\"", "\\\"", $returnString); 
  } else {
    //If not Japanese, simply escape slashes and double quotes as normal
    $returnString = str_replace("\\", "\\\\", $string); 
    $returnString = str_replace("\"", "\\\"", $returnString); 
  }
  //Nasty hack so that if the last character is a slash (which may be because it's the trailing byte of a 5c character), then it won't break javascript quoting of arguments. I.e. if string = abc\, then "string" = "abc\" . Note the last quote is escaped by the backslash. Ugh.
  if (substr($returnString, -1) == "\\") {
    $returnString .= " ";
  }
  return $returnString;
}  

function unescapeFromURL($string) {
  $returnString = $string;
  if (substr($returnString, -2) == "\\ ") {
    $returnString = substr($returnString, 0, -1);
  }
  $returnString = str_replace("\\\"", "\"", $returnString);
  $returnString = str_replace("\\\\", "\\", $returnString);
  return $returnString;
}  

//Takes a double-byte character
function is5cCharacter($character) {
  if (strlen($character) != 2) {
    exit;
  } 
  $firstByte = substr($character, 0, 1);
  $secondByte = substr($character, 1, 1);
  
  $firstVal = hexdec(bin2hex($firstByte));
  $secondVal = hexdec(bin2hex($secondByte));
  $range1low = hexdec("81");
  $range1hi = hexdec("9f");
  $range2low = hexdec("e0");
  $range2hi = hexdec("ef");
  $fiveC= hexdec("5c");
  
  if (($secondVal == $fiveC) && 
      (($firstVal >= $range1low && $firstVal <= $range1hi) ||
       ($firstVal >= $range2low && $firstVal <= $range2hi))) {
    return true;
  } else {
    return false;
  }
}


// description: get message body from IMAP and encode it into the right
//     encoding
// param: connection: an IMAP stream
// param: msgnum: the number of the message in question
// param: part: the message part
// param: partNo: the message part number
// returns: message body
function getBody($connection, $msgnum, $part, $partNo) {
  // get charset by parsing the header
  // we should be able to get it if it is not a multipart message
  // do not look for CHARSET from $de_part->properties because $de_part
  // is from imap_fetchstructure() which uses IMAP command
  // "a fetch <id> FULL". The IMAP server usually wrongly returns
  // "US-ASCII" for a non-multipart message
  $charset = getHeaderCharset($connection, $msgnum);
  if($charset == "") {
    $parameters = $part->parameters;
    for ($i = 0; $i < count($parameters); $i++)
      if ($parameters[$i]->attribute == "CHARSET")
	$charset = $parameters[$i]->value;
  }
  $charset = strtoupper($charset);
  $charset=ereg_replace('"',"",$charset);

  $body = imap_fetchbody($connection, $msgnum, $partNo);

  if (get_mime_encoding($part) == "qprint")
    $body = imap_qprint($body);

  // get mime type
  $mimeType = get_mime_type($part);

  // get subtype
  $subtype = strtoupper($part->subtype);
//LOCALE SPECIFIC
  if ($subtype == "PLAIN" && ($charset == "ISO-2022-JP"
                          || $charset == "EUC_JP"
                          || $charset == "EUC-JP")) {
    $j = new EncodingConv($body, "japanese");
    return $j->toSJIS();
  } else {
    return $body;
  }
}

// description: get a list of email address strings from header objects
// param: an array of header objects from imap_headerinfo()
// param: charset: the charset of the headers
// returns: an array of email addresses
function getAddresses($headerObjs, $charset) {
  $addresses = array();

  for ($i = 0; $i < count($headerObjs); $i++) {
    $headerObj = $headerObjs[$i];

    // add the address part
    $address = $headerObj->mailbox;
    if($headerObj->host != "UNKNOWN")
      $address .= "@" . $headerObj->host;

    // add the name part
    if ($headerObj->personal != "")
      $address = formatHeaderForDisplay($headerObj->personal, $charset) . " <" . $address . ">";

    $addresses[] = $address;
  }

  return $addresses;
}

// description: get the charset declared in the header
// returns: a charset string or "" if charset is not declared
function getHeaderCharset($connection, $msgnum) {
  $headerString = imap_fetchheader($connection, $msgnum);
  $headers = explode("\n", $headerString);
    for($i = 0; $i < count($headers); $i++)
      if(preg_match("/charset=(.*)\r/", $headers[$i], $matches))
	return $matches[1];

  return "";
}

/***************************************************************
 Functions to convert UTF
					Coded by Kazuhiko Iwama
					iwama@cyberwork.co.jp

 ***************************************************************/

/***  utf16to8() : UTF-16 to UTF-8 ***/
  function utf16to8 ( $utf16 ){
    $utf8 = "";
    $array = unpack("C*", $utf16);
    $l = count($array);
    for ($i = 1 ; $i <= $l ; $i+=2 ){
      $c1 = $array[$i];
      if ( $i+1 <= $l ){ 
        $c2 = $array[$i+1];
      } else {
        $c2 = 0;
      }
      $u1 = 0xE0 | (( $c1 >> 4 ) & 0x0f );
      $u2 = 0x80 | (( $c1 << 2 ) & 0x3c ) | (( $c2 >> 6 ) & 0x03 );
      $u3 = 0x80 | (( $c2      ) & 0x3f );
      $utf8 .= pack("CCC", $u1, $u2, $u3);
    }
    return $utf8;
  };

/***  utf8to16() : UTF-8 to UTF-16 ***/
  function utf8to16 ( $utf8 ){
    $utf16 = "";
    $array= unpack("C*", "$utf8");
    $l = count($array);
    $i = 1;
    while ($i <= $l){
      $c1 = $array[$i++];
      $m = ($c1 & 0xE0);
      if ( ($c1 & 0x80) == 0x00 ){
        $u1 = 0;
        $u2 = $c1;
      } else if ( ($c1 & 0xE0) == 0xC0 ){
        $c2 = $array[$i++];
        $u1 = (( $c1 >> 2 ) & 0x07 )                 & 0x07;
        $u2 = (( $c1 << 6 ) & 0xC0 ) | ( $c2 & 0x3F );
      } else if ( ($c1 & 0xF0) == 0xE0 ){
        $c2 = $array[$i++];
        $c3 = $array[$i++];
        $u1 = (( $c1 << 4 ) & 0xF0 ) | (( $c2 >> 2 ) & 0x0F );
        $u2 = (( $c2 << 6 ) & 0xC0 ) | (  $c3        & 0x3F );
      }
      $utf16 .= pack("CC", $u1, $u2);
    }
    return $utf16;
  };

/***  decode_imap_utf7() :  UTF-7 to  UTF-8 ***/
  function my_imap_utf7_decode ( $folder ){
    $retval = "";
    $p = strpos( $folder, '&' );
    if ( $p === false ){
      $retval = $folder;
    } else { 
      while ( $p !== false ){
        if ( $p > 0 ){
          $retval .= substr($folder, 0, $p);
        }
        $folder = substr($folder, $p +1);
        $p = strpos( $folder, '-' );
        if ( $p === false ){
          $mbstr = $folder;
          $folder = "";
        } else {
          $mbstr = "";
          if ( $p == 0 ){
            $retval .= "&";
          } else if ( $p > 0 ){
            $mbstr = substr($folder, 0, $p);
          }
          $folder = substr($folder, $p +1);
        }
        $retval .= utf16to8(imap_base64( ereg_replace(",", "/",$mbstr) ));
        $p = strpos( $folder, '&' );
      }
      $retval .= $folder;
    }
    return $retval;
  };

/***  encode_imap_utf7() : UTF-8 to  UTF-7  ***/
  function my_imap_utf7_encode ( $folder ){
    $retval = "";
    $utf16 = utf8to16( $folder );
    $array = unpack( "n*", $utf16 );
    $l = count($array);
    $i = 1;
    while ($i <= $l){
      $c = $array[$i++];
      if ( $c >= 0x20 && $c <= 0x7e ){
        if ( $c == 0x26 ){
          $retval .= "&-";
        } else {  
          $retval .= pack("C", $c);
        }
      } else {
        $mbstr = pack("n", $c);
        $c = $array[$i];
        while ( ($c < 0x20 || $c > 0x7e) && $i<=$l ){
          $mbstr .= pack("n", $array[$i++]);
          if ( $i > $l ){
            break;
          }
          $c = $array[$i];
        };
        $mbstr = imap_binary( $mbstr );
        $mbstr = ereg_replace( '/', ',', $mbstr );
        $mbstr = ereg_replace( '=', '', $mbstr );
        $retval .= "&" . chop($mbstr) . "-";
      }
    }
    return $retval;
  }

  function part_is_browsable($part) {
    if ($part->type==TYPETEXT || $part->type==TYPEIMAGE) {
      return 1;
    }
    return 0;
  }


 

?>
