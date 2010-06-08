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

// Determine if we're admin or siteAdmin to set access:
if (($helper->getAllowed('siteAdmin')) && (!$helper->getAllowed('adminUser'))) {
    $access = "r";
}
else {
    $access = "rw";
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/base/vsite/vsite_phpHandler.php?group=$group");
$transMethodOn="off";

// Only adminUser can modify things on this page.
// Site admins can view it for informational purposes.
if ($helper->getAllowed('adminUser')){
    $is_site_admin = 0;
    $access = 'rw';
} else {
    $access = 'r';
    $is_site_admin = 1;
}

// Which site are we editing?
list($myvsite) = $cceClient->find('Vsite', array('name' => $group));
$vsite = $cceClient->get($myvsite);
$vsite_php = $cceClient->get($myvsite, "PHP");

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
else {
    $systemObj = $cceClient->get($myvsite, "PHPVsite");
}

$page = $factory->getPage();

$block = $factory->getPagedBlock("php_vsite_head", array("Default"));
$block->setLabel($factory->getLabel('php_vsite_head', false, array('vsite' => $vsite['fqdn'])));
$block->processErrors($serverScriptHelper->getErrors());

// Force Update of CODB:
mt_srand((double)microtime() * 1000000);
$zufall = mt_rand();
$force_update_Field = $factory->getTextField("force_update", $zufall);
$force_update_Field->setOptional ('silent');
$block->addFormField(
    $force_update_Field,
    $factory->getLabel("force_update"),
    "hidden"
);

// Register Globals:
if ($systemObj["register_globals"] == 'Off') {
        $register_globals_choices=array("register_globals_no" => "Off", "register_globals_yes" => "On");
}
else {
	//Strict, but safe default:
        $register_globals_choices=array("register_globals_yes" => "On", "register_globals_no" => "Off");
}

// Register Globals Input:
$register_globals_select = $factory->getMultiChoice("register_globals",array_values($register_globals_choices));
$register_globals_select->setSelected($register_globals_choices[$register_globals], true);
$block->addFormField($register_globals_select,$factory->getLabel("register_globals"), "Default");


// Safe Mode:
if ($systemObj["safe_mode"] == 'Off') {
        $safe_mode_choices=array("safe_mode_no" => "Off", "safe_mode_yes" => "On");
}
else {
	//Strict, but safe default:
        $safe_mode_choices=array("safe_mode_yes" => "On", "safe_mode_no" => "Off");
}

// Safe Mode Input:
$safe_mode_select = $factory->getMultiChoice("safe_mode",array_values($safe_mode_choices));
$safe_mode_select->setSelected($safe_mode_choices[$safe_mode], true);
$block->addFormField($safe_mode_select,$factory->getLabel("safe_mode"), "Default");

// safe_mode_gid = Off
if ($systemObj["safe_mode_gid"] == 'On') {
        $safe_mode_gid_choices=array("safe_mode_gid_yes" => "On", "safe_mode_gid_no" => "Off");
}
else {
//Safe default:
        $safe_mode_gid_choices=array("safe_mode_gid_no" => "Off", "safe_mode_gid_yes" => "On");
}

// Safe Mode GID Input:
$safe_mode_gid_select = $factory->getMultiChoice("safe_mode_gid",array_values($safe_mode_gid_choices));
$safe_mode_gid_select->setSelected($safe_mode_gid_choices[$safe_mode_gid], true);
$block->addFormField($safe_mode_gid_select,$factory->getLabel("safe_mode_gid"), "Default");

// safe_mode_include_dir =
$safe_mode_include_dir_Field = $factory->getTextField("safe_mode_include_dir", $systemObj['safe_mode_include_dir'], $access);
$safe_mode_include_dir_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_include_dir_Field,
    $factory->getLabel("safe_mode_include_dir"),
    "Default"
);

// safe_mode_exec_dir =
$safe_mode_exec_dir_Field = $factory->getTextField("safe_mode_exec_dir", $systemObj['safe_mode_exec_dir'], $access);
$safe_mode_exec_dir_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_exec_dir_Field,
    $factory->getLabel("safe_mode_exec_dir"),
    "Default"
);

// safe_mode_allowed_env_vars = PHP_
$safe_mode_allowed_env_vars_Field = $factory->getTextField("safe_mode_allowed_env_vars", $systemObj['safe_mode_allowed_env_vars'], $access);
$safe_mode_allowed_env_vars_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_allowed_env_vars_Field,
    $factory->getLabel("safe_mode_allowed_env_vars"),
    "Default"
);

