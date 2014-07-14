<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Validation extends MX_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/validation
	 *	- or -  
	 * 		http://example.com/index.php/validation/index
	 *	- or -
	 * 		http://example.com/validation/
	 *
	 * This dynamically creates the validation.js code needed for
	 * dynamic input validation via jQuery. It parses all schema
	 * files from '/usr/sausalito/schemas/', pulls out the <typedef>
	 * tags and extends the jSON script with the native BlueOnyx 
	 * rules for input validation. While we're at it, we also localize
	 * the error messages in the jSON output with i18N.
	 *
	 * PLEASE NOTE: A bad regular expression in the schema file can break
	 * the jSON stuff. For example the 'network' regexp in basetypes.schema
	 * is such an example. We ignore it in this case. We also substitute
	 * a slightly different rule for fqdn validation than in the actual
	 * schema file for the 'fqdn'.
	 *
	 */

	/* On a stock BlueOnyx this boils down to the following checks:
	*
	*	scalar - /usr/sausalito/schemas/basetypes.schema
	*	word - /usr/sausalito/schemas/basetypes.schema
	*	alphanum - /usr/sausalito/schemas/basetypes.schema
	*	memdisk - /usr/sausalito/schemas/basetypes.schema 			<-- For numbers with or without memory related unit (K, KB, M, MB, T, TB)
	*	alphanum_plus - /usr/sausalito/schemas/basetypes.schema
	*	alphanum_plus_multiline - /usr/sausalito/schemas/basetypes.schema	<-- for multiple alphanum_plus
	*	int - /usr/sausalito/schemas/basetypes.schema
	*	uint - /usr/sausalito/schemas/basetypes.schema
	*	boolean - /usr/sausalito/schemas/basetypes.schema
	*	ipaddr - /usr/sausalito/schemas/basetypes.schema
	*	email_address - /usr/sausalito/schemas/basetypes.schema	    <-- for a single email address
	*	email_addresses - /usr/sausalito/schemas/basetypes.schema	<-- for multiple email addresses
	*	netmask - /usr/sausalito/schemas/basetypes.schema
	*	fqdn - /usr/sausalito/schemas/basetypes.schema
	*	hostname - /usr/sausalito/schemas/basetypes.schema
	*	domainname - /usr/sausalito/schemas/basetypes.schema 		<-- for a single domain name
	*	domainnames - /usr/sausalito/schemas/basetypes.schema		<-- for multiple domain names (this regexp needs much more work!)
	*	password - /usr/sausalito/schemas/basetypes.schema
	*	cgiAccess - /usr/sausalito/schemas/base/apache/apache.schema
	*	vhostDocRoot - /usr/sausalito/schemas/base/apache/apache.schema
	*	emailQueueTime - /usr/sausalito/schemas/base/email/email.schema
	*	mail_alias - /usr/sausalito/schemas/base/email/email.schema
	*	fq_email_address - /usr/sausalito/schemas/base/email/email.schema
	*	schedule_type - /usr/sausalito/schemas/base/schedule/schedule.schema
	*	schedule_filename_type - /usr/sausalito/schemas/base/schedule/schedule.schema
	*	dns_record_type - /usr/sausalito/schemas/base/dns/dns.schema
	*	dns_email - /usr/sausalito/schemas/base/dns/dns.schema
	*	dns_zone_format - /usr/sausalito/schemas/base/dns/dns.schema
	*	mx_priority - /usr/sausalito/schemas/base/dns/dns.schema
	*	fullName - /usr/sausalito/schemas/base/user/user.schema
	*	userNameGenMode - /usr/sausalito/schemas/base/user/user.schema
	*	amstate - /usr/sausalito/schemas/base/am/am.schema
	*	statsReport - /usr/sausalito/schemas/base/sitestats/sitestats.schema
	*	swidType - /usr/sausalito/schemas/base/swupdate/update.schema
	*	versionType - /usr/sausalito/schemas/base/swupdate/update.schema
	*	installStateMode - /usr/sausalito/schemas/base/swupdate/update.schema
	*	pType - /usr/sausalito/schemas/base/swupdate/update.schema
	*	uType - /usr/sausalito/schemas/base/swupdate/update.schema
	*	intervalType - /usr/sausalito/schemas/base/swupdate/update.schema
	*	status - /usr/sausalito/schemas/base/swupdate/update.schema
	*	notifyType - /usr/sausalito/schemas/base/swupdate/update.schema
	*	bootproto - /usr/sausalito/schemas/base/network/network.schema
	*	internetMode - /usr/sausalito/schemas/base/network/network.schema
	*	interface - /usr/sausalito/schemas/base/network/network.schema
	*	devicename - /usr/sausalito/schemas/base/sauce-basic/basic.schema
	*	accountname - /usr/sausalito/schemas/base/sauce-basic/basic.schema
	*	knownFs - /usr/sausalito/schemas/base/disk/disk.schema
	*	diskDevice - /usr/sausalito/schemas/base/disk/disk.schema
	*	wakemode - /usr/sausalito/schemas/base/power/power.schema
	*	powermode - /usr/sausalito/schemas/base/power/power.schema
	*	telnetaccess - /usr/sausalito/schemas/base/telnet/telnet.schema
	*	sslCountry - /usr/sausalito/schemas/base/ssl/ssl.schema
	*	postPolicy - /usr/sausalito/schemas/base/mailman/MailMan.schema
	*	subPolicy - /usr/sausalito/schemas/base/mailman/MailMan.schema
	*	mailman_name - /usr/sausalito/schemas/base/mailman/MailMan.schema
	*
	*/

	public function index() {

		$CI =& get_instance();
		
	    // We load the BlueOnyx helper library first of all, as we heavily depend on it:
	    $this->load->helper('blueonyx');
	    init_libraries();

	    $data = array();

		// Location of the directory with the BX Schema files:
		$menu_XML_dir = '/usr/sausalito/schemas/';

		// Get a fileMap of /usr/sausalito/schemas/:
		$map = directory_map($menu_XML_dir, FALSE, FALSE);

		// Pre-define array for our XML schema files:
		$xml_files = array();

		// The fileMap $map is pretty detailed. Let us build an array that has all
		// paths to XML files in it and contains them in an easily accessible way. 
		foreach($map as $key => $val) {
			if (is_array($val)) {
				foreach($map[$key] as $key_zwo => $val_zwo) {
					// This handles 'base' and 'vendor' dirs:
					if (is_array($map[$key][$key_zwo])) {
						foreach($map[$key][$key_zwo] as $key_drei => $val_drei) {
			  				// We're only interested in .schema files:
			  				if (preg_match('/\.schema$/', $val_drei)) {
								$xml_files[] = $menu_XML_dir . "$key" . '/' .  $key_zwo . '/' . $val_drei;
			  				}
						}
					}
					else {
			  			// This handles short pathed XML locations:
			  			// We're only interested in .schema files:
			  			if (preg_match('/\.schema$/', $map[$key][$key_zwo])) {
							$xml_files[] = $menu_XML_dir . "$key" . '/' .  $map[$key][$key_zwo];
			  			}
					}
				}
			}
			else {
	  			// This handles the toplevel dir:
	  			// We're only interested in .schema files:
	  			if (preg_match('/\.schema$/', $map[$key])) {
					$xml_files[] = $menu_XML_dir . $map[$key];
				}
			}
		}

		// Set up an empty $_Schema_Items array:
		$_Schema_Items = array();

		// Populate $_Schema_Items:
		// Unfortunately our *.schema XML files are really dirty and neither 
		// simplexml or DOMXML can get at the data without throwing fits. So we have
		// to rely on simple regular expressions and preg_match_all() and preg_match()
		// to populate $_Schema_Items with the typref data that we want:
		for($i = 0; $i < count($xml_files); $i++) {
			// Read in each XML file:
			$xml_data = read_file($xml_files[$i]);

			// Preg match all <typedef(.*)/> tags:
			preg_match_all('#<typedef(.*)/>#isU',$xml_data,$matches, PREG_SET_ORDER);

			foreach ($matches as $key => $val) {
				if (isset($val)) {
					foreach ($val as $key_zwo => $val_zwo) {
						if (isset($val_zwo)) {
							preg_match('#name\s{0,3}=\s{0,3}"(.*)"#isU',$val_zwo,$name);
							preg_match('#type\s{0,3}=\s{0,3}"(.*)"#isU',$val_zwo,$type);
							if (preg_match('#data\s{0,3}=\s{0,3}"(.*)"#isU',$val_zwo,$data)) {
								$my_data = $data[1];
							}
							else {
								$my_data = "";
							}
							if (preg_match('#errmsg\s{0,3}=\s{0,3}"(.*)"#isU',$val_zwo,$errmsg)) {
								$my_errmsg = $errmsg[1];
							}
							else {
								$my_errmsg = "";
							}
							$_Schema_Items[$name[1]] = array(
												'type' => $type[1],
												'data' => json_encode($my_data), 
												'errmsg' => $my_errmsg,
												'schemafile' => $xml_files[$i]
												);
						}
					}
				}
			}
		}

	    // Get $sessionId and $loginName from Cookie (if they are set):
	    $sessionId = $CI->input->cookie('sessionId');
	    $loginName = $CI->input->cookie('loginName');

	    // Get the IP address of the user accessing the GUI:
	    $userip = $CI->input->ip_address();

	    // locale and charset setup:
	    $ini_langs = initialize_languages(FALSE);
	    $locale = $ini_langs['locale'];
	    $localization = $ini_langs['localization'];
	    $charset = $ini_langs['charset'];

	    // Get Locale from Cookie:
	    $CookieLocale = $CI->input->cookie('locale');

	    if ((isset($CookieLocale)) && ($CookieLocale != "")) {
			$locale = $CookieLocale;
		}

	    // Set headers:
	    $CI->output->set_header("Cache-Control: no-store, no-cache, must-revalidate");
	    $CI->output->set_header("Cache-Control: post-check=0, pre-check=0");
	    $CI->output->set_header("Pragma: no-cache"); 
	    $CI->output->set_header("Content-language: $localization");
	    $CI->output->set_header("Content-type: text/html; charset=$charset");

		$i18n = new I18n("palette", $locale);

		// Prepare the messages output for our jQuery script:

		// These are for the checks that are already included in the stock validator.js:
		$messages = array(
					'required: "' . $i18n->getHtml("[[palette.val_required]]") . '"',
					'remote: "' . $i18n->getHtml("[[palette.val_remote]]") . '"',
					'email: "' . $i18n->getHtml("[[palette.val_email]]") . '"',
					'url: "' . $i18n->getHtml("[[palette.val_url]]") . '"',
					'date: "' . $i18n->getHtml("[[palette.val_date]]") . '"',
					'dateISO: "' . $i18n->getHtml("[[palette.val_dateISO]]") . '"',
					'number: "' . $i18n->getHtml("[[palette.val_number]]") . '"',
					'digits: "' . $i18n->getHtml("[[palette.val_digits]]") . '"',
					'creditcard: "' . $i18n->getHtml("[[palette.val_creditcard]]") . '"',
					'equalTo: "' . $i18n->getHtml("[[palette.val_equalTo]]") . '"',
					'accept: "' . $i18n->getHtml("[[palette.val_accept]]") . '"',
					'maxlength: $.validator.format("' . $i18n->getHtml("[[palette.val_maxlength]]") . '")',
					'minlength: $.validator.format("' . $i18n->getHtml("[[palette.val_minlength]]") . '")',
					'rangelength: $.validator.format("' . $i18n->getHtml("[[palette.val_rangelength]]") . '")',
					'range: $.validator.format("' . $i18n->getHtml("[[palette.val_range]]") . '")',
					'max: $.validator.format("' . $i18n->getHtml("[[palette.val_max]]") . '")',
					'min: $.validator.format("' . $i18n->getHtml("[[palette.val_min]]") . '")'
			);

		// We now add our schema based BlueOnyx checks to that list:
		foreach ($_Schema_Items as $key => $value) {
			if ($_Schema_Items[$key]['errmsg']) {
				// Schema rule has own error message:
				$messages[] = $key . ": " . '"' . $i18n->getHtml($_Schema_Items[$key]['errmsg']) . '"';
			}
			else {
				// Schema rule has no own error message. Add the default one ("Fix your input!"):
				$messages[] = $key . ": " . '"' . $i18n->getHtml("[[palette.val_remote]]") . '"';
			}
		}

		// Assemble the output of our new jSON messages:
		$data['messages'] = implode(',', $messages);

		// Next item: The actual checks. They look roughly like this:
		//
		//		// http://docs.jquery.com/Plugins/Validation/Methods/dateISO
		//		dateISO: function(value, element) {
		//			return this.optional(element) || /^\d{4}[\/-]\d{1,2}[\/-]\d{1,2}$/.test(value);
		//		},

		// The 'delegate' stuff looks like this:
		//
		//		"[type='number'], [type='search'] ,[type='tel'], [type='url'], " +

		// Class stuff:
		//
		//		creditcard: {creditcard: true}

		$rules = array();
		$dg = "";
		$delegate = "";
		$class = array();
		foreach ($_Schema_Items as $key => $value) {
			if ($key == "fqdn") {
				// For FQDN we cheat a bit. The rule in the schema sucks and we can't change it with this complex one. So we use the better rule here:
				$rule = "\n// BlueOnyx Rule: " . $_Schema_Items[$key]['schemafile'] . "\n";
				$rule .= $key . ': function(value, element) {' . "\n";
				$rule .= '	return this.optional(element) || /' . '(?=^.{1,254}$)(^(?:(?!\d+\.)[a-zA-Z0-9_\-]{1,63}\.?)+(?:[a-zA-Z]{2,})$)' . '/.test(value);' . "\n";
				$rule .= '}';
				$rules[] = $rule;
				$dg .= '[type=\'' . $key . '\'], ';
				$class[] = "		" . $key . ': {' . $key . ': true}';
			}
			elseif ($key == "network") {
				// Bad rule. Skip.

			}
			else {
				$rule = "\n// BlueOnyx Rule: " . $_Schema_Items[$key]['schemafile'] . "\n";
				$rule .= $key . ': function(value, element) {' . "\n";
				$rule .= '	return this.optional(element) || /' . strtr(json_decode($_Schema_Items[$key]['data']), array('\\\\' => "\\")) . '/.test(value);' . "\n";
				$rule .= '}';
				$rules[] = $rule;
				$dg .= '[type=\'' . $key . '\'], ';
				$class[] = "		" . $key . ': {' . $key . ': true}';
			}

		}

		// Assemble the output of our new jSON rules:
		$data['rules'] = implode(',', $rules);

		// Assemble delegate output of our new jSON rules:
		$data['delegate'] = "\"" . $dg . "\" +";

		// Assemble the class output of our new jSON rules:
		$data['class'] = implode(",\n", $class);

		// Set Localization:
		$data['localization'] = $localization;

		// Show the data:
		$this->load->view('validation_view', $data);

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