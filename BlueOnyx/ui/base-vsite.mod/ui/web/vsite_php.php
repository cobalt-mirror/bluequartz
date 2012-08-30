<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2010, Stauber Multimedia Design. All rights reserved.

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

// Find out which PHP version we use:
list($myplatform) = $cceClient->find('PHP');
$mysystem = $cceClient->get($myplatform);
$platform = $mysystem["PHP_version"];
if (($platform >= "5.3") && ($mysystem["show_safemode"] == "0")) {
    // We need to hide some legacy PHP settings that no longer work in PHP-5.3 or better:
    $pageID = "Hidden";
}
else {
    $pageID = "Default";
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
$block->addFormField($safe_mode_select,$factory->getLabel("safe_mode"), $pageID);

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
$block->addFormField($safe_mode_gid_select,$factory->getLabel("safe_mode_gid"), $pageID);

// safe_mode_include_dir =
$safe_mode_include_dir_Field = $factory->getTextField("safe_mode_include_dir", $systemObj['safe_mode_include_dir'], $access);
$safe_mode_include_dir_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_include_dir_Field,
    $factory->getLabel("safe_mode_include_dir"),
    $pageID
);

// safe_mode_exec_dir =
$safe_mode_exec_dir_Field = $factory->getTextField("safe_mode_exec_dir", $systemObj['safe_mode_exec_dir'], $access);
$safe_mode_exec_dir_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_exec_dir_Field,
    $factory->getLabel("safe_mode_exec_dir"),
    $pageID
);

// safe_mode_allowed_env_vars = PHP_
$safe_mode_allowed_env_vars_Field = $factory->getTextField("safe_mode_allowed_env_vars", $systemObj['safe_mode_allowed_env_vars'], $access);
$safe_mode_allowed_env_vars_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_allowed_env_vars_Field,
    $factory->getLabel("safe_mode_allowed_env_vars"),
    $pageID
);

// safe_mode_protected_env_vars = LD_LIBRARY_PATH
$safe_mode_protected_env_vars_Field = $factory->getTextField("safe_mode_protected_env_vars", $systemObj['safe_mode_protected_env_vars'], $access);
$safe_mode_protected_env_vars_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_protected_env_vars_Field,
    $factory->getLabel("safe_mode_protected_env_vars"),
    $pageID
);

// open_basedir
/*

    OK, this gets a little complicated looking, so some explanations may be in order:

    The new handling for 'open_basedir' splits the form field into two separate text blocks. One is read only
    and contains the server wide PHP settings defined for 'open_basedir' plus the Vsite's root directory
    added to it. That is the read only one.

    The second one is editable and contains optional paths which a serverAdmin or siteAdmin may have chosen
    to add manually.

    To achieve this we read in the current server wide PHP settings to get our mandatory 'open_basedir' paths.
    We add the Vsite basedir to that and store this in one array. 

    THEN we read in the Vsite's 'open_basedir' settings and remove anything from it that starts with 
    '/home/.sites/' just to be really damn sure that no path to the wrong Vsite's root directory has been added.
    Anything that is not yet covered in the mandatory section is then pushed into a second array.

    Lastly we use array_diff() to compare both arrays and to extract anything that's different. This may be 
    redundant, but let us really be sure here. The differences are then stored in a third array, which we 
    present in the second editable 'open_basedir' text block.

    But take note here: This is just for showing off. In fact the only thing that vsite_phpHandler.php will really
    care about are the bits and pices from the user editeable form. The mandatory bits and pices and the Vsite
    root will be added by the underlying constructor. That way we remain compatible with CMU.

*/

// Make sure our 'open_basedir' has the bare metal minimums in it as defined for this server:
// We add the Vsite basedir to the 'open_basedir' settings defined for the server:
$open_basedir_mandatory_pieces = explode (':', $mysystem['open_basedir'] . ":" . $vsite['basedir']);

// Now we walk through this Vsite's 'open_basedir' settings and remove all the bits and pieces
// that we already have covered, so that only user added additions remain:
$this_vsite_open_basedir = preg_split ("/:/", $systemObj['open_basedir']);
$this_vsite_open_basedir_new = array();
foreach ($this_vsite_open_basedir as $entry) {
    // Only push pieces if they're not already covered by mandatory entries and also don't push if path starts with '/home/.sites/':
    if ((!in_array($entry, $open_basedir_mandatory_pieces)) && (!preg_match("/\/home\/.sites\//i", $entry, $regs))) {
	array_push($this_vsite_open_basedir_new, $entry);
    }
}

// Now remove anything that we have already covered in the mandatory section. That leaves us with the user additions:
$result = array_diff($this_vsite_open_basedir_new, $open_basedir_mandatory_pieces);
$open_basedir_additions = $result;

// Sort the arrays before we implode them later on:
array_multisort($open_basedir_mandatory_pieces, SORT_ASC);
array_multisort($open_basedir_additions, SORT_ASC);

// Print out the block with the mandatory 'open_basedir' stuff. Please note: This is for display only. The contends here cannot
// be processed via form handlers:
$open_basedir_mandatory_Field = $factory->getTextBlock("open_basedir_mandatory", implode("\n",$open_basedir_mandatory_pieces), 'r');
$open_basedir_mandatory_Field->setOptional ('silent');
$block->addFormField(
    $open_basedir_mandatory_Field,
    $factory->getLabel("open_basedir_mandatory"),
    "Default"
);

