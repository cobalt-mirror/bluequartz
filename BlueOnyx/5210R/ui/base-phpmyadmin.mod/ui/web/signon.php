<?php

// phpMyAdmin signon adapted for sausalito/Bluapp 
// (C) Bluapp AB and Project BlueOnyx 2009 - All rights reserved.

include_once("ServerScriptHelper.php");
include_once("uifc/Label.php");
include_once("uifc/TextList.php");
include_once("uifc/Button.php");
include_once("uifc/FormFieldBuilder.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-phpmyadmin","/base/phpmyadmin/signon.php");

$builder = new FormFieldBuilder();

// Sanity checks:
if ($db_enabled == "0") {
	$db_host = "localhost";
	$db_username = "";
	$db_pass = "";
}
if (!$db_host) {
	$db_host = "localhost";
}

/* Was data posted? */
if ($_POST['PMA_user'] != "") {
    if (isset($_POST['PMA_user'])) {
	/* Need to have cookie visible from parent directory */
	session_set_cookie_params(0, '/', '', 0);
	/* Create signon session */
	$session_name = 'SignonSession';
	session_name($session_name);
	session_start();
	/* Store there credentials */
	$_SESSION['PMA_single_signon_user'] = $_POST['PMA_user'];
	$_SESSION['PMA_single_signon_password'] = $_POST['PMA_password'];
	$_SESSION['PMA_single_signon_host'] = $_POST['hostname'];
	$id = session_id();
	/* Close that session */
	session_write_close();
	/* Redirect to phpMyAdmin (should use absolute URL here!) */
	header('Location: /phpMyAdmin/index.php');
    } 
} 
else {

  $page = $factory->getPage();
  $block = $factory->getPagedBlock("PMA_logon");
  $block->addFormField(
		       $factory->getTextField("PMA_user", $db_username),
		       $factory->getLabel("PMA_user")
		       );
  $passwordField = $factory->getPassword("PMA_password", $db_pass);
  $passwordField->setConfirm(false);
  $block->addFormField(
		       $passwordField,
		       $factory->getLabel("PMA_password")
		       );
  $hidden = $builder->makeHiddenField("hostname", $db_host);

  $block->addButton($factory->getButton($page->getSubmitAction(), "login"));

  print($page->toHeaderHtml());
  print $hidden;

  print($block->toHtml());
	  
  print($page->toFooterHtml());
  $serverScriptHelper->destructor();
}
?>
