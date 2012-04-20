<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2009, Stauber Multimedia Design. All rights reserved.

include_once('ServerScriptHelper.php');
include_once('AutoFeatures.php');
include_once('Capabilities.php');

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-vsite");
$helper = new ServerScriptHelper();

// Only adminUser and siteAdmin should be here
if (!$helper->getAllowed('adminUser') &&
    !($helper->getAllowed('siteAdmin') &&
      $group == $helper->loginUser['site'])) {
  header("location: /error/forbidden.html");
  return;
}

if (!$group) {
  header("location: /error/forbidden.html");
  return;
}

$cceClient = $serverScriptHelper->getCceClient();

// Which site are we editing?
  $oids = $cceClient->find('Vsite', array('name' => $group));
  if ($oids[0] == '') {
    exit();
  }
  $vsite_php = $cceClient->get($oids[0], 'PHP');

// Show error message box if PHP is not enabled for this vsite:
if ($vsite_php["enabled"] == "0") {
    $page = $factory->getPage();

    // Info about PHP-Status:
    $phpvsite_statusbox = $factory->getPagedBlock("PHPVsiteStatusBox_header", array("Default"));
    $phpvsite_statusbox->processErrors($serverScriptHelper->getErrors());

    $warning = $i18n->get("phpVsiteNotEnabled");
    $phpvsite_statusbox->addFormField(
        $factory->getTextList("_", $warning, 'r'),
        $factory->getLabel(" "),
        "Default"
    );

    print($page->toHeaderHtml());
    print($phpvsite_statusbox->toHtml());

    $serverScriptHelper->destructor();
    exit;
}

  $oids = $cceClient->find('Vsite', array('name' => $group));
  if ($oids[0] == '') {
    exit();
  }

   // Make sure our 'open_basedir' has the bare metal minimums in it:
   $open_basedir_pieces = explode (':', $open_basedir);
   if ($open_basedir_pieces[0] == "") {
	$open_basedir_pieces = array();
   }
   $open_basedir_minimal = array('/tmp/', '/var/lib/php/session/', '/usr/sausalito/configs/php/');
   $open_basedir_merged = array_merge($open_basedir_pieces, $open_basedir_minimal);
   $new_open_basedir = array_unique($open_basedir_merged);
   $open_basedir = implode(":", $new_open_basedir);

  // Remove any superfluxus /home/.sites/ paths from $open_basedir:
  $this_vsite_open_basedir = preg_split ("/:/", $open_basedir);
  $this_vsite_open_basedir_new = array();
  foreach ($this_vsite_open_basedir as $entry) {
        if(!preg_match("/\/home\/.sites\//i", $entry, $regs)) {
            array_push($this_vsite_open_basedir_new, $entry);
        }
  }
  $open_basedir = implode(":",$this_vsite_open_basedir_new);

// Make sure our 'safe_mode_allowed_env_vars' has the bare metal minimums in it:
$safe_mode_allowed_env_vars_pieces = explode (',', $safe_mode_allowed_env_vars);
if ($safe_mode_allowed_env_vars_pieces[0] == "") {
    $safe_mode_allowed_env_vars_pieces = array();
}
$safe_mode_allowed_env_vars_minimal = array('PHP_','_HTTP_HOST','_SCRIPT_NAME','_SCRIPT_FILENAME','_DOCUMENT_ROOT','_REMOTE_ADDR','_SOWNER');
$safe_mode_allowed_env_vars_merged = array_merge($safe_mode_allowed_env_vars_pieces, $safe_mode_allowed_env_vars_minimal);
$new_safe_mode_allowed_env_vars = array_unique($safe_mode_allowed_env_vars_merged);
$safe_mode_allowed_env_vars = implode(',', $new_safe_mode_allowed_env_vars);

// Find out which PHP version we use:
list($myplatform) = $cceClient->find('PHP');
$mysystem = $cceClient->get($myplatform);
$platform = $mysystem["PHP_version"];
if ($platform >= "5.3") {
  // We need to skip updating some legacy PHP settings that no longer work in PHP-5.3 or better:
  $cceClient->set($oids[0], 'PHPVsite',
        array(
              "force_update" => $force_update,
              "register_globals" => $register_globals,
              "open_basedir" => $open_basedir,
              "upload_max_filesize" => $upload_max_filesize,
              "post_max_size" => $post_max_size,
              "allow_url_fopen" => $allow_url_fopen,
              "allow_url_include" => $allow_url_include,
              "max_execution_time" => $max_execution_time,
              "max_input_time" => $max_input_time,
              "memory_limit" => $memory_limit,
              "force_update" => time())
	      );

}
else {
  // Update all settings for PHP older than 5.3:
  $cceClient->set($oids[0], 'PHPVsite',
        array(
              "force_update" => $force_update,
              "register_globals" => $register_globals,
              "safe_mode" => $safe_mode,
              "safe_mode_gid" => $safe_mode_gid,
              "safe_mode_include_dir" => $safe_mode_include_dir,
              "safe_mode_exec_dir" => $safe_mode_exec_dir,
              "safe_mode_allowed_env_vars" => $safe_mode_allowed_env_vars,
              "safe_mode_protected_env_vars" => $safe_mode_protected_env_vars,
              "open_basedir" => $open_basedir,
              "upload_max_filesize" => $upload_max_filesize,
              "post_max_size" => $post_max_size,
              "allow_url_fopen" => $allow_url_fopen,
              "allow_url_include" => $allow_url_include,
              "max_execution_time" => $max_execution_time,
              "max_input_time" => $max_input_time,
              "memory_limit" => $memory_limit,
              "force_update" => time())
	      );
}

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/vsite/vsite_php.php?group=$group", $errors));

$serverScriptHelper->destructor();

?>
