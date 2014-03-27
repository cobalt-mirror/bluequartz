<?php
// Author: Kevin K.M. Chiu & Michael Stauber
// $Id: personalEmailHandler.php

include_once("ServerScriptHelper.php");
include_once("BXEncoding.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();

$errors = array();

// boolean cce values are only 0 or 1
if($autoResponderField) {
  $autoResponderField = "1";
} else {
  $autoResponderField = "0";
}
if($forwardEnableField) {
  $forwardEnableField = "1";
} else {
  $forwardEnableField = "0";
}

if ($_autoRespondStartDate_amPm == "PM") { 
  $_autoRespondStartDate_hour = $_autoRespondStartDate_hour + 12; 
 } 

if ($_autoRespondStopDate_amPm == "PM") { 
  $_autoRespondStopDate_hour = $_autoRespondStopDate_hour + 12; 
 } 

$vacationMsgStart = mktime($_autoRespondStartDate_hour, $_autoRespondStartDate_minute, 
         $_autoRespondStartDate_second, $_autoRespondStartDate_month, 
         $_autoRespondStartDate_day, $_autoRespondStartDate_year); 

$vacationMsgStop = mktime($_autoRespondStopDate_hour, $_autoRespondStopDate_minute, 
        $_autoRespondStopDate_second, $_autoRespondStopDate_month, 
        $_autoRespondStopDate_day, $_autoRespondStopDate_year); 

if (($vacationMsgStop - $vacationMsgStart) < 0) { 
  $vacationMsgStop = $oldStop; 
  
  $error_msg = "[[base-user.invalidVacationDate]]"; 
  $errors[] = new Error($error_msg); 
 } 

if ($locale != "ja_JP") {
  $vacMsg = BXEncoding::toUTF8($autoResponderMessageField);
}
else {
  $vacMsg = EncodingConv::doJapanese($autoResponderMessageField, "euc");
}

$cceClient->setObject("User", array(
  "forwardEnable" => $forwardEnableField, 
  "forwardEmail" => $forwardEmailField, 
  "forwardSave" => $forwardSaveField,
  "vacationOn" => $autoResponderField, 
  "vacationMsg" => $vacMsg, 
  "vacationMsgStart" => $vacationMsgStart, 
  "vacationMsgStop" =>$vacationMsgStop),  
  "Email", 
  array("name" => $serverScriptHelper->getLoginName()));
$errors = array_merge($cceClient->errors(), $errors);

print($serverScriptHelper->toHandlerHtml("personalEmail.php", $errors));

$serverScriptHelper->destructor();

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
Copyright (c) 2003 Sun Microsystems, Inc. 
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>