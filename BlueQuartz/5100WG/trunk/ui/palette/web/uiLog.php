<HTML>
<BODY>
<!-- Logged:
<?php
// Author: Kevin K.M. Chiu
// Copyright 2001, Cobalt Networks.  All rights reserved.
// $Id: uiLog.php 236 2003-09-10 07:50:31Z shibuya $

include("ServerScriptHelper.php");
include("System.php");

// use helper here to make sure user is authenticated
// we do not want malicious people to fill the log
$serverScriptHelper = new ServerScriptHelper();

// nothing to log?
if($log == "")
  return;

// find out where the log file is
$system = new System();
$logPath = $system->getConfig("logPath");

// break the log into lines
$lines = explode("\n", $log);

// open log file to append to
$file = fopen($logPath, "a");

// write to log file
for($i = 0; $i < count($lines); $i++) {
  // empty line?
  if($lines[$i] == "")
    continue;

  // element 0 is timestamp, 1 is event, 2 is target, 3 is id, 4 onwards are
  // parameters
  $elements = explode("\t", $lines[$i]);

  // the elements to write into the log file
  $logElements = array();

  // make timestamp RFC822 compliant
  // timestamp from Javascript is in milliseconds
  $logElements[0] = date("D j M Y G:i:s T", $elements[0]/1000);
  $logElements[1] = $serverScriptHelper->getLoginName();
  for($j = 1; $j < count($elements); $j++)
    $logElements[] = $elements[$j];

  $log = implode("\t", $logElements)."\n";

  // write to file
  fwrite($file, $log);

  // print to front-end as well
  print($log);
}

// don't forget to close files
fclose($file);

$serverScriptHelper->destructor();
?>
-->
</BODY>
</HTML>
<?php
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
