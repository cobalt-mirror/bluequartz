<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2008, Stauber Multimedia Design. All rights reserved.

include_once('ServerScriptHelper.php');
include_once('AutoFeatures.php');
include_once('Capabilities.php');

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-vsite");
$helper =& new ServerScriptHelper();

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
 // $phpVsite = $cceClient->get($oids[0], 'PHPVsite');
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
              "max_execution_time" => $max_execution_time,
              "max_input_time" => $max_input_time,
              "memory_limit" => $memory_limit,
              "force_update" => time())
	      );

$errors = $cceClient->errors();

print($serverScriptHelper->toHandlerHtml("/base/vsite/vsite_php.php?group=$group", $errors));

$serverScriptHelper->destructor();

?>