// Print out a hidden block with the same mandatory 'open_basedir' stuff in it, but hide it from view. This can be processed
// as form data:
$open_basedir_mandatory_hidden_Field = $factory->getTextField("open_basedir_mandatory_hidden", implode(":",$open_basedir_mandatory_pieces), 'rw');
$open_basedir_mandatory_hidden_Field->setOptional ('silent');
$block->addFormField(
    $open_basedir_mandatory_hidden_Field,
    $factory->getLabel("open_basedir_mandatory_hidden"),
    "hidden"
);

// Print out the block with the custom additions for 'open_basedir':
$open_basedir_Field = $factory->getTextBlock("open_basedir", implode("\n",$open_basedir_additions), $access);
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
$upload_max_filesize_choices = array (
            '2M',
            '4M',
            '8M',
            '16M',
            '24M',
            '32M',
            '40M',
            '48M',
            '56M',
            '64M',
            '72M',
            '80M',
            '88M',
            '96M',
            '104M',
            '112M',
            '120M',
            '128M',
            '132M',
            '140M',
            '148M',
            '156M',
            '164M',
            '172M',
            '180M'
        );

if ($systemObj['upload_max_filesize']) {
        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['upload_max_filesize'], $upload_max_filesize_choices)) {
                $upload_max_filesize_choices[] = $systemObj['upload_max_filesize'];
        }
        sort($upload_max_filesize_choices, SORT_NUMERIC);
}

// upload_max_filesize Input:
$upload_max_filesize_choices_select = $factory->getMultiChoice("upload_max_filesize",array_values($upload_max_filesize_choices));
$upload_max_filesize_choices_select->setSelected($systemObj['upload_max_filesize'], true);
$block->addFormField($upload_max_filesize_choices_select,$factory->getLabel("upload_max_filesize"), "Default");

// post_max_size:
$post_max_size_choices = array (
            '2M',
            '4M',
            '8M',
            '16M',
            '24M',
            '32M',
            '40M',
            '48M',
            '56M',
            '64M',
            '72M',
            '80M',
            '88M',
            '96M',
            '104M',
            '112M',
            '120M',
            '128M',
            '132M',
            '140M',
            '148M',
            '156M',
            '164M',
            '172M',
            '180M'
        );

if ($systemObj['post_max_size']) {
        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['post_max_size'], $post_max_size_choices)) {
                $post_max_size_choices[] = $systemObj['post_max_size'];
        }
        sort($post_max_size_choices, SORT_NUMERIC);
}

// post_max_size Input:
$post_max_size_choices_select = $factory->getMultiChoice("post_max_size",array_values($post_max_size_choices));
$post_max_size_choices_select->setSelected($systemObj['post_max_size'], true);
$block->addFormField($post_max_size_choices_select,$factory->getLabel("post_max_size"), "Default");

// max_execution_time:
$max_execution_time_choices = array (
            '30',
            '60',
            '90',
            '120',
            '150',
            '180',
            '210',
            '240',
            '270',
            '300',
            '500',
            '600',
            '900'
        );

if ($systemObj['max_execution_time']) {
        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['max_execution_time'], $max_execution_time_choices)) {
                $max_execution_time_choices[] = $systemObj['max_execution_time'];
        }
        sort($max_execution_time_choices, SORT_NUMERIC);
}

// max_execution_time Input:
$max_execution_time_choices_select = $factory->getMultiChoice("max_execution_time",array_values($max_execution_time_choices));
$max_execution_time_choices_select->setSelected($systemObj['max_execution_time'], true);
$block->addFormField($max_execution_time_choices_select,$factory->getLabel("max_execution_time"), "Default");

// max_input_time:
$max_input_time_choices = array (
            '30',
            '60',
            '90',
            '120',
            '150',
            '180',
            '210',
            '240',
            '270',
            '300',
            '500',
            '600',
            '900'
        );

if ($systemObj['max_input_time']) {
        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['max_input_time'], $max_input_time_choices)) {
                $max_input_time_choices[] = $systemObj['max_input_time'];
        }
        sort($max_input_time_choices, SORT_NUMERIC);
}

// max_input_time Input:
$max_input_time_choices_select = $factory->getMultiChoice("max_input_time",array_values($max_input_time_choices));
$max_input_time_choices_select->setSelected($systemObj['max_input_time'], true);
$block->addFormField($max_input_time_choices_select,$factory->getLabel("max_input_time"), "Default");

// memory_limit:
$memory_limit_choices = array (
            '16M',
            '24M',
            '32M',
            '40M',
            '48M',
            '56M',
            '64M',
            '72M',
            '80M',
            '88M',
            '96M',
            '104M',
            '112M',
            '120M',
            '128M',
            '132M',
            '140M',
            '148M',
            '156M',
            '164M',
            '172M',
            '180M'
        );

if ($systemObj['memory_limit']) {
        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['memory_limit'], $memory_limit_choices)) {
                $memory_limit_choices[] = $systemObj['memory_limit'];
        }
        sort($memory_limit_choices, SORT_NUMERIC);
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

