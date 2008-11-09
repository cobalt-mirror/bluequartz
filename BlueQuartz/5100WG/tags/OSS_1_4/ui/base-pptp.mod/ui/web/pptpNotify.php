<?

/* This page tells the adminstrator to notify his users that they need to set
 * their secrets in the remote access page */

include_once("ServerScriptHelper.php");
include_once("uifc/HtmlComponent.php");

class MyHtmlComponent extends HtmlComponent {
  var $users;
  function setUsers(&$users) {
    $this->users = $users; 
  }

  function toHtml() {
    $page = $this->getPage();
    $i18n = $page->getI18n();

    /* touch up users */
    $usertext = "<UL>";
    foreach($this->users as $user) {
      $usertext .= "<LI>$user</LI>";
    }
    $usertext .= "</UL>";
    return $i18n->interpolate("[[base-pptp.pptp_notify_text]]", array(users=>$usertext));
  }
}

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-pptp");

$page = $factory->getPage();
$block = $factory->getSimpleBlock("");                  

$i18n = $serverScriptHelper->getI18n("base-pptp");


$label = new MyHtmlComponent($page);
$label->setUsers(stringToArray($users));
$block->addHtmlComponent($label);

$yesButton = $factory->getButton("/base/pptp/pptpSendEmail.php?users=".urlencode($users), "pptp_notify_yes");
$noButton = $factory->getButton("/base/pptp/pptp.php", "pptp_notify_no");

print $page->toHeaderHtml();
print "<br><table border=0 width=400><tr><td>";
print "<div style=\"font-size:20px;font-weight:bold\">";
printf($i18n->get("pptp_notify_header"));
print "</div>";    
print $block->toHtml();
print "</td></tr><tr><td align=right>";
print "<br><table border=0><tr><td valign=middle>";
print $noButton->toHtml();
print "</td><td valign=middle>";
print $yesButton->toHtml(); 
print "</td></tr></table>"; 
print "</td></tr></table>"; 
print $page->toFooterHtml();
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