// safe_mode_protected_env_vars = LD_LIBRARY_PATH
$safe_mode_protected_env_vars_Field = $factory->getTextField("safe_mode_protected_env_vars", $systemObj['safe_mode_protected_env_vars'], $access);
$safe_mode_protected_env_vars_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_protected_env_vars_Field,
    $factory->getLabel("safe_mode_protected_env_vars"),
    "Default"
);

// open_basedir
// Remove any superfluxus /home/.sites/ paths from $systemObj['open_basedir']:
$this_vsite_open_basedir = preg_split ("/:/", $systemObj['open_basedir']);
$this_vsite_open_basedir_new = array();
foreach ($this_vsite_open_basedir as $entry) {
	if(!preg_match("/\/home\/.sites\//i", $entry, $regs)) {
	    array_push($this_vsite_open_basedir_new, $entry);
	}
}
# Unless someone intentionally runs with an empty 'open_basedir' we need to fix the site root:
if ($systemObj['open_basedir'] != "") {
	$systemObj['open_basedir'] = implode(":",$this_vsite_open_basedir_new);
	// If 'open_basedir' doesn't have this vsite's basedir in it, then we need to append it here:
	$vs_bdir = $vsite['basedir'];
	if(!preg_match('/$vs_bdir/i', $systemObj['open_basedir'])) {
	    $systemObj['open_basedir'] = $systemObj['open_basedir'] . ":" . $vsite['basedir'] . "/";
	}
}

$open_basedir_Field = $factory->getTextField("open_basedir", $systemObj['open_basedir'], $access);
$open_basedir_Field->setOptional ('silent');
$block->addFormField(
    $open_basedir_Field,
    $factory->getLabel("open_basedir"),
    "Default"
);

// allow_url_fopen:
if ($systemObj["allow_url_fopen"] == 'On') {
        $allow_url_fopen_choices=array("allow_url_fopen_yes" => "On", "allow_url_fopen_no" => "Off");
}
else {
        //Strict, but safe default:
        $allow_url_fopen_choices=array("allow_url_fopen_no" => "Off", "allow_url_fopen_yes" => "On");
}

// allow_url_fopen Input:
$allow_url_fopen_select = $factory->getMultiChoice("allow_url_fopen",array_values($allow_url_fopen_choices));
$allow_url_fopen_select->setSelected($allow_url_fopen_choices[$allow_url_fopen], true);
$block->addFormField($allow_url_fopen_select,$factory->getLabel("allow_url_fopen"), "Default");

// allow_url_include:
if ($systemObj["allow_url_include"] == 'On') {
        $allow_url_include_choices=array("allow_url_include_yes" => "On", "allow_url_include_no" => "Off");
}
else {
        //Strict, but safe default:
        $allow_url_include_choices=array("allow_url_include_no" => "Off", "allow_url_include_yes" => "On");
}

// allow_url_include Input:
$allow_url_include_select = $factory->getMultiChoice("allow_url_include",array_values($allow_url_include_choices));
$allow_url_include_select->setSelected($allow_url_include_choices[$allow_url_include], true);
$block->addFormField($allow_url_include_select,$factory->getLabel("allow_url_include"), "Default");

// upload_max_filesize:
if ($systemObj['upload_max_filesize']) {
    $upload_max_filesize_choices=array
            (
                "ul_01" => "2M",
                "ul_02" => "5M",
                "ul_03" => "8M",
                "ul_04" => "10M",
                "ul_05" => "12M",
                "ul_06" => "15M",
                "ul_07" => "20M",
                "ul_08" => "25M",
                "ul_09" => "30M",
                "ul_10" => "40M",
                "ul_11" => "50M",
                "ul_12" => "60M",
                "ul_13" => "70M",
                "ul_14" => "80M",
                "ul_15" => "90M",
                "ul_16" => "100M",
                "pm_17" => "110M",
                "pm_18" => "128M",
                "pm_19" => "136M",
                "pm_20" => "148M",
                "pm_21" => "156M",
                "pm_22" => "164M",
                "pm_23" => "172M",
                "pm_24" => "180M"
            );
}

// upload_max_filesize Input:
$upload_max_filesize_choices_select = $factory->getMultiChoice("upload_max_filesize",array_values($upload_max_filesize_choices));
$upload_max_filesize_choices_select->setSelected($systemObj['upload_max_filesize'], true);
$block->addFormField($upload_max_filesize_choices_select,$factory->getLabel("upload_max_filesize"), "Default");

