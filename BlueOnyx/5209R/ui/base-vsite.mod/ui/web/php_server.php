<?php

// Author: Michael Stauber <mstauber@solarspeed.net>
// Copyright 2006-2008, Stauber Multimedia Design. All rights reserved.
// Copyright 2008-2009, Team BlueOnyx. All rights reserved.

include_once("ServerScriptHelper.php");

$serverScriptHelper = new ServerScriptHelper();
$i18n = $serverScriptHelper->getI18n("base-vsite");

//Only users with adminUser capability should be here
if (!$serverScriptHelper->getAllowed('adminUser')) {
    header("location: /error/forbidden.html");
    return;
}

$cceClient = $serverScriptHelper->getCceClient();
$factory = $serverScriptHelper->getHtmlComponentFactory("base-vsite", "/base/vsite/php_serverHandler.php");
$transMethodOn="off";

// Find out which PHP version we use:
list($myplatform) = $cceClient->find('PHP');
$mysystem = $cceClient->get($myplatform);
$platform = $mysystem["PHP_version"];
if (($platform >= "5.3") && ($mysystem["show_safemode"] == "0")) {
    // We need to hide some legacy PHP settings that no longer work in PHP-5.3 or better:
    $pageID = "Hidden";
}
else {
    $pageID = "php_ini_security_settings";
}
if (($platform >= "5.4") && ($mysystem["show_safemode"] == "0")) {
    // We need to hide some legacy PHP settings that no longer work in PHP-5.3 or better:
    $pageID54 = "Hidden";
}
else {
    $pageID54 = "php_ini_security_settings";
}

// get settings
$systemObj = $cceClient->getObject("PHP",array('applicable' => 'server'));

$page = $factory->getPage();

$block = $factory->getPagedBlock("php_server_head", array("php_ini_security_settings", "php_ini_expert_mode"));
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

// php.ini location:
$php_ini_location_Field = $factory->getTextField("php_ini_location", $systemObj['php_ini_location'], "r");
$php_ini_location_Field->setOptional ('silent');
$block->addFormField(
    $php_ini_location_Field,
    $factory->getLabel("php_ini_location"),
    "php_ini_security_settings"
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
$block->addFormField($register_globals_select,$factory->getLabel("register_globals"), $pageID54);


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
$safe_mode_include_dir_Field = $factory->getTextField("safe_mode_include_dir", $systemObj['safe_mode_include_dir']);
$safe_mode_include_dir_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_include_dir_Field,
    $factory->getLabel("safe_mode_include_dir"),
    $pageID
);

// safe_mode_exec_dir =
$safe_mode_exec_dir_Field = $factory->getTextField("safe_mode_exec_dir", $systemObj['safe_mode_exec_dir']);
$safe_mode_exec_dir_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_exec_dir_Field,
    $factory->getLabel("safe_mode_exec_dir"),
    $pageID
);

// safe_mode_allowed_env_vars = PHP_
$safe_mode_allowed_env_vars_Field = $factory->getTextField("safe_mode_allowed_env_vars", $systemObj['safe_mode_allowed_env_vars']);
$safe_mode_allowed_env_vars_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_allowed_env_vars_Field,
    $factory->getLabel("safe_mode_allowed_env_vars"),
    $pageID
);

// safe_mode_protected_env_vars = LD_LIBRARY_PATH
$safe_mode_protected_env_vars_Field = $factory->getTextField("safe_mode_protected_env_vars", $systemObj['safe_mode_protected_env_vars']);
$safe_mode_protected_env_vars_Field->setOptional ('silent');
$block->addFormField(
    $safe_mode_protected_env_vars_Field,
    $factory->getLabel("safe_mode_protected_env_vars"),
    $pageID
);

//------ open_basedir:

// Make sure our 'open_basedir' has all the mandatory stuff in it:
$open_basedir_mandatory_pieces = array("/tmp/", "/var/lib/php/session/", "/usr/sausalito/configs/php/");

// Now we walk through $systemObj['open_basedir'] and make sure nobody added '/home/.sites/' here:
$this_open_basedir = preg_split ("/:/", $systemObj['open_basedir']);
// Pre-populate our new output array with the mandatory fields (we remove duplicates later on):
$this_open_basedir_new = $open_basedir_mandatory_pieces;
foreach ($this_open_basedir as $entry) {
    // Only push pieces if '/home/.sites/' or '/home/sites/' has not been added:
    if ((!preg_match("/^\/home\/.sites\//i", $entry, $regs)) && (!preg_match("/^\/home\/sites\//i", $entry, $regs))) {
        array_push($this_open_basedir_new, $entry);
    }
}

// Remove duplicates:
$open_basedir_cleaned = array_unique($this_open_basedir_new);

// Sort the array before we implode them later on:
array_multisort($open_basedir_cleaned, SORT_ASC);

// Print out the block with the mandatory 'open_basedir' stuff. Please note: This is for display only. The contends here cannot
// be processed via form handlers:
$open_basedir_mandatory_Field = $factory->getTextBlock("open_basedir_mandatory", implode("\n",$open_basedir_cleaned));
$open_basedir_mandatory_Field->setOptional ('silent');
$block->addFormField(
    $open_basedir_mandatory_Field,
    $factory->getLabel("open_basedir_mandatory"),
    "php_ini_security_settings"
); 

// disable_functions
$disable_functions_Field = $factory->getTextField("disable_functions", $systemObj['disable_functions']);
$disable_functions_Field->setOptional ('silent');
$block->addFormField(
    $disable_functions_Field,
    $factory->getLabel("disable_functions"),
    "php_ini_security_settings"
);

// disable_classes
$disable_classes_Field = $factory->getTextField("disable_classes", $systemObj['disable_classes']);
$disable_classes_Field->setOptional ('silent');
$block->addFormField(
    $disable_classes_Field,
    $factory->getLabel("disable_classes"),
    "php_ini_security_settings"
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
$block->addFormField($allow_url_fopen_select,$factory->getLabel("allow_url_fopen"), "php_ini_security_settings");

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
$block->addFormField($allow_url_include_select,$factory->getLabel("allow_url_include"), "php_ini_security_settings");

// upload_max_filesize:
if ($systemObj['upload_max_filesize']) {
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

        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['upload_max_filesize'], $upload_max_filesize_choices)) {
                $upload_max_filesize_choices[] = $systemObj['upload_max_filesize'];
        }
        sort($upload_max_filesize_choices, SORT_NUMERIC);}

// upload_max_filesize Input:
$upload_max_filesize_choices_select = $factory->getMultiChoice("upload_max_filesize",array_values($upload_max_filesize_choices));
$upload_max_filesize_choices_select->setSelected($systemObj['upload_max_filesize'], true);
$block->addFormField($upload_max_filesize_choices_select,$factory->getLabel("upload_max_filesize"), "php_ini_security_settings");

// post_max_size:
if ($systemObj['post_max_size']) {
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

        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['post_max_size'], $post_max_size_choices)) {
                $post_max_size_choices[] = $systemObj['post_max_size'];
        }
        sort($post_max_size_choices, SORT_NUMERIC);
}

// post_max_size Input:
$post_max_size_choices_select = $factory->getMultiChoice("post_max_size",array_values($post_max_size_choices));
$post_max_size_choices_select->setSelected($systemObj['post_max_size'], true);
$block->addFormField($post_max_size_choices_select,$factory->getLabel("post_max_size"), "php_ini_security_settings");

// max_execution_time:
if ($systemObj['max_execution_time']) {
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
        
        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['max_execution_time'], $max_execution_time_choices)) {
                $max_execution_time_choices[] = $systemObj['max_execution_time'];
        }
        sort($max_execution_time_choices, SORT_NUMERIC);
}

// max_execution_time Input:
$max_execution_time_choices_select = $factory->getMultiChoice("max_execution_time",array_values($max_execution_time_choices));
$max_execution_time_choices_select->setSelected($systemObj['max_execution_time'], true);
$block->addFormField($max_execution_time_choices_select,$factory->getLabel("max_execution_time"), "php_ini_security_settings");

// max_input_time:
if ($systemObj['max_input_time']) {
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
        
        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['max_input_time'], $max_input_time_choices)) {
                $max_input_time_choices[] = $systemObj['max_input_time'];
        }
        sort($max_input_time_choices, SORT_NUMERIC);
}

// max_input_time Input:
$max_input_time_choices_select = $factory->getMultiChoice("max_input_time",array_values($max_input_time_choices));
$max_input_time_choices_select->setSelected($systemObj['max_input_time'], true);
$block->addFormField($max_input_time_choices_select,$factory->getLabel("max_input_time"), "php_ini_security_settings");

// memory_limit:
if ($systemObj['memory_limit']) {
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

        // If we're currently using something that's not in that array, we add it to it:
        if (!in_array($systemObj['memory_limit'], $memory_limit_choices)) {
                $memory_limit_choices[] = $systemObj['memory_limit'];
        }
        sort($memory_limit_choices, SORT_NUMERIC);
}

// memory_limit Input:
$memory_limit_choices_select = $factory->getMultiChoice("memory_limit",array_values($memory_limit_choices));
$memory_limit_choices_select->setSelected($systemObj['memory_limit'], true);
$block->addFormField($memory_limit_choices_select,$factory->getLabel("memory_limit"), "php_ini_security_settings");

// Review php.ini:
$datei_zwo = $systemObj["php_ini_location"];
$array_zwo = file($datei_zwo);
for($x=0;$x<count($array_zwo);$x++){
	// Replace
        $array_zwo[$x] = nl2br($array_zwo[$x]); //#newline conversion
        $array_zwo[$x] = preg_replace('/\s\s+/', '', $array_zwo[$x]); //#strip spaces

        if(preg_match("/^register_globals/i",$array_zwo[$x], $regs)) {
                $array_zwo[$x] = preg_replace('/\s+/', '', $array_zwo[$x]); //#strip spaces
                $array_zwo[$x] = preg_replace('/register_globals=Off/i', 'register_globals = '.$systemObj["register_globals"].' ', $array_zwo[$x]);
                $array_zwo[$x] = preg_replace('/register_globals=On/i', 'register_globals = '.$systemObj["register_globals"].' ', $array_zwo[$x]);
        }
        if(preg_match("/^safe_mode/i",$array_zwo[$x], $regs)) {
                $array_zwo[$x] = preg_replace('/\s+/', '', $array_zwo[$x]); //#strip spaces
                $array_zwo[$x] = preg_replace('/safe_mode=Off/', 'safe_mode = '.$systemObj["safe_mode"].' ', $array_zwo[$x]);
                $array_zwo[$x] = preg_replace('/safe_mode=On/', 'safe_mode = '.$systemObj["safe_mode"].' ', $array_zwo[$x]);
        }
        if(preg_match("/^safe_mode_gid/i",$array_zwo[$x], $regs)) {
                $array_zwo[$x] = preg_replace('/\s+/', '', $array_zwo[$x]); //#strip spaces
                $array_zwo[$x] = preg_replace('/safe_mode_gid=On/', 'safe_mode_gid = '.$systemObj["safe_mode_gid"].' ', $array_zwo[$x]);
                $array_zwo[$x] = preg_replace('/safe_mode_gid=Off/', 'safe_mode_gid = '.$systemObj["safe_mode_gid"].' ', $array_zwo[$x]);
        }
        if(preg_match("/^safe_mode_include_dir/i",$array_zwo[$x], $regs)) {
		$array_zwo[$x] = 'safe_mode_include_dir = '.$systemObj["safe_mode_include_dir"].'<br>';
        }
        if(preg_match("/^safe_mode_exec_dir/i",$array_zwo[$x], $regs)) {
		$array_zwo[$x] = 'safe_mode_exec_dir = '.$systemObj["safe_mode_exec_dir"].'<br>';
        }
        if(preg_match("/^safe_mode_allowed_env_vars/i",$array_zwo[$x], $regs)) {
		$array_zwo[$x] = 'safe_mode_allowed_env_vars = '.$systemObj["safe_mode_allowed_env_vars"].'<br>';
        }
        if(preg_match("/^safe_mode_protected_env_vars/i",$array_zwo[$x], $regs)) {
		$array_zwo[$x] = 'safe_mode_protected_env_vars = '.$systemObj["safe_mode_protected_env_vars"].'<br>';
        }
        if(preg_match("/^open_basedir/i",$array_zwo[$x], $regs)) {
		$array_zwo[$x] = 'open_basedir = '.$systemObj["open_basedir"].'<br>';
        }
        if(preg_match("/^disable_functions/i",$array_zwo[$x], $regs)) {
		$array_zwo[$x] = 'disable_functions = '.$systemObj["disable_functions"].'<br>';
        }
        if(preg_match("/^disable_classes/i",$array_zwo[$x], $regs)) {
		$array_zwo[$x] = 'disable_classes = '.$systemObj["disable_classes"].'<br>';
        }
	// Replace end
	$array_zwo[$x] = br2nl($array_zwo[$x]);
	$the_file_data = $the_file_data.$array_zwo[$x];
}

$GLOBALS["_FormField_height"] = 40;
$GLOBALS["_FormField_width"] = 90;

$block->addFormField(
  $factory->getTextBlock("php_ini", $the_file_data, "rw"),
  $factory->getLabel("php_ini"),
  "php_ini_expert_mode"
);

// Show "save" button - unless we're on the "expert mode", which is currently disabled:
if ($_PagedBlock_selectedId_php_server_head != "php_ini_expert_mode") {
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

