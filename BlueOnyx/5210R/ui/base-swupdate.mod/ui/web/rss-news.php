<?php

// Author: Michael Stauber
// Copyright 2006-2012, Stauber Multimedia Design. All rights reserved.
// Copyright 2008-2012, Team BlueOnyx. All rights reserved.

include_once("ServerScriptHelper.php");
include_once("uifc/ImageButton.php");

$serverScriptHelper = new ServerScriptHelper();

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-yum");
$i18n = $serverScriptHelper->getI18n("base-yum");

// Start Output generation:
$page = $factory->getPage();
print($page->toHeaderHtml());

// Location (URL) of the RSS feed:
$rsslocation = 'http://www.blueonyx.it/index.php?mact=CGFeedMaker,cntnt01,default,0&cntnt01feed=BlueOnyx-News&cntnt01showtemplate=false';

// Check if we are online:
if (areWeOnline($rsslocation)) {
    $online = "1";
}
else {
   $online = "0";
}

if ($online == "1") {

    // Process the RSS feed:
    getRssfeed($rsslocation,"BlueOnyx News","auto",50,3);

    // News are now stored in this format:
    //
    // $GLOBALS["_bx_title"] : Titles
    // $GLOBALS["_bx_date"]  : Date
    // $GLOBALS["_bx_desc"]  : Short description
    // $GLOBALS["_bx_link"]  : Link

    if ($GLOBALS["_bx_title"] == "n/a") {
	// Although we can establish a connection to www.blueonyx.it, the RSS feed did not return expected results:
	$scrollList = $factory->getScrollList("[[base-yum.RSSnewsTitle]]", array("ErrorMSG"));
	$scrollList->setAlignments(array("left"));
	$scrollList->setSortEnabled(false);
	$scrollList->setWidth("500");
	$scrollList->addEntry(array(
    	    $factory->getTextField("", $i18n->get('[[base-yum.ErrorMSGdesc]]'), "r")
	));
	print($scrollList->toHtml());
	print($page->toFooterHtml());
	$serverScriptHelper->destructor();
	exit;
    }

    // General parameters for the scroll list:
    $scrollList = $factory->getScrollList("[[base-yum.RSSnewsTitle]]", array("title", "desc", "date", "internal", "link"));
    $scrollList->setAlignments(array("left", "left", "center", "center", "center"));
    $scrollList->setSortEnabled(false);
    $scrollList->setColumnWidths(array("250", "*", "120", "25", "25"));
    $scrollList->setWidth("100%");


    // Count number of news-entries:
    $bx_num = count($GLOBALS["_bx_title"]);

    // Build multidimensional array of our news:
    $news = array($GLOBALS["_bx_title"], $GLOBALS["_bx_desc"], $GLOBALS["_bx_date"], $GLOBALS["_bx_link"]);

    // Loop through array $news and extract the news to populate the scroll list rows:
    $num = "0";
    while ($num < $bx_num) {

	// Create the image link button for the external news article URL:
	preg_match_all("/articleid=(.*)&(.*)/Uism", $news[3][$num], $article_id);
	$article = $article_id[1][0];

	$linkInternal = $factory->getDetailButton("javascript: location='/base/swupdate/rss-article.php?id=$article'; top.code.flow_showNavigation(false)");

	//$linkInternal = new ImageButton($page, $news[3][$num], "/libImage/visitWebsite.gif", "openPdf", "openURL_help");
	$linkInternal->setTarget('_self');

	// Create the image link button for the external news article URL:
	$linkExternal = new ImageButton($page, $news[3][$num], "/libImage/visitWebsite.gif", "openPdf", "openURL_help");
	$linkExternal->setTarget('_self');

	// Populate the scroll list rows:
	$scrollList->addEntry(array(
	    $factory->getTextField("", $news[0][$num], "r"),
	    $factory->getTextField("", $news[1][$num], "r"),
	    $factory->getTextField("", $news[2][$num], "r"),
	    $linkInternal,
	    $linkExternal
        ));
	$num++;
    }

    print($scrollList->toHtml());

}
else {

    // Connection to www.blueonyx.it not possible. Show error message:
    $scrollList = $factory->getScrollList("[[base-yum.RSSnewsTitle]]", array("ErrorMSG"));
    $scrollList->setAlignments(array("left"));
    $scrollList->setSortEnabled(false);
    $scrollList->setWidth("500");

    $scrollList->addEntry(array(
        $factory->getTextField("", $i18n->get('[[base-yum.ErrorMSGdesc]]'), "r")
    ));

    print($scrollList->toHtml());

}

// Finish the rest of the page generation:
print($page->toFooterHtml());
$serverScriptHelper->destructor();

function getRssfeed($rssfeed, $cssclass="", $encode="auto", $howmany=10, $mode=0) {
	// $encode e[".*"; "no"; "auto"]

	// $mode e[0; 1; 2; 3]:
	// 0 = only titel and link of the items
	// 1 = Titel and link
	// 2 = Titel, link and description
	// 3 = 1 & 2
	
	$bx_title = array();
	$bx_date = array();
	$bx_desc = array();
	$bx_link = array();
    
	// Pull the RSS feed:
	$data = get_data($rssfeed);
	if(strpos($data,"</item>") > 0)
	{
		preg_match_all("/<item.*>(.+)<\/item>/Uism", $data, $items);
		$atom = 0;
	}
	elseif(strpos($data,"</entry>") > 0)
	{
		preg_match_all("/<entry.*>(.+)<\/entry>/Uism", $data, $items);
		$atom = 1;
	}
	
	// Encoding:
	if($encode == "auto")
	{
		preg_match("/<?xml.*encoding=\"(.+)\".*?>/Uism", $data, $encodingarray);
		$encoding = $encodingarray[1];
	}
	else
	{$encoding = $encode;}
	
	// Titel and link:
	if($mode == 1 || $mode == 3)
	{
		if(strpos($data,"</item>") > 0)
		{
			$data = preg_replace("/<item.*>(.+)<\/item>/Uism", '', $data);
		}
		else
		{
			$data = preg_replace("/<entry.*>(.+)<\/entry>/Uism", '', $data);
		}
		preg_match("/<title.*>(.+)<\/title>/Uism", $data, $channeltitle);
		if($atom == 0)
		{
			preg_match("/<link>(.+)<\/link>/Uism", $data, $channellink);
		}
		elseif($atom == 1)
		{
			preg_match("/<link.*alternate.*text\/html.*href=[\"\'](.+)[\"\'].*\/>/Uism", $data, $channellink);
		}

		$channeltitle = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $channeltitle);
		$channellink = preg_replace('/<!\[CDATA\[(.+)\]\]>/Uism', '$1', $channellink);
	}
	// Check if we get multiple news items back. If not, a proxy or a badly configured router may be interfering:
	$counter = count ($items);
	if ($counter) {
	    // Titel, link and description of the news items:
	    foreach ($items[1] as $item) {
		preg_match("/<title.*>(.+)<\/title>/Uism", $item, $title);
		if($atom == 0)
		{
			preg_match("/<link>(.+)<\/link>/Uism", $item, $link);
		}
		elseif($atom == 1)
		{
			preg_match("/<link.*alternate.*text\/html.*href=[\"\'](.+)[\"\'].*\/>/Uism", $item, $link);
		}
		
		if($atom == 0)
		{
			preg_match("/<description>(.*)<\/description>/Uism", $item, $description);
		}
		elseif($atom == 1)
		{
			preg_match("/<summary.*>(.*)<\/summary>/Uism", $item, $description);
		}

		preg_match("/<pubDate>(.*)-(.*)<\/pubDate>/Uism", $item, $pubDate);

		$bx_title[] = $title[1];
		$bx_date[] = $pubDate[1];
		$bx_desc[] = $description[1];
		$bx_link[] = $link[1];

		if ($howmany-- <= 1) break;
	    }
	    $GLOBALS["_bx_title"] = $bx_title;
	    $GLOBALS["_bx_date"] = $bx_date;
	    $GLOBALS["_bx_desc"] = $bx_desc;
	    $GLOBALS["_bx_link"] = $bx_link;
	}
	else {
	    // Did not receive expected results. Set bx_title to something we can catch and process:
	    $GLOBALS["_bx_title"] = "n/a";
	}
}

function areWeOnline($domain) {
    // Check to see if we're online and if the desired URL is reachable.
    // Returns true, if URL is reachable, false if not

   // Initialize curl:
   $curlInit = curl_init($domain);
   curl_setopt($curlInit,CURLOPT_CONNECTTIMEOUT,10);
   curl_setopt($curlInit,CURLOPT_HEADER,true);
   curl_setopt($curlInit,CURLOPT_NOBODY,true);
   curl_setopt($curlInit,CURLOPT_RETURNTRANSFER,true);

   // Get answer
   $response = curl_exec($curlInit);

    // Close curl:
   curl_close($curlInit);

    // Generate response:
   if ($response) return true;
       return false;
}

function get_data($url) {
  $ch = curl_init();
  $timeout = 5;
  curl_setopt($ch,CURLOPT_URL,$url);
  curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
  curl_setopt($ch,CURLOPT_CONNECTTIMEOUT,$timeout);
  $data = curl_exec($ch);
  curl_close($ch);
  return $data;
}

?>