// post_max_size:
if ($systemObj['post_max_size']) {
    $post_max_size_choices=array
            (
                "pm_01" => "2M",
                "pm_02" => "5M",
                "pm_03" => "8M",
                "pm_04" => "10M",
                "pm_05" => "12M",
                "pm_06" => "15M",
                "pm_07" => "20M",
                "pm_08" => "25M",
                "pm_09" => "30M",
                "pm_10" => "40M",
                "pm_11" => "50M",
                "pm_12" => "60M",
                "pm_13" => "70M",
                "pm_14" => "80M",
                "pm_15" => "90M",
                "pm_16" => "100M",
                "pm_17" => "110M",
                "pm_18" => "128M",
                "pm_19" => "136M",
                "pm_20" => "148M",
                "pm_21" => "156M",
                "pm_22" => "164M",
                "pm_23" => "172M",
                "pm_24" => "180M"
            );
}

// post_max_size Input:
$post_max_size_choices_select = $factory->getMultiChoice("post_max_size",array_values($post_max_size_choices));
$post_max_size_choices_select->setSelected($systemObj['post_max_size'], true);
$block->addFormField($post_max_size_choices_select,$factory->getLabel("post_max_size"), "Default");

// max_execution_time:
if ($systemObj['max_execution_time']) {
    $max_execution_time_choices=array
            (
                "me_01" => "30",
                "me_02" => "60",
                "me_03" => "90",
                "me_04" => "120",
                "me_05" => "150",
                "me_06" => "180",
                "me_07" => "210",
                "me_08" => "240",
                "me_09" => "270",
                "me_10" => "300",
                "me_11" => "500",
                "me_12" => "600",
                "me_13" => "900"
            );
}

// max_execution_time Input:
$max_execution_time_choices_select = $factory->getMultiChoice("max_execution_time",array_values($max_execution_time_choices));
$max_execution_time_choices_select->setSelected($systemObj['max_execution_time'], true);
$block->addFormField($max_execution_time_choices_select,$factory->getLabel("max_execution_time"), "Default");

// max_input_time:
if ($systemObj['max_input_time']) {
    $max_input_time_choices=array
            (
                "mit_01" => "30",
                "mit_02" => "60",
                "mit_03" => "90",
                "mit_04" => "120",
                "mit_05" => "150",
                "mit_06" => "180",
                "mit_07" => "210",
                "mit_08" => "240",
                "mit_09" => "270",
                "mit_10" => "300",
                "mit_11" => "500",
                "mit_12" => "600",
                "mit_13" => "900"
            );
}

// max_input_time Input:
$max_input_time_choices_select = $factory->getMultiChoice("max_input_time",array_values($max_input_time_choices));
$max_input_time_choices_select->setSelected($systemObj['max_input_time'], true);
$block->addFormField($max_input_time_choices_select,$factory->getLabel("max_input_time"), "Default");

// memory_limit:
if ($systemObj['memory_limit']) {
    $memory_limit_choices=array
            (
                "mlc_01" => "16M",
                "mlc_02" => "18M",
                "mlc_03" => "20M",
                "mlc_04" => "25M",
                "mlc_05" => "30M",
                "mlc_06" => "35M",
                "mlc_07" => "40M",
                "mlc_08" => "45M",
                "mlc_09" => "50M",
                "mlc_10" => "55M",
                "mlc_11" => "60M",
                "mlc_12" => "65M",
                "mlc_13" => "70M",
                "mlc_14" => "80M",
                "mlc_15" => "90M",
                "mlc_16" => "100M",
                "mlc_17" => "110M",
                "mlc_18" => "128M",
                "mlc_19" => "136M",
                "mlc_20" => "148M",
                "mlc_21" => "156M",
                "mlc_22" => "164M",
                "mlc_23" => "172M",
                "mlc_24" => "180M"
                
            );
}

// memory_limit Input:
$memory_limit_choices_select = $factory->getMultiChoice("memory_limit",array_values($memory_limit_choices));
$memory_limit_choices_select->setSelected($systemObj['memory_limit'], true);
$block->addFormField($memory_limit_choices_select,$factory->getLabel("memory_limit"), "Default");


if ($access == "rw") {
    // Show "save" button
    $block->addButton($factory->getSaveButton($page->getSubmitAction()));
}

$serverScriptHelper->destructor();

print($page->toHeaderHtml()); 
print($block->toHtml());
print($page->toFooterHtml());

function br2nl($str) {
   $str = preg_replace("/(\r\n|\n|\r)/", "", $str);
   return preg_replace("=<br */?>=i", "\n", $str);
}

?>

