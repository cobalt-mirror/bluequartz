<?php
// Author: Kevin K.M. Chiu & Michael Stauber
// $Id: personalEmail.php

include_once("ServerScriptHelper.php");
include_once("uifc/PagedBlock.php");
include_once("BXEncoding.php");

$serverScriptHelper = new ServerScriptHelper();
$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-user", "personalEmailHandler.php");
$loginName = $serverScriptHelper->getLoginName();

// get setting
$userEmail = $cceClient->getObject("User", array("name" => $loginName), "Email");
$User = $cceClient->getObject("User", array("name" => $loginName), "");

$page = $factory->getPage();

$block = new PagedBlock($page, "emailSettingsFor", $factory->getLabel("emailSettingsFor", false, array("userName" => $loginName)));
$block->processErrors($serverScriptHelper->getErrors());

$forwardEnable = $factory->getOption("forwardEnable", $userEmail["forwardEnable"]);
$forwardEnable->addFormField(
  $factory->getEmailAddressList("forwardEmailField", $userEmail["forwardEmail"]),
  $factory->getLabel("forwardEmailField")
);
$forwardEnable->addFormField(
  $factory->getBoolean("forwardSaveField", $userEmail["forwardSave"]),
  $factory->getLabel("forwardSaveField")
);

$forward = $factory->getMultiChoice("forwardEnableField");
$forward->addOption($forwardEnable);
$block->addFormField($forward, $factory->getLabel("forwardEnableField"));

$autoResponder = $factory->getMultiChoice("autoResponderField");
$enableAutoResponder = $factory->getOption("enableAutoResponderField", $userEmail["vacationOn"]);

if(!$userEmail["vacationMsgStart"]) { 
  $start = time(); 
  $oldStart = time(); 
 } else { 
  $start = $userEmail["vacationMsgStart"]; 
  $oldStart = $userEmail["vacationMsgStop"]; 
 } 

if(!$userEmail["vacationMsgStop"]) { 
  $stop = time(); 
  $oldStop = time(); 
 } else { 
  $stop = $userEmail["vacationMsgStop"]; 
  $oldStop = $userEmail["vacationMsgStop"]; 
 } 

$autoRespondStartDate = $factory->getTimeStamp("autoRespondStartDate", $start, "datetime"); 
$enableAutoResponder->addFormField($factory->getTimeStamp("oldStart", $oldStart, "time", "")); 
   
$autoRespondStopDate = $factory->getTimeStamp("autoRespondStopDate", $stop, "datetime"); 
$enableAutoResponder->addFormField($factory->getTimeStamp("oldStop", $oldStop, "time", "")); 

$enableAutoResponder->addFormField($autoRespondStartDate, $factory->getLabel("autoRespondStartDate")); 
$enableAutoResponder->addFormField($autoRespondStopDate, $factory->getLabel("autoRespondStopDate")); 

$enableAutoResponder->addFormField(
  $factory->getTextBlock("autoResponderMessageField", I18n::Utf8Encode($userEmail["vacationMsg"])),
  $factory->getLabel("autoResponderMessageField")
);

$autoResponder->addOption($enableAutoResponder);
$block->addFormField($autoResponder, $factory->getLabel("autoResponderField"));

$block->addFormField(
  $factory->getTextField("locale", $User['localePreference'], ""),
  $factory->getLabel("locale")
);

$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();
?>
<?php print($page->toHeaderHtml()); ?>

<?php print($block->toHtml()); ?>

<?php print($page->toFooterHtml());


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