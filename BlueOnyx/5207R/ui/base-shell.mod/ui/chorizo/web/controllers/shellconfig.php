<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Shellconfig extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Past the login page this loads the page for /shell/shellconfig.
	 *
	 */

	public function index() {

		$CI =& get_instance();
		
	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $this->load->helper('blueonyx');
	    init_libraries();

  		// Need to load 'BxPage' for page rendering:
  		$this->load->library('BxPage');

	    // Get $CI->BX_SESSION['sessionId'] and $CI->BX_SESSION['loginName'] from Cookie (if they are set):
	    $CI->BX_SESSION['sessionId'] = $CI->input->cookie('sessionId');
	    $CI->BX_SESSION['loginName'] = $CI->input->cookie('loginName');

	    // Line up the ducks for CCE-Connection:
	    include_once('ServerScriptHelper.php');
		$CI->serverScriptHelper = new ServerScriptHelper($CI->BX_SESSION['sessionId'], $CI->BX_SESSION['loginName']);
		$CI->cceClient = $CI->serverScriptHelper->getCceClient();
		$user = $CI->BX_SESSION['loginUser'];
		$i18n = new I18n("base-ssh", $CI->BX_SESSION['loginUser']['localePreference']);
		$system = $CI->getSystem();

		// Initialize Capabilities so that we can poll the access rights as well:
		$Capabilities = new Capabilities($CI->cceClient, $CI->BX_SESSION['loginName'], $CI->BX_SESSION['sessionId']);

		// Define who runs CCEwrap:
		$runas = 'root';

		// -- Actual page logic start:

		// Not 'serverShell'? Bye, bye!
		if (!$Capabilities->getAllowed('serverShell')) {
			// Nice people say goodbye, or CCEd waits forever:
			$CI->cceClient->bye();
			$CI->serverScriptHelper->destructor();
			Log403Error("/gui/Forbidden403");
		}

		//
		//-- Set up where we can find User root's .ssh directory:
		//

		// $ssh_homedir
		$ssh_homedir = '/root/.ssh';

		//
		//--- Activate AutoFeatures
		//

		$auto_features = new AutoFeatures($CI->serverScriptHelper);

		// get System info from CCE:
		$sys_oid = $system['OID'];

		//
		//--- Handle GET Rrequests (create or download actions):
		//

		$get_form_data = $CI->input->get(NULL, TRUE);
		if (isset($get_form_data['action'])) {
			$action = $get_form_data['action'];
		}
		if (isset($get_form_data['type'])) {
			$action_file = $get_form_data['type'];
		}
		if (isset($get_form_data['id'])) {
			$key_id = $get_form_data['id'];
		}
		$allowed_files = array('id_rsa.pub', 'root.pem');

		// On a Demo server we don't want to delete anything:
		if (is_file('/etc/DEMO')) {
			unset($action);
		}

		// Some defaults:
		$available_ssh_key_length = array('2048 bit', '4096 bit', '8192 bit');
		$available_ssh_key_length_selector = array(
				'2048 bit' => '2048', 
				'4096 bit' => '4096', 
				'8192 bit' => '8192'
			);

		// Find out if $ssh_homedir/ exists:
		$ret = $CI->serverScriptHelper->shell("/bin/ls --directory $ssh_homedir", $is_there, $runas, $CI->BX_SESSION['sessionId']);
		if (preg_match('/^\/root\/\.ssh$/', $is_there)) {
			# $ssh_homedir exists
		}
		else {
			# $ssh_homedir does not exists

			# Create it:
			$ret = $CI->serverScriptHelper->shell("/bin/mkdir $ssh_homedir", $nfk, $runas, $CI->BX_SESSION['sessionId']);
			$ret = $CI->serverScriptHelper->shell("/bin/touch $ssh_homedir/authorized_keys", $nfk, $runas, $CI->BX_SESSION['sessionId']);
			$ret = $CI->serverScriptHelper->shell("/bin/chmod 700 -R $ssh_homedir", $nfk, $runas, $CI->BX_SESSION['sessionId']);
			$ret = $CI->serverScriptHelper->shell("/bin/chown root:root -R $ssh_homedir", $nfk, $runas, $CI->BX_SESSION['sessionId']);
			$ret = $CI->serverScriptHelper->shell("/bin/chmod 644 $ssh_homedir/authorized_keys", $nfk, $runas, $CI->BX_SESSION['sessionId']);
		}

		if ((isset($action)) && (isset($action_file))) {

			// We need to be a bit selective as to what filename we allow. Hence
			// the in_array() check here, which weeds out all illegal input
			// and other shenannigans:
			if (($action == 'export') && (in_array($action_file, $allowed_files))) {

				$ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/$action_file", $output, $runas, $CI->BX_SESSION['sessionId']);
			    if ($ret != 0) {
			    	# File not present.
			    }
			    else {
				    // Force download:
				    $exp_filename = 'root' . '_at_' . $system['hostname'] . '.' . $system['domainname'] . '.' . $action_file;
					$this->load->helper('download');
					force_download($exp_filename, $output);
			    }
			}

			// Redirect to correct Tab:
			header("Location: /shell/shellconfig#tabs-2");
			exit;
		}

		// Handle authorized_key item deletion:
		if ((isset($action)) && (isset($key_id))) {
			if ($action == 'akremove') {
				$key_id = urldecode($key_id);

				// Create a unique temporary file name:
				$tempname = tempnam("/var/cache/admserv/", "root_") . ".tmp";

				$ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/authorized_keys|/bin/grep -v $key_id", $finder, $runas, $CI->BX_SESSION['sessionId']);
				if ($ret == 0) {
					write_file($tempname, $finder);
					$ret = $CI->serverScriptHelper->shell("/bin/cp $tempname $ssh_homedir/authorized_keys", $res, $runas, $CI->BX_SESSION['sessionId']);
					$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tempname", $res, $runas, $CI->BX_SESSION['sessionId']);
				}
				else {
					// Check if this is the only key in there:
					$ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/authorized_keys|/usr/bin/wc -l", $finder, $runas, $CI->BX_SESSION['sessionId']);
					$finder = chop($finder);
					if (($finder == "") || ($finder == "0") || ($finder == "1")) {
						// Seems so: Delete it and recreate it:
						$ret = $CI->serverScriptHelper->shell("/bin/rm -f $ssh_homedir/authorized_keys", $finder, $runas, $CI->BX_SESSION['sessionId']);
						$ret = $CI->serverScriptHelper->shell("/bin/touch $ssh_homedir/authorized_keys", $nfk, $runas, $CI->BX_SESSION['sessionId']);
						$ret = $CI->serverScriptHelper->shell("/bin/chown root:root -R $ssh_homedir", $nfk, $runas, $CI->BX_SESSION['sessionId']);
						$ret = $CI->serverScriptHelper->shell("/bin/chmod 644 $ssh_homedir/authorized_keys", $nfk, $runas, $CI->BX_SESSION['sessionId']);
					}
				}
			}
			// Redirect to correct Tab:
			header("Location: /shell/shellconfig#tabs-2");
			exit;
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
		$required_keys = array("");

    	// Set up rules for form validation. These validations happen before we submit to CCE and further checks based on the schemas are done:

		// Empty array for key => values we want to submit to CCE:
    	$attributes = array();
    	// Items we do NOT want to submit to CCE:
    	$ignore_attributes = array();
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
			$config['upload_path'] = '/var/cache/admserv/'; // Safer than /tmp as only 'admserv' has access there.
			$config['allowed_types'] = '*';
			$config['encrypt_name'] = TRUE;
			$config['remove_spaces'] = TRUE;
			$this->load->library('upload', $config);
			$this->upload->do_upload("UploadPubkey");

			// Get the full path and encrypted/randomized file name:
			$data = $this->upload->data();

		    if (is_file($data['full_path'])) {
				$tmp_cert = $data['full_path'];

				// Check if it is a valid public key:
				$ret = $CI->serverScriptHelper->shell("/usr/bin/ssh-keygen -lf $tmp_cert", $keylength, $runas, $CI->BX_SESSION['sessionId']);
				$kl = preg_split('/[\ \n\,]+/', $keylength);
				if ((in_array('(RSA)', $kl)) || (in_array('(DSA)', $kl))) {

	                // Get current authorized_keys:
	                $ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/authorized_keys", $authorized_keys, $runas, $CI->BX_SESSION['sessionId']);

	                // Read uploaded file:
	                $tmp_cert_data = read_file($tmp_cert);

	                // Combine both:
	                $out_data = $authorized_keys . $tmp_cert_data;

	                // This contraption makes sure that there are no blank lines or joint lines
	                // between the two joined files:
					$out_data_cleaned = implode("\n", array_filter(explode("\n", $out_data)));

					// Create a unique temporary file name:
					$tempnameShort = tempnam("/var/cache/admserv/", "root_");
					$tempname =  $tempnameShort . ".tmp";

					// Write the new joint authorized_keys as temporary file:
	                write_file($tempname, $out_data_cleaned);

	                // Move it to the right location and delete the temporary files:
					$ret = $CI->serverScriptHelper->shell("/bin/cp $tempname ~$ssh_homedir/authorized_keys", $output, $runas, $CI->BX_SESSION['sessionId']);
					$ret = $CI->serverScriptHelper->shell("/bin/chmod 644 ~$ssh_homedir/authorized_keys", $output, $runas, $CI->BX_SESSION['sessionId']);
					$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tempname", $output, $runas, $CI->BX_SESSION['sessionId']);
					$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tempnameShort", $output, $runas, $CI->BX_SESSION['sessionId']);
					$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tmp_cert", $output, $runas, $CI->BX_SESSION['sessionId']);

					// Redirect to correct Tab:
					header("Location: /shell/shellconfig#tabs-2");
					exit;
				}
				else {
					$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tmp_cert", $output, $runas, $CI->BX_SESSION['sessionId']);
					$ci_errors[] = new CceError('huh', 0, 'cert', "[[base-ssl.sslImportError4]]");
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

			// We have no errors. We submit to CODB:
		    $CCEerrors = $auto_features->handle('shell.System',
		                                        array(
		                                            'CCE_SERVICES_OID' => $sys_oid,
		                                            'CCE_OID' => $sys_oid
		                                            ));


			// CCE errors that might have happened during submit to CODB:
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// Transform GUI friendly setting to CODB friendly format:
			$bits = $available_ssh_key_length_selector[$attributes['SSH_keylength']];
			$SSHCODB = array("bits" => $bits);

			// Set trigger for key-create:
			if (isset($attributes['key_present'])) {
				if ($attributes['key_present'] == "1") {
					$SSHCODB['keycreate'] = time();
				}
			}

			// Set trigger for cert-create:
			if (isset($attributes['cert_present'])) {
				if ($attributes['cert_present'] == "1") {
					$SSHCODB['certcreate'] = time();
				}
			}

	  		// Actual submit to CODB:
			$CI->cceClient->set($system['OID'], 'SSH', $SSHCODB);

			// CCE errors that might have happened during submit to CODB:
			$CCEerrors = $CI->cceClient->errors();
			foreach ($CCEerrors as $object => $objData) {
				// When we fetch the CCE errors it tells us which field it bitched on. And gives us an error message, which we can return:
				$errors[] = ErrorMessage($i18n->get($objData->message, true, array('key' => $objData->key)) . '<br>&nbsp;');
			}

			// No errors. Reload the entire page to load it with the updated values:
			if ((count($errors) == "0")) {
				header("Location: /shell/shellconfig#tabs-2");
				exit;
			}
		}

		//
	    //-- Generate page:
	    //

		// Prepare Page:
		$factory = $CI->serverScriptHelper->getHtmlComponentFactory("base-ssh", "/shell/shellconfig");
		$BxPage = $factory->getPage();
		$BxPage->setErrors($errors);
		$i18n = $factory->getI18n();

		// Set Menu items:
		$BxPage->setVerticalMenu('base_controlpanel');
		$page_module = 'base_sysmanage';

		$defaultPage = "basicSettingsTab";
		$certKeyPage = "advancedSettingsTab";

		$block =& $factory->getPagedBlock("shell", array($defaultPage, $certKeyPage));
		$block->setLabel($factory->getLabel('[[base-shell.shell]]', false));

		$block->setToggle("#");
		$block->setSideTabs(FALSE);
		$block->setShowAllTabs("#");
		$block->setDefaultPage($defaultPage);

		//
		//-- AutoFeature Block for Basics:
		//
		$auto_features->display($block, 'shell.System',
		                array(
						    'CCE_SERVICES_OID' => $sys_oid,
						    'CCE_OID' => $sys_oid,
		                    'PAGED_BLOCK_DEFAULT_PAGE' => $defaultPage
		                    ));

		//
		//-- SSH Key Management:
		//

		// Start sane:
		$ret = $CI->serverScriptHelper->shell("/bin/rm -f /var/cache/admserv/*.tmp", $junk, $runas, $CI->BX_SESSION['sessionId']);
		$ret = $CI->serverScriptHelper->shell("/bin/rm -f /var/cache/admserv/*.pub", $junk, $runas, $CI->BX_SESSION['sessionId']);

		# authorized_keys:
		$ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/authorized_keys", $authorized_keys, $runas, $CI->BX_SESSION['sessionId']);
	    if ($ret != 0) {
	    	# File not present.
	    }
	    else {
	    	# Turn authorized_keys in an array of arrays:
			$authorized_keys_array = array_filter(explode("\n", $authorized_keys));
			$authorized_keys = array();
			foreach ($authorized_keys_array as $key => $value) {
				$split_lines = preg_split('/[\ \n\,]+/', $value);

				// Detect key length:
				$kl = array();
				$keylength = "";
				// Make sure the line in authorized_keys contains valid data:
				if ((isset($split_lines[0])) && (isset($split_lines[1])) && (isset($split_lines[2]))) {
					// Make sure it contains an RSA or at the worst a DSA key:
					if (($split_lines[0] == "ssh-rsa") || ($split_lines[0] == "ssh-dsa")) {

						// Create a unique temporary file name:
						$tempnameShort = tempnam("/var/cache/admserv/", "root_");
						$tempname =  $tempnameShort . ".tmp";

						// Continue: Write it to a temporary file and parse it:
						write_file($tempname, $split_lines[0] . " " . $split_lines[1] . " " . $split_lines[2]);
						$ret = $CI->serverScriptHelper->shell("/usr/bin/ssh-keygen -lf $tempname", $keylength, $runas, $CI->BX_SESSION['sessionId']);
						$kl = preg_split('/[\ \n\,]+/', $keylength);
						$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tempname", $junk, $runas, $CI->BX_SESSION['sessionId']);
						$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tempnameShort", $junk, $runas, $CI->BX_SESSION['sessionId']);

						if (is_file('/etc/DEMO')) {
							// On a Demo server we don't even want to show the partial payload:
							$split_lines[1] = "XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX";
						}

						$authorized_keys[$key] = array(
													'key_userhost' => $split_lines[2], 
													'key_payload' => $split_lines[1], 
													'key_type' => $split_lines[0],
													'key_lenght' => $kl[0]
													);
					}
				}
			}
	    }

	    # id_rsa:
		$ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/id_rsa", $id_rsa, $runas, $CI->BX_SESSION['sessionId']);
	    if ($ret != 0) {
	    	# File not present.
	    	unset($id_rsa);
	    }
	    else {
	    	// Detect private key length:
	    	$ret = $CI->serverScriptHelper->shell("/usr/bin/openssl rsa -text -noout -in $ssh_homedir/id_rsa|/bin/grep '^Private-Key:'", $id_rsa_length, $runas, $CI->BX_SESSION['sessionId']);
	    	preg_match('/^Private-Key: \((.*)\)$/', $id_rsa_length, $rs_matches);
	    	if (isset($rs_matches[1])) {
	    		$id_rsa_length = $rs_matches[1];
	    	}
	    	$id_rsa_present = '1';
	    }

	    # id_rsa.pub:
		$ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/id_rsa.pub", $id_rsa_pub, $runas, $CI->BX_SESSION['sessionId']);
	    if ($ret != 0) {
	    	# File not present.
	    }
	    else {
	    	# Turn id_rsa.pub in an array of arrays:
			$id_rsa_pub_array = array_filter(explode("\n", $id_rsa_pub));
			$id_rsa_pub = array();
			foreach ($id_rsa_pub_array as $key => $value) {
				$split_lines = preg_split('/[\ \n\,]+/', $value);

				// Detect key length:
				$kl = array();
				$keylength = "";

				// Create a unique temporary file name:
				$tempnameShort = tempnam("/var/cache/admserv/", "root_");
				$tempname =  $tempnameShort . ".tmp";

				// Make sure the line in authorized_keys contains valid data:
				if ((isset($split_lines[0])) && (isset($split_lines[1])) && (isset($split_lines[2]))) {
					// Make sure it contains an RSA or at the worst a DSA key:
					if (($split_lines[0] == "ssh-rsa") || ($split_lines[0] == "ssh-dsa")) {

						write_file($tempname, $split_lines[0] . " " . $split_lines[1] . " " . $split_lines[2]);
						$ret = $CI->serverScriptHelper->shell("/usr/bin/ssh-keygen -lf $tempname", $keylength, $runas, $CI->BX_SESSION['sessionId']);
						$kl = preg_split('/[\ \n\,]+/', $keylength);
						$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tempname", $junk, $runas, $CI->BX_SESSION['sessionId']);
						$ret = $CI->serverScriptHelper->shell("/bin/rm -f $tempnameShort", $junk, $runas, $CI->BX_SESSION['sessionId']);

						$id_rsa_pub = array(
													'key_userhost' => $split_lines[2], 
													'key_payload' => $split_lines[1], 
													'key_type' => $split_lines[0],
													'key_lenght' => $kl[0]
													);
						$id_rsa_pub_present = '1';
					}
				}
			}
	    }

		//
		//-- SSH Cert Management:
		//

	    # $CI->BX_SESSION['loginName'].pem:
		$ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/root.pem", $root_pem, $runas, $CI->BX_SESSION['sessionId']);
	    if ($ret != 0) {
	    	# File not present.
	    	unset($root_pem);
	    	$root_pem_present = '0';
	    }
	    else {
	    	// Detect private key length:
	    	$ret = $CI->serverScriptHelper->shell("/usr/bin/openssl rsa -text -noout -in $ssh_homedir/root.pem|/bin/grep '^Private-Key:'", $root_pem_length, $runas, $CI->BX_SESSION['sessionId']);
	    	preg_match('/^Private-Key: \((.*)\)$/', $root_pem_length, $root_pem_matches);
	    	if (isset($root_pem_matches[1])) {
	    		$root_pem_length = $root_pem_matches[1];
	    	}
	    	$root_pem_present = '1';
	    }

	    # $CI->BX_SESSION['loginName'].pem.pub:
		$ret = $CI->serverScriptHelper->shell("/bin/cat $ssh_homedir/root.pem.pub", $root_pem_pub, $runas, $CI->BX_SESSION['sessionId']);
	    if ($ret != 0) {
	    	$root_pem_pub_present = '0';
	    }
	    else {
	    	# Turn id_rsa.pub in an array of arrays:
			$root_pem_pub_array = array_filter(explode("\n", $root_pem_pub));
			$root_pem_pub = array();
			foreach ($root_pem_pub_array as $key => $value) {
				$split_lines = preg_split('/[\ \n\,]+/', $value);

				// Detect key length:
				$kl = array();
				$keylength = "";
				$ret = $CI->serverScriptHelper->shell("/usr/bin/ssh-keygen -lf $ssh_homedir/root.pem.pub", $keylength, $runas, $CI->BX_SESSION['sessionId']);
				$kl = preg_split('/[\ \n\,]+/', $keylength);

				$root_pem_pub = array(
											'key_userhost' => $split_lines[2], 
											'key_payload' => $split_lines[1], 
											'key_type' => $split_lines[0],
											'key_lenght' => $kl[0]
											);
				$root_pem_pub_present = '1';
			}
	    }

	    //---

		$SSHsettings = $CI->cceClient->get($user['OID'], 'SSH');

		// Show selector for SSH key length:
		$available_ssh_key_length_selector = array_flip($available_ssh_key_length_selector);
		$bits = $available_ssh_key_length_selector[$SSHsettings['bits']];
        $SSHkeyLength = $factory->getMultiChoice("SSH_keylength", array_values($available_ssh_key_length));
        $SSHkeyLength->setSelected($bits, true);
        $SSHkeyLength->setOptional(false);
        $block->addFormField(
	        	$SSHkeyLength, 
	        	$factory->getLabel("SSH_keylength"), 
	        	$certKeyPage
        	);

		// Do we have a public and private key?
		if ((isset($id_rsa_length)) && (isset($id_rsa_present)) && (isset($id_rsa_pub_present)))  {

	    	// If we're currently using a key length that is not yet
		   	// listed, then we add it to the array:
	    	if (!in_array($id_rsa_length, $available_ssh_key_length)) {
	    		$available_ssh_key_length[] = $id_rsa_length;
	    		sort($available_ssh_key_length);
	    	}

	    	$nokey_info = $i18n->getClean("[[base-ssh.keys_present_msg]]", false, array("bits" => $id_rsa_length));
			$block->addFormField(
				$factory->getTextField("key_present", $nokey_info , 'r'),
				$factory->getLabel("key_present"),
				$certKeyPage
			);
			$gotKey = '1';
		}
		else {
			// Create Key:
			$block->addFormField(
				$factory->getBoolean("key_present", '0' , 'rw'),
				$factory->getLabel("key_present"),
				$certKeyPage
			);
		}

		// Do we have a public and private certificate?
		if (($root_pem_present == '1') && ($root_pem_pub_present == '1') && (isset($root_pem_pub['key_lenght'])))  {

			$Cert_info = $i18n->getClean("[[base-ssh.certs_present_msg]]", false, array("bits" => $root_pem_pub['key_lenght']));
			$block->addFormField(
				$factory->getTextField("cert_present", $Cert_info , 'r'),
				$factory->getLabel("cert_present"),
				$certKeyPage
			);
			$gotCert = '1';
		}
		else {
			// Create Cert:
			$block->addFormField(
				$factory->getBoolean("cert_present", '0' , 'rw'),
				$factory->getLabel("cert_present"),
				$certKeyPage
			);
		}

		//
		//-- Generate authorized_keys scrollList:
		//

		$scrollList = $factory->getScrollList("AuthKeyList", array("key_type", "key_payload", "key_userhost", "bits", "listAction"), array()); 
	    $scrollList->setAlignments(array("left", "left", "left", "center", "center"));
	    $scrollList->setDefaultSortedIndex('0');
	    $scrollList->setSortOrder('ascending');
	    $scrollList->setSortDisabled(array('4'));
	    $scrollList->setPaginateDisabled(FALSE);
	    $scrollList->setSearchDisabled(FALSE);
	    $scrollList->setSelectorDisabled(FALSE);
	    $scrollList->enableAutoWidth(FALSE);
	    $scrollList->setInfoDisabled(FALSE);
	    $scrollList->setColumnWidths(array("100", "400", "170", "34", "35")); // Max: 739px

	    // Populate Scrollist:
	    if (is_array($authorized_keys)) {
		    foreach ($authorized_keys as $key => $kdata) {
				$scrollList->addEntry(array(
						    $kdata['key_type'],
						    substr($kdata['key_payload'], 0, 15). " ... " . substr($kdata['key_payload'], -15),
							$kdata['key_userhost'],
							$kdata['key_lenght'],
							'<a class="lb" href="/shell/shellconfig?action=akremove&id=' . urlencode($kdata['key_userhost']) . '"><button class="tiny icon_only div_icon tooltip hover dialog_button" title="' . $i18n->getHtml("AKRemove") . '"><div class="ui-icon ui-icon-trash"></div></button></a><br>'
						    ));
		    }
		}

		// Add divider:
		$didi = $i18n->getHtml("[[base-ssh.AuthKeyList]]", false, array("authkey_file" => "$ssh_homedir/authorized_keys"));
		$block->addFormField(
		        $factory->addBXDivider('AuthKeyList', ""),
		        $factory->getLabel('AuthKeyList', false, array('authkey_file' => "$ssh_homedir/authorized_keys")),
		        $certKeyPage
		        );

		// Push out the Scrollist:
		$block->addFormField(
			$factory->getRawHTML("AuthKeyList", $scrollList->toHtml()),
			$factory->getLabel("AuthKeyList"),
			$certKeyPage
		);

		// Add divider:
		$block->addFormField(
		        $factory->addBXDivider('UploadPubKeyHead', ""),
		        $factory->getLabel('UploadPubKeyHead', false, array('authkey_file' => "$ssh_homedir/authorized_keys")),
		        $certKeyPage
		        );

		$block->addFormField(
		    $factory->getFileUpload('UploadPubkey', ""),
		    $factory->getLabel('UploadPubkey'),
		    $certKeyPage
		    );

	    // Create Buttons for downloading (if we have something to download!)
		$export_buttons = array();
		if (is_array($id_rsa_pub)) {
			$export_buttons[] = $factory->getButton('/shell/shellconfig?action=export&type=id_rsa.pub', 'export_id_rsa_pub');
		}
		if ($root_pem_present == "1") {
			$export_buttons[] = $factory->getButton("/shell/shellconfig?action=export&type=root.pem", 'export_root_pem');
		}

		// Add Button-Container with download buttons:
		if (count($export_buttons) > '0') {

			// Add divider:
			$block->addFormField(
			        $factory->addBXDivider('keyDownloadHeader', ""),
			        $factory->getLabel('keyDownloadHeader', false),
			        $certKeyPage
			        );

			$buttonContainer_a = $factory->getButtonContainer("", $export_buttons);

			// Push out the Button-Container:
			$block->addFormField(
				$factory->getRawHTML("", $buttonContainer_a->toHtml()),
				$factory->getLabel(""),
				$certKeyPage
			);
		}

		// Extra header for the "do you really want to delete" dialog:
		$BxPage->setExtraHeaders('
				<script type="text/javascript">
				$(document).ready(function () {

				  $("#dialog").dialog({
				    modal: true,
				    bgiframe: true,
				    width: 500,
				    height: 280,
				    autoOpen: false
				  });

				  $(".lb").click(function (e) {
				    e.preventDefault();
				    var hrefAttribute = $(this).attr("href");

				    $("#dialog").dialog(\'option\', \'buttons\', {
				      "' . $i18n->getHtml("[[palette.remove]]") . '": function () {
				        window.location.href = hrefAttribute;
				      },
				      "' . $i18n->getHtml("[[palette.cancel]]") . '": function () {
				        $(this).dialog("close");
				      }
				    });

				    $("#dialog").dialog("open");

				  });
				});
				</script>');

		// Add the buttons
		$block->addButton($factory->getSaveButton($BxPage->getSubmitAction()));
		$block->addButton($factory->getCancelButton("/shell/shellconfig"));

		$page_body[] = $block->toHtml();

		// Add hidden Modal for Delete-Confirmation:
        $page_body[] = '
			<div class="display_none">
			    		<div id="dialog" class="dialog_content narrow no_dialog_titlebar" title="' . $i18n->getHtml("[[base-ssh.AKRemoveConfirmNeutral]]") . '">
			                <div class="block">
			                        <div class="section">
			                                <h1>' . $i18n->getHtml("[[base-ssh.AKRemoveConfirmNeutral]]") . '</h1>
			                                <div class="dashed_line"></div>
			                                <p>' . $i18n->getHtml("[[base-ssh.removeConfirmInfo]]") . '</p>
			                        </div>
			                </div>
			        	</div>
			</div>';

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