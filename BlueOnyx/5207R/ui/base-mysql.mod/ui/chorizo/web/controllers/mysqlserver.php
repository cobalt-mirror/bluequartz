<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Mysqlserver extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /mysql/mysqlserver.
	 *
	 */

	public function index() {

		$CI =& get_instance();

	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $this->load->helper('blueonyx');
	    init_libraries();

  		// Need to load 'BxPage' for page rendering:
  		$this->load->library('BxPage');
		$MX =& get_instance();

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');
	    $locale = $CI->input->cookie('locale');

	    // Line up the ducks for CCE-Connection:
	    include_once('ServerScriptHelper.php');
		$serverScriptHelper = new ServerScriptHelper($sessionId, $loginName);
		$cceClient = $serverScriptHelper->getCceClient();
		$user = $cceClient->getObject("User", array("name" => $loginName));
		$i18n = new I18n("base-mysql", $user['localePreference']);
		$system = $cceClient->getObject("System");

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($cceClient, $loginName, $sessionId);

		// -- Actual page logic start:

		// Not adminUser? Bye, bye!
		if (!$Capabilities->getAllowed('adminUser')) {
			// Nice people say goodbye, or CCEd waits forever:
			$cceClient->bye();
			$serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		//
		//--- Handle form validation:
		//

	    // We start without any active errors:
	    $errors = array();
	    $extra_headers =array();
	    $ci_errors = array();
	    $my_errors = array();

		// Shove submitted input into $form_data after passing it through the XSS filter:
		$form_data = $CI->input->post(NULL, TRUE);

		// Form fields that are required to have input:
		$required_keys = array(	"sql_host", "sql_port");
    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();
    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array("BlueOnyx_Info_Text", "sql_status", "_newpass_repeat", "filesize", "last_backup", "sql_host", "sql_port", "sql_rootpassword", "_sql_rootpassword_repeat", "sql_root");
		if (is_array($form_data)) {
			// Function GetFormAttributes() walks through the $form_data and returns us the $parameters we want to
			// submit to CCE. It intelligently handles checkboxes, which only have "on" set when they are ticked.
			// In that case it pulls the unticked status from the hidden checkboxes and addes them to $parameters.
			// It also transformes the value of the ticked checkboxes from "on" to "1". 
			//
			// Additionally it generates the form_validation rules for CodeIgniter.
			//
			// params: $i18n				i18n Object of the error messages
			// params: $form_data			array with form_data array from CI
			// params: $required_keys		array with keys that must have data in it. Needed for CodeIgniter's error checks
			// params: $ignore_attributes	array with items we want to ignore. Such as Labels.
			// return: 						array with keys and values ready to submit to CCE.
			$attributes = GetFormAttributes($i18n, $form_data, $required_keys, $ignore_attributes, $i18n);
		}
		//Setting up error messages:
		$CI->form_validation->set_message('required', $i18n->get("[[palette.val_is_required]]", false, array("field" => "\"%s\"")));		

	    // Do we have validation related errors?
	    if ($CI->form_validation->run() == FALSE) {

			if (validation_errors()) {
				// Set CI related errors:
				$ci_errors = array(validation_errors('<div class="alert dismissible alert_red"><img width="40" height="36" src="/.adm/images/icons/small/white/alarm_bell.png"><strong>', '</strong></div>'));
			}		    
			else {
				// No errors. Pass empty array along:
				$ci_errors = array();
			}
		}

		//
		//--- Own error checks:
		//

		if ($CI->input->post(NULL, TRUE)) {

			// First we get the existing MySQL data from CODB's "System" object:
			$SystemMYSQL = $cceClient->getObject("System", array(), "mysql");

			// Then we get the existing "MySQL" Object:
			$AbsMYSQL = $cceClient->getObject("MySQL");

			$mysql_current_username = $AbsMYSQL['sql_root'];
	  		$mysql_current_password = $AbsMYSQL['sql_rootpassword'];

			$sql_root = $mysql_current_username;
			$sql_rootpassword = $mysql_current_password;

			if (!isset($attributes['onoff'])) {
				$attributes['onoff'] = date("U");
			}

			if (!isset($attributes['newpass'])) {
				// We don't do a password change. So we write back the username and password we already had in CODB:
				$sql_root = $mysql_current_username;
				$sql_rootpassword = $mysql_current_password;
			}

			if (isset($form_data['sql_root'])) {
				$sql_root = $form_data['sql_root'];
			}
			else {
				$sql_root = $mysql_current_username;
			}

			if (!isset($attributes['username'])) {
				$attributes['username'] = $sql_root;
			}
			if (!isset($attributes['password'])) {
				$attributes['password'] = $sql_rootpassword;
			}

			if (isset($attributes['newpass'])) {
				$attributes['changepass'] = date("U");
				if (!isset($attributes['oldpass'])) {
					$attributes['oldpass'] = "-1"; 
				}
				$attributes['password'] = $attributes['newpass'];
				$sql_rootpassword = $attributes['newpass'];
			}

			if (isset($form_data['sql_rootpassword'])) {
				$sql_rootpassword = $form_data['sql_rootpassword'];
			}
			else {
				$sql_rootpassword = $mysql_current_password;
			}

			if (isset($attributes['newpass'])) {
				if ($attributes['newpass'] != "") {
					$sql_rootpassword = $attributes['newpass'];
				}
			}

			// Check Password match:
			$passwd = "";
			if (isset($form_data['newpass'])) {
				$passwd = $form_data['newpass'];
			}
			$passwd_repeat = "";
			if (isset($form_data['_newpass_repeat'])) {
				$passwd_repeat = $form_data['_newpass_repeat'];
			}
			if ((isset($form_data['newpass'])) || (isset($form_data['_newpass_repeat']))) {
				if ($form_data['newpass'] != "") {
					if (bx_pw_check($i18n, $passwd, $passwd_repeat) != "") {
						$my_errors[] = bx_pw_check($i18n, $passwd, $passwd_repeat);
					}
				}
			}

		}

		//
		//--- At this point all checks are done. If we have no errors, we can submit the data to CODB:
		//

		// Join the various error messages:
		$errors = array_merge($ci_errors, $my_errors);

		// If we have no errors and have POST data, we submit to CODB:
		if ((count($errors) == "0") && ($CI->input->post(NULL, TRUE))) {

			// We have no errors. We submit to CODB.

	  		// Actual submit to CODB:
	  		$cceClient->setObject("System", $attributes, "mysql");

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// Now handle the set to the CODB object "MySQL" as well:
			$getthisOID = $cceClient->find("MySQL");
			$mysql_settings_exists = 0;
			$mysql_settings = $cceClient->get($getthisOID[0]);
			if (!$mysql_settings['timestamp']) {
		            $mysqlOID = $cceClient->create("MySQL",
		                    array(
		                        'sql_host' => $form_data['sql_host'],
		                        'sql_port' => $form_data['sql_port'],
		                        'sql_root' => $sql_root,
		                        'sql_rootpassword' => $sql_rootpassword,
		                        'savechanges' => time(),
		                        'timestamp' => time()
		                    )
		            );
			}
			else {
		            $mysqlOID = $cceClient->find("MySQL");
		            $cceClient->set($mysqlOID[0], "",
		                    array(
		                        'sql_host' => $form_data['sql_host'],
		                        'sql_port' => $form_data['sql_port'],
		                        'sql_root' => $sql_root,
		                        'sql_rootpassword' => $sql_rootpassword,
		                        'savechanges' => time(),
		                        'timestamp' => time()
		                    )
		            );
			}

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /mysql/mysqlserver");
				exit;
			}
		}

		//
		//-- Own page logic:
		//

		// Location of the backup file:
		$backup_file = "/usr/sausalito/ui/chorizo/ci/application/modules/base/mysql/models/mysql-dump.sql";

		// MySQL PID:
		$pid = `/sbin/pidof mysqld`;
		$pid = str_replace("\n", "", $pid);		

		// Get MySQLd status:
		if ( $pid ) {
			$my_enabled = 1;
			$access="rw";
		} else {
			$my_enabled = 0;
			$access="r";
		}

		// Find out if we have a MySQL-Dump and if so, get its specs:
		if ( file_exists($backup_file) ) {
			$last_ran = date("M j Y - g:i a", filemtime($backup_file));
			$fs = format_bytes(filesize($backup_file));
		}
		else {
		    $last_ran = "- n/a -";
		    $fs = "- n/a -";	    
		}

		// Assemble date, size and status of MySQL-Dump:
	    $dump = date("U");
	    $cfg = array(
		    "dumpdate" => $last_ran,
	        "dumpsize" => $fs,
	        "enabled" => $my_enabled);

	    // Push that info into CODB:
    	$cceClient->setObject("System", $cfg, "mysql");		

		$nuMYSQL = $cceClient->getObject("System", array(), "mysql");

		if ($my_enabled == 1) {
			$nuMYSQL["enabled"] = "1";
		}

		$getthisOID = $cceClient->find("MySQL");
		$mysql_settings_exists = 0;
		$mysql_settings = $cceClient->get($getthisOID[0]);

		if ($mysql_settings['timestamp'] != '') {
		    $mysql_settings_exists = 1;
		}

		// MySQL settings:
		$sql_root               = $mysql_settings['sql_root'];
		$sql_rootpassword       = $mysql_settings['sql_rootpassword'];
		$sql_host               = $mysql_settings['sql_host'];
		$sql_port               = $mysql_settings['sql_port'];

		// Configure defaults:
		if (!$sql_root) { $sql_root = "root"; }
		if (!$sql_host) { $sql_host = "localhost"; }
		if (!$sql_port) { $sql_port = "3306"; }

		if (($sql_host != "localhost") || ($sql_host != "127.0.0.1")) {
		    $mysql_is_local = "1";
    	    $my_sql_host = $sql_host . ":" . $sql_port;
    	    $con_sql_host = $my_sql_host;
		}
		else {
		    $mysql_is_local = "0";
		}
		
		// Test MySQL connection:
		$ret = ini_set("display_errors", "Off");
		$mysql_error = "";
		$mysql_link = @mysql_connect($con_sql_host, $sql_root, $sql_rootpassword) or $mysql_error = mysql_error();
		$ret = ini_set("display_errors", "On");
		if (!$mysql_error) {
		    @mysql_select_db("mysql") or $mysql_error = mysql_error();
		    @mysql_close($mysql_link);
		}
		$mysql_no_connect = "0";
		if ($mysql_error) {
		    // MySQL connection not possible:	    
		    $mysql_status = $i18n->interpolate("[[base-mysql.mysql_status_incorrect]]");
		    $mysql_no_connect = "1";
		}
		else {
		    // MySQL connection can be established:
		    $mysql_status = $i18n->interpolate("[[base-mysql.mysql_status_ok]]");
		    $mysql_no_connect = "0";
		    // Connection is OK, but no root password configured. Append suggestion to set password:
		    if ($sql_rootpassword == "") {
				$mysql_status .= $i18n->interpolate("[[base-mysql.root_has_no_pwd]]");
				$mysql_no_connect = "2";
		    }
		}

		// Generate SQL-Dump if appropriate:
	    if (preg_match("/^mysql\/mysqlserver\/dump\/1$/", uri_string(), $matches)) {
	    	$dump = date("U");
			$dumpcfg = array(
				"username" => $sql_root, 
				"password" => $sql_rootpassword, 
				"dump" => $dump);	    	
			$cceClient->setObject("System", $dumpcfg, "mysql");
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /mysql/mysqlserver");
				exit;
			}
	    }
		// Delete SQL-Dump if appropriate:
	    if (preg_match("/^mysql\/mysqlserver\/delete\/1$/", uri_string(), $matches)) {
	    	$delete = date("U");
			$dumpcfg = array(
				"delete" => $delete);	    	
			$cceClient->setObject("System", $dumpcfg, "mysql");
			$CCEerrors = $cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}
			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /mysql/mysqlserver");
				exit;
			}
	    }
		// Download SQL-Dump if appropriate:
	    if ((preg_match("/^mysql\/mysqlserver\/download\/1$/", uri_string(), $matches)) && (is_file($backup_file))) {
			$this->load->helper('download');
			$data = file_get_contents($backup_file); // Read the file's contents
			$name = 'sqldump.sql';
			force_download($name, $data);
	    }
		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $serverScriptHelper->getHtmlComponentFactory("base-mysql", "/mysql/mysqlserver");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		$product = new Product($cceClient);

		// Set Menu items:
		$BxPage->setVerticalMenu('base_controlpanel');
		$BxPage->setVerticalMenuChild('base_mysql');
		$page_module = 'base_sysmanage';

		$defaultPage = "server";

		$block =& $factory->getPagedBlock("mysql_header", array($defaultPage, "sqlpass", "sqldump"));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//--- server Tab
		//

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("MySQL_Local_divider", ""),
		        $factory->getLabel("MySQL_Local_divider", false),
		        $defaultPage
		        );

		$block->addFormField(
			$factory->getBoolean("enabled", $nuMYSQL["enabled"]),
			$factory->getLabel("mysql_enabled"),
			$defaultPage
		);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("MySQL_Remote_divider", ""),
		        $factory->getLabel("MySQL_Remote_divider", false),
		        $defaultPage
		        );

		// sql_host:
		$line_sql_host = $factory->getTextField("sql_host", $sql_host);
		$line_sql_host->setMaxLength(30);
		$block->addFormField($line_sql_host, $factory->getLabel("sql_host"), $defaultPage);

		// sql_port:
        $line_sql_port =& $factory->getInteger("sql_port", $sql_port, 1, 65535);
        $line_sql_port->showBounds(1);
        $line_sql_port->setWidth(5);
        $block->addFormField(
            $line_sql_port,
            $factory->getLabel('sql_port')
            );

		// People apparently get confused by the username / password dialogue on the first tab
		// and attempt to change the password there - not on the 2nd tab instead.
		// So we now hide the login details for MySQL user "root" and only show it if a 
		// MySQL-connection cannot be established:
        //
		// Possible $mysql_no_connect values:
		//
		// 0 = MySQL connection OK
		// 1 = MySQL connection not OK
		// 2 = MySQL connection OK, but "root" has no password set.

        if ($mysql_no_connect == "1") {
	    	// Show 'enter password' dialogue in first tab:
            $db_details_visibility = "server";
        }
        else {
	    	// Hide 'enter password' dialogue in first tab:
            $db_details_visibility = "hidden";
        }

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("MySQL_Login_divider", ""),
		        $factory->getLabel("MySQL_Login_divider", false),
		        $db_details_visibility
		        );

		// sql_root:
		$line_sql_root = $factory->getTextField("sql_root", $sql_root);
		$line_sql_root->setMaxLength(30);
		$block->addFormField($line_sql_root, $factory->getLabel("sql_root"), $db_details_visibility);

		// sql_rootpassword:
		$line_sql_rootpassword = $factory->getPassword("sql_rootpassword", $sql_rootpassword);
		$line_sql_rootpassword->setOptional("silent");
		$line_sql_rootpassword->setConfirm(FALSE);
		$line_sql_rootpassword->setCheckPass(FALSE);
		$block->addFormField($line_sql_rootpassword, $factory->getLabel("sql_rootpassword"), $db_details_visibility);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider("MySQL_Status_divider", ""),
		        $factory->getLabel("MySQL_Status_divider", false),
		        $defaultPage
		        );

		// sql_status:
		$line_sql_status = $factory->getTextField("sql_status", $mysql_status, 'r');
		$block->addFormField($line_sql_status, $factory->getLabel("sql_status"), $defaultPage);

		//
		//--- sqlpass Tab
		//

		$old_pass = $factory->getPassword("oldpass", "", FALSE, $access);
		$old_pass->setOptional('silent');
		$old_pass->setConfirm(FALSE);
		$old_pass->setCheckPass(FALSE);
		$block->addFormField(
			$old_pass,
			$factory->getLabel("current_pass"),
			"sqlpass");

		$new_pass = $factory->getPassword("newpass", "", TRUE, $access);
		$new_pass->setOptional('silent');
		$block->addFormField(
			$new_pass,
			$factory->getLabel("mysqlpass"),
			"sqlpass");

		//
		//--- sqldump Tab
		//

		// Get results:
		$last_ran = $nuMYSQL["dumpdate"];
		$fs = $nuMYSQL["dumpsize"];

		if ( file_exists($backup_file) ) {
			$last_ran = date("M j Y - g:i a", filemtime($backup_file));
			$fs = format_bytes(filesize($backup_file));
		}

		// generate add mx button:
		$generate_dump = $factory->getButton("/mysql/mysqlserver/dump/1", 'mysqldump', "");
		$array_of_buttons[] = $generate_dump;

		if (file_exists($backup_file) ) {
			$download_button = $factory->getButton("/mysql/mysqlserver/download/1", "download_backup");
			$array_of_buttons[] = $download_button;			
			$delete_button = $factory->getRemoveButton("/mysql/mysqlserver/delete/1", "delete_backup");
			$array_of_buttons[] = $delete_button;
		}

		$buttonContainer = $factory->getButtonContainer("mysqldump", $array_of_buttons);
		$block->addFormField(
			$buttonContainer,
			$factory->getLabel("mysqldump"),
			"sqldump"
		);

		$block->addFormField(
			$factory->getTextField("last_backup", $nuMYSQL["dumpdate"], "r"),
			$factory->getLabel("last_backup"),
			"sqldump");

		$block->addFormField(
			$factory->getTextField("filesize", $nuMYSQL["dumpsize"], "r"),
			$factory->getLabel("filesize"),
			"sqldump");

		//
		//--- Add the buttons
		//

		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/mysql/mysqlserver"));

		// Nice people say goodbye, or CCEd waits forever:
		$cceClient->bye();
		$serverScriptHelper->destructor();

		$page_body[] = $block->toHtml();

		// Out with the page:
	    $BxPage->render($page_module, $page_body);

	}		
}

/*
Copyright (c) 2014 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2014 Team BlueOnyx, BLUEONYX.IT
All Rights Reserved.

1. Redistributions of source code must retain the above copyright 
   notice, this list of conditions and the following disclaimer.

2. Redistributions in binary form must reproduce the above copyright 
   notice, this list of conditions and the following disclaimer in 
   the documentation and/or other materials provided with the 
   distribution.

3. Neither the name of the copyright holder nor the names of its 
   contributors may be used to endorse or promote products derived 
   from this software without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS 
"AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT 
LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS 
FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE 
COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, 
INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, 
BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; 
LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER 
CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT 
LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN 
ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE 
POSSIBILITY OF SUCH DAMAGE.

You acknowledge that this software is not designed or intended for 
use in the design, construction, operation or maintenance of any 
nuclear facility.

*/
?>