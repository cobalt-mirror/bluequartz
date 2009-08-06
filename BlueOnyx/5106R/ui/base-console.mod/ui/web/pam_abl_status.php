<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.
// Copyright Team BlueOnyx 2009. All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-console");

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$cceClient = $serverScriptHelper->getCceClient();

// Unblocking action taking place:
if ($host || $user) {
    if ($host) {
	$OID = $cceClient->find("fail_hosts", array("host" => $host));
	$cceClient->set($OID[0], "",
    	    array(
            "host_remove" => time())
	);
    }
    if ($user) {
	$OID = $cceClient->find("fail_users", array("username" => $user));
	$cceClient->set($OID[0], "",
    	    array(
            "user_remove" => time())
	);
    }
}

// Other (button initiated) action taking place:
if ($action) {
    $mainOID = $cceClient->find("pam_abl_settings");
    $cceClient->set($mainOID[0], "", array($action => time()));
    $errors = $cceClient->errors();
}

// Update CODB with the latest info gathered from our config file:
$helper =& new ServerScriptHelper($sessionId);
$cceHelper =& $helper->getCceClient();
$ourOID = $cceHelper->find("pam_abl_settings");
$cceHelper->set($ourOID[0], "", array('import_history' => time()));
$errors = $cceHelper->errors();

// get settings
$systemObj = $cceClient->getObject("pam_abl_settings");

$factory = $serverScriptHelper->getHtmlComponentFactory("base-console", "/base/console/pam_abl_status.php");
$transMethodOn="off";
$page = $factory->getPage();

// Show main header:
// by default show summary and users
$page_selector =& $factory->getPagedBlock('pam_abl_blocked_users_and_hosts', array('blocked_hosts'));
$page_selector->addPage('blocked_users', $factory->getLabel('blocked_users'));

// Make sure we end up on the right tab after unblock actions:
if ($host || $user) {
    if ($host) {
	$page_selector->setSelectedId('blocked_hosts');
    }
    if ($user) {
	$page_selector->setSelectedId('blocked_users');
    }
}
if ($ps) {
    if ($ps == "h") {
	$page_selector->setSelectedId('blocked_hosts');
    }
    else {
	$page_selector->setSelectedId('blocked_users');
    }
}

// Hosts page:
if ($page_selector->getSelectedId() == 'blocked_hosts' || $page_selector->getSelectedId() == '') {

    // Host table:
    $hblock = $factory->getScrollList("pam_abl_blocked_hosts", array("host", "failcnt", "access", "Action"), array(0,1,2,$hactions));
    $hblock->setDefaultSortedIndex(0);

    // Get 'fail_hosts' information out of CCE:
    $hostnum = 1;
    $oids = $cceClient->findx('fail_hosts');
    foreach ($oids as $oid) {
	$HOSTS = $cceClient->get($oid);
	if ($HOSTS['host']) {
    	    $HOSTSLIST[$hostnum] = array(
        	'host' => $HOSTS['host'],
    	        'failcnt' => $HOSTS['failcnt'],
        	'blocking' => $HOSTS['blocking'],
    	        'activity' => $HOSTS['activity']
    	    );
    	    $hostnum++;
	}
    }

    // Populate host table rows with the data:
    while ( $hostnum > 0 ) {
	$nowtime = time();
        if ($HOSTSLIST[$hostnum]['host']) {

	    if ($HOSTSLIST[$hostnum]['blocking'] == "0") {
		$status =& $factory->getStatusSignal('normal');
	    }
	    else {
		$status =& $factory->getStatusSignal('severeProblem');
	    }
	    $hostname = $HOSTSLIST[$hostnum]['host'];
            $actions =& $factory->getRemoveButton("/base/console/pam_abl_status.php?host=$hostname");
	    if ($HOSTSLIST[$hostnum]['blocking'] == "0") {
        	$actions->setDisabled(true);
    	    }
            $hblock->addEntry(array(
                $factory->getTextField("", $HOSTSLIST[$hostnum]['host'], "r"),
                $factory->getTextField("", $HOSTSLIST[$hostnum]['failcnt'], "r"),
		$status,
                $actions
            ));
	}
        $hostnum--;
    }
}

// User page:
if ($page_selector->getSelectedId() == 'blocked_users') {

    // User table:
    $block = $factory->getScrollList("pam_abl_blocked_users", array("username", "failcnt", "access", "Action"), array(0,1,2,$uactions));
    $block->setDefaultSortedIndex(0);

    // Get 'fail_users' information out of CCE:
    $usernum = 1;
    $oids = $cceClient->findx('fail_users');
    foreach ($oids as $oid) {
	$USERS = $cceClient->get($oid);
	if ($USERS['username']) {
    	    $USERLIST[$usernum] = array(
        	'username' => $USERS['username'],
        	'failcnt' => $USERS['failcnt'],
        	'blocking' => $USERS['blocking'],
        	'activity' => $USERS['activity']
    	    );
    	    $usernum++;
	}
    }

    // Populate user table rows with the data:
    while ( $usernum > 0 ) {
	$nowtime = time();
        if ($USERLIST[$usernum]['username']) {

	    if ($USERLIST[$usernum]['blocking'] == "0") {
		$status =& $factory->getStatusSignal('normal');
	    }
	    else {
		$status =& $factory->getStatusSignal('severeProblem');
	    }
	    $username = $USERLIST[$usernum]['username'];
    	    $actions =& $factory->getRemoveButton("/base/console/pam_abl_status.php?user=$username");
	    if ($USERLIST[$usernum]['blocking'] == "0") {
        	$actions->setDisabled(true);
    	    }
            $block->addEntry(array(
                $factory->getTextField("", $USERLIST[$usernum]['username'], "r"),
                $factory->getTextField("", $USERLIST[$usernum]['failcnt'], "r"),
                $status,
                $actions
            ));
	}
	$usernum--;
    }
}

//$block->addButton($factory->getSaveButton($page->getSubmitAction()));

$serverScriptHelper->destructor();

// Out with the page:
print($page->toHeaderHtml()); 

print($page_selector->toHtml());

if ($page_selector->getSelectedId() == 'blocked_hosts' || $page_selector->getSelectedId() == '') {
    $ps = "h";
    print($hblock->toHtml());
}
if ($page_selector->getSelectedId() == 'blocked_users') {
    $ps = "u";
    print($block->toHtml());
}

// Buttons:
$reset_hosts_button = $factory->getButton("/base/console/pam_abl_status.php?action=reset_hosts&ps=$ps", 'reset_hosts_button');
$reset_users_button = $factory->getButton("/base/console/pam_abl_status.php?action=reset_users&ps=$ps", 'reset_users_button');
print("<p><TABLE><TR><TD>".$reset_hosts_button->toHtml()."</TD><TD>".$reset_users_button->toHtml()."</TD></TR></TABLE></p>");
$reset_all_button = $factory->getButton("/base/console/pam_abl_status.php?action=reset_all&ps=$ps", 'reset_all_button');
$purge_button = $factory->getButton("/base/console/pam_abl_status.php?action=purge&ps=$ps", 'purge_button');
print("<p><TABLE><TR><TD>".$reset_all_button->toHtml()."</TD><TD>".$purge_button->toHtml()."</TD></TR></TABLE></p>");

print($page->toFooterHtml());

?>
