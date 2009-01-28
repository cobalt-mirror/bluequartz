<?php

// phpMyAdmin signon adapted for sausalito/Bluapp 
// (C) Bluapp AB 2009 - All rights reserved.

include_once("ServerScriptHelper.php");
include_once("uifc/Label.php");
include_once("uifc/TextList.php");
include_once("uifc/Button.php");
include_once("uifc/FormFieldBuilder.php");

$serverScriptHelper = new ServerScriptHelper();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-phpmyadmin","/base/phpmyadmin/signon.php");

$builder = new FormFieldBuilder();




/* Was data posted? */

if ($_POST['user'] != "") {
  if (isset($_POST['user'])) {
    /* Need to have cookie visible from parent directory */
    session_set_cookie_params(0, '/', '', 0);
    /* Create signon session */
    $session_name = 'SignonSession';
    session_name($session_name);
    session_start();
    /* Store there credentials */
    $_SESSION['PMA_single_signon_user'] = $_POST['user'];
    $_SESSION['PMA_single_signon_password'] = $_POST['password'];
    $_SESSION['PMA_single_signon_host'] = $_POST['host'];
    $id = session_id();
    /* Close that session */
    session_write_close();
    /* Redirect to phpMyAdmin (should use absolute URL here!) */
    header('Location: /phpMyAdmin/index.php');
  } 
 } else {

  $page = $factory->getPage();
  $block = $factory->getPagedBlock("PMA_logon");
  $block->addFormField(
		       $factory->getTextField("user", ""),
		       $factory->getLabel("PMA_user")
		       );
  $passwordField = $factory->getPassword("password");
  $passwordField->setConfirm(false);
  $block->addFormField(
		       $passwordField,
		       $factory->getLabel("PMA_password")
		       );
  $hidden = $builder->makeHiddenField("hostname", "localhost");

  $block->addButton($factory->getButton($page->getSubmitAction(), "login"));


  $serverScriptHelper->destructor();

  print($page->toHeaderHtml());
  print $hidden;

  print($block->toHtml());
	  
  print($page->toFooterHtml());
}
?>
