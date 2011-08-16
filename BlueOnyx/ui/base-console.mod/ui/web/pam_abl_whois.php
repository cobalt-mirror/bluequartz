<?php

// Author: 
//		Michael Stauber - Stauber Multimedia Design - http://www.solarspeed.net
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright 2009 Team BlueOnyx. All rights reserved.
// Mon 10 Aug 2009 09:48:30 AM CEST
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
array_walk_recursive($stylist, create_function('&$v, $k, &$t', '$t->aFlat[] = $v;'), $objTmp);
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
$factory = $serverScriptHelper->getHtmlComponentFactory("base-console", "/base/console/pam_abl_whois.php");

// Prepare Page generation:
$page = $factory->getPage();

// Print Whois output:
print("<pre>\n");
system("/usr/bin/whois $whois");
print("</pre>\n");

// Print Footer:
print($page->toFooterHtml()); 

?>
