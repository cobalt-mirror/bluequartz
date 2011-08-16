<?php

// Author: 
//		Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Fri 03 Jul 2009 02:25:29 PM CEST
//

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-console");

// Only adminUser should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

// Ok, this is something for a war crimes tribunal: 
// In order to get our custom JavaScripts into the page (instead of the default ones!) we
// have to build the page headers ourselves:

print("<HTML>\n");
print("<HEAD>\n");
print("<META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">\n");
print("<META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">\n");
print("</HEAD>\n");

// But worse: We have to determine the page style all by ourselves as well AND need to push that out::

$stylist = $serverScriptHelper->getStylist();
$objTmp = (object) array('aFlat' => array());
array_walk_recursive($stylist, create_function('$v, $k, $t', '$t->aFlat[] = $v;'), $objTmp);
$stuff = $objTmp->aFlat;

// Include stylehack.php
include_once("stylehack.php");

$logLoad = " return";
$onLoad = "true";
$textStyleStr = "font-family:" . $stuff[9] . ";font-size:" . $stuff[10];
$backgroundStyleStr = "background-color:$background_color;background-image:url($background_image);background-repeat:no-repeat;background-attachment:fixed;";
print("<BODY ALINK=\"$aLinkColor\" onLoad=\"$logLoad $onLoad\" $logUnload STYLE=\"$backgroundStyleStr\">\n");
print("<FONT STYLE=\"$textStyleStr\">\n");

// On with the show:

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-console");

// Prepare Page generation:
$page = $factory->getPage();

// menu_include.php
$selected="/var/log/admserv/adm_error";
include_once("menu_include.php");

?>
<script type="text/javascript" src=".logtail/ajax.js"> </script>
<script type="text/javascript" src=".logtail/adm_e.js"> </script>

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<button onclick="getLog('start');">Start Log</button><button onclick="stopTail();">Stop Log</button>
<br><br>
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<b>/var/log/admserv/adm_error:</b>
<div id="log" style="border:solid 1px #dddddd; margin-left:25px; font-size:12px;
padding-left:5px; padding-right:10px; padding-top:10px; padding-bottom:20px;
margin-top:10px; margin-bottom:10px; width:90%; text-align:left;">
This is the Log Viewer. To begin viewing the log live in this window, click Start Log. To stop the window refreshes, click Stop Log.
</div>
<p></p>

<?

// Print Footer:
print($page->toFooterHtml()); 

?>
