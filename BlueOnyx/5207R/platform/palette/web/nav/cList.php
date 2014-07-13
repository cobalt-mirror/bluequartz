<?php
// Author: Kevin K.M. Chiu
// $Id: cList.php

// description:
// This is the base page of the collapsible list navigation system.
//
// usage:
// This page can be configured with several URL encoded variables. They are:
// root
//   Mandatory parameter to specify what is the root of the collapsible list.
//   It is a menuItem ID in string
// isTabbed
//   Can be "true" or "false". Optional and true by default. If false, all menu
//   items on the tab are shifted to the collapsible list.
// commFrame
//   An URL to specify the location of the communication frame. Optional. Good
//   to be used to load in Javascript. The page can define an
//   onSiteMapLoad(siteMap) function if it edits the site map.
// goto
//   The item id that should be initially selected. Optional.
// title
//    The title of the window
// Global variables on this page are:
// siteMap
//   This object has all menuItem objects defined in menu XML files associated
//   with it. For example, an item defined in a menu XML with ID "node" is
//   accessible in Javascript as siteMap.node.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$stylist = $serverScriptHelper->getStylist();
$style = $stylist->getStyle("cListNavigation");
$i18n = $serverScriptHelper->getI18n("palette");

// log activity if necessary
$system = new System();
$isLogUi = ($system->getConfig("logPath") != "") ? "true" : "false";

if(!isset($title)) {
    global $HTTP_HOST;
    preg_match("/([^:]+):?.*/", $HTTP_HOST, $matches);
    $title_hostname = $matches[1] ? $matches[1] : `/bin/hostname --fqdn`;
    $title = $i18n->getHtml("navigationTitle", "", array("hostName" => $title_hostname, "userName" => $serverScriptHelper->getLoginName()));
} else {
    $title = urldecode($title);
}


// make sure no caching on IE
header("cache-control: no-cache");
$lang=$i18n->getLocales();
header("Content-language: $lang[0]");

//make really sure the browser knows what we are giving it.
$charset = "UTF-8";

header("Content-type: text/html; charset=$charset");

?>

<HTML>
<HEAD>
<META HTTP-EQUIV="expires" CONTENT="-1">
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<meta http-equiv="content-type" content="text/html; charset=<?php print($charset)?>">
<TITLE><?php print($title);?></TITLE>
<script language="javascript" type="text/javascript">
if (self != top) {
    if (document.images)
        top.location.replace(window.location.href);
    else
        top.location.href = window.location.href;
}
</script>
<SCRIPT LANGUAGE="javascript">
// global
var siteMap;
var isLogUi = <?php print($isLogUi) ?>;

function init() {
  // get root from parameter
  var root = "<?php print($root); ?>";

  // get tab configuration from parameter
  var isTabbed = true;
  if("<?php print($isTabbed); ?>" == "false")
    isTabbed = false;

  // fix for Safari 3.0 missing menus:
  if (!top.code) top.code = frames[4];

  // build site map
  siteMap = new Object();
  top.siteMap = siteMap;
<?php
include_once("SiteMap.php");
$siteMap = new SiteMap();
print($siteMap->toJavascript($serverScriptHelper->getAccessRights(), "siteMap.", $serverScriptHelper->getLocalePreference()));
?>
  if(commFrame.onSiteMapLoad != null)
    commFrame.onSiteMapLoad(siteMap);

  // set frames
  code.tab_setFrame("content", mainFrame);
  code.tab_setFrame("tab", controlFrame);
  code.cList_setFrame("content", mainFrame);
  code.cList_setFrame("list", navFrame);
  code.info_setFrame(infoFrame);

<?php
  // setup style
  print($serverScriptHelper->getTabStyleJavascript());
  print($serverScriptHelper->getCListStyleJavascript());
  print($serverScriptHelper->getInfoStyleJavascript());
?>

  // initialize info frame
  code.info_clear();

  // the goto parameter specifies the first item to display
  var startNode = "<?php print($goto); ?>";

  // repaint here
  if(!isTabbed) {
    // put everything under collapsible list
    code.cList_setRoot(siteMap[root]);

    if ( startNode ) 
      code.cList_selectPath( startNode );

    code.cList_repaint(true);
    code.tab_repaint();
  }
  else {
    code.tab_setRoot(siteMap[root]);

    if ( startNode ) {
      code.tab_selectPath( startNode );
      code.cList_selectPath( startNode );
    } else {
      // select default
      tabItems = siteMap[root].getItems();
      if(tabItems.length > 0)
        code.tab_select(tabItems[0].getId(), true);
      else
        code.tab_repaint();
    }
  }

  setInterval("code.cList_check_username('<?php echo $HTTP_COOKIE_VARS["loginName"] ?>')", 10000);

  // start scheduler in case there are jobs pending
  code.scheduler_doJob();
}
</SCRIPT>
<META HTTP-EQUIV="Pragma" CONTENT="no-cache">
<meta http-equiv="content-type" content="text/html; charset=<?php print($charset)?>">
</HEAD>

<FRAMESET ROWS="<?php print($style->getProperty("tabHeight")); ?>,*,<?php print($style->getProperty("infoHeight")); ?>,0,0" BORDER="0" FRAMEBORDER="no" FRAMESPACING="0" onLoad="init()">
  <FRAME SRC="blank.html" NAME="controlFrame" FRAMEBORDER="no" MARGINWIDTH="0" SCROLLING="no">
  <FRAMESET COLS="<?php print($style->getProperty("collapsibleListWidth")); ?>,*" ID="set1" BORDER="0" FRAMEBORDER="no" FRAMESPACING="0">
    <FRAME SRC="blank.html" NAME="navFrame" FRAMEBORDER="no">
    <FRAME SRC="blank.php" NAME="mainFrame" FRAMEBORDER="no">
  </FRAMESET>
  <FRAME SRC="blank.html" NAME="infoFrame" FRAMEBORDER="no" MARGINWIDTH="0">
  <FRAME SRC="jsLibrary.php" NAME="code" FRAMEBORDER="no" MARGINWIDTH="0" SCROLLING="no">
  <FRAME SRC="<?php print($commFrame ? $commFrame : "blank.html"); ?>" NAME="commFrame" FRAMEBORDER="no" MARGINWIDTH="0" SCROLLING="no">
</FRAMESET>

</HTML>
<?php

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