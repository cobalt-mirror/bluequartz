<?php
	// Copyright 2011, Team BlueOnyx. All rights reserved.
	// $Id: system.php,v 1.0.0-1 Tue 26 Apr 2011 02:22:10 AM CEST mstauber Exp $

	include_once("ServerScriptHelper.php");

	$serverScriptHelper = new ServerScriptHelper();
	$i18n = $serverScriptHelper->getI18n("base-mailman");
	$cceClient = $serverScriptHelper->getCceClient();

	if (!$serverScriptHelper->getAllowed('serverServerDesktop')) {
		header("location: /error/forbidden.html");
		return;
	}

	$factory = $serverScriptHelper->getHtmlComponentFactory("base-mailman", "/base/mailman/systemHandler.php");

	$pid = `/etc/init.d/mailman status`;
	if (preg_match("/is running/i", $pid)) {
		$my_enabled = 1;
		$access="rw";
	} else {
		$my_enabled = 0;
		$access="r";
	}

	$nuMailMan = $cceClient->getObject("System", array(), "MailListStatus");

	if ($my_enabled == 1) {
		$nuMailMan["enabled"] = "1";
	}

        $getthisOID = $cceClient->find("System");
        $MailMan_settings = $cceClient->get($getthisOID[0], "MailListStatus");
        $oldpass = $MailMan_settings['admin_pw'];

	$page = $factory->getPage();
	$block = $factory->getPagedBlock("MailMan_header", array("server"));

	$block->addFormField(
		$factory->getBoolean("enabled", $nuMailMan["enabled"]),
		$factory->getLabel("mailman_enabled"),
		"server");

        // Current Password:
        $line_oldpass = $factory->getTextField("oldpass", $oldpass, 'r');
        $line_oldpass->setMaxLength(30);
        $block->addFormField($line_oldpass, $factory->getLabel("oldpass"), "server");

	echo $page->toHeaderHtml();
	$block->addButton($factory->getSaveButton($page->getSubmitAction()));

	$redirect = "http://" . $_SERVER['SERVER_NAME'] . "/mailman/admin";

	$block->addButton($factory->getButton("$redirect", "MailMan_Admin"));

	echo $block->toHtml();
	echo $page->toFooterHtml();

	$serverScriptHelper->destructor();

?>
