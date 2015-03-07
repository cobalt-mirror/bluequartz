<?php
// Author: Kevin K.M. Chiu
// $Id: emailHandler.php

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();

// Only 'serverEmail' should be here:
if (!$serverScriptHelper->getAllowed('serverEmail')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();

$queueTimeMap = array("queue0" => "immediate", "queue15" => "quarter-hourly", "queue30" => "half-hourly", "queue60" => "hourly", "queue360" => "quarter-daily", "queue1440" => "daily");

$maxRecipientsPerMessageMap = 
    array(
	"unlimited" => "0", 
        "5" => "5", 
        "10" => "10", 
        "15" => "15", 
        "20" => "20", 
        "25" => "25", 
        "50" => "50", 
        "75" => "75", 
        "100" => "100", 
        "125" => "125", 
        "150" => "150", 
        "175" => "175", 
        "200" => "200"
    );

// empty maximum message size means no limits
// convert MB to KB
$max = $maxEmailSizeField ? $maxEmailSizeField*1024 : "";

if ($hideHeaders != "1") {
    $hideHeaders = "0";
}

$cceClient->setObject("System", 
  array(
    "enableSMTP" => $enableServersField, 
    "enableSMTPS" => $enableSMTPSField,
    "enableSMTPAuth" => $enableSMTPAuthField,
    "enableSubmissionPort" => $enableSubmissionPortField,
    "enableImap" => $enableImapField, 
    "enableImaps" => $enableImapsField,
    "enablePop" => $enablePopField, 
    "enableZpush" => $enableZpushField, 
    "enablePops" => $enablePopsField,
    "popRelay" => $popRelayField, 
    "delayChecks" => $delayChecksField, 
    "enablepopRelay" => $popRelayField, 
    "queueTime" => $queueTimeMap[$queueTimeField], 
    "maxMessageSize" => $max, 
    "maxRecipientsPerMessage" => $maxRecipientsPerMessageMap[$maxRecipientsPerMessageField], 
    "relayFor" => $relayField, 
    "acceptFor" => $receiveField, 
    "deniedUsers" => $blockUserField, 
    "masqAddress" => $masqAddressField,
    "smartRelay" => $smartRelayField,
    "hideHeaders" => $hideHeaders,
    "deniedHosts" => $blockHostField), 
  "Email");
  
$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/email/email.php?view=$_PagedBlock_selectedId_emailSettings", 
	$errors, "base-email"));

# disable activeMonitor for these items
$serverScriptHelper->destructor();

 /*
 Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
 Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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