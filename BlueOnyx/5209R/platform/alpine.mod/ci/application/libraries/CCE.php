<?php

/**
 * CCE.php
 *
 * BlueOnyx PHP connector for interfacing Chorizo directly via UNIX Socket with CCEd.
 *
 * Description:
 * ============
 *
 * So far BlueOnyx (and the predecessors BlueQuartz and the RaQ550) used a loadable PHP 
 * Zend API module to interface PHP with CCEd. This was done for obvious performance reasons
 * on the really limited (ancient) hardware. The drawback of this is naturally that the 
 * PHP module must be compatible to the PHP version that AdmServ uses and it must be compiled
 * against said PHP version. Upgrading PHP then (naturally) was out of the question as it
 * would have required a recompile of the 'cce.so' module again.
 *
 * Now with PHP-5.4 (and later) we have the problem that the Zend API for modules has changed.
 * The current code of the cce.so module is no longer compatible and (as is) this is a bit
 * beyond our limited capabilities of fixing. 
 *
 * Steven Howes asked the right question on the BlueOnyx developer list: 
 *
 * "I wonder ... is there any reason we can’t communicate with CCE in pure PHP?"
 *
 * Well, actually we can. So I modified CceClient.php to check if the cce.so module is loaded
 * and available. If it is, we use it (for performance reasons). If it is not available, then
 * CceClient.php will load CCE.php and will use the functions within to communicate via PHP
 * over the Unix socket of CCEd.
 *
 * This class here simply mimicks all ccephp_*() functions that the cce.so PHP Zend API module
 * would normally provide. But instead of calling the module functions we use the PHP commands
 * stream_socket_client(), fwrite() and stream_get_contents() to connect to the Unix socket of
 * CCEd, to send our commands and to listen for the response.
 *
 * There are several catches:
 *
 * - This is 3-4 times slower than doing it via cce.so
 * - This is more complicated because the stream socket functions require us to do all our
 *   magick before we send the BYE to CCEd. Only when we send the BYE we actually get any
 *   response from CCEd that tell us if the transaction(s) went through or not. But by always
 *   sending a BYE we also make sure that no CCEd child processes linger around if a GUI page
 *   forgets to do so. Which certainly is a bonus.
 * - Another bonus is that for the first time ever it is now possible to upgrade PHP on 
 *   BlueOnyx a hell of a lot easier. We will still actually discourage to do so, because we
 *   can only support the GUI on PHP versions that we actually tested it under. 
 * - This step also allows us to port the Chorizo GUI to EL7 (and clones) which use PHP-5.4.
 *
 * This code might still need some work and it sure needs a hell of a lot of testing.
 *
 * @package   CCE
 * @author    Michael Stauber
 * @link      http://www.blueonyx.it
 * @license   http://devel.blueonyx.it/pub/BlueOnyx/licenses/SUN-modified-BSD-License.txt
 * @version   2.0
 */

class CCE {
  var $socketPath;
  var $errorno;
  var $errorstr;
  var $self;
  var $DTS;
  var $DelayedTransactions;

  // DTS state: Keep a record of the delayed transactions
  // for later execution:
  function addDelayedTransaction($transaction) {
    if (!is_array($this->DelayedTransactions)) {
      $this->DelayedTransactions = array();
    }
    $this->DelayedTransactions[] = $transaction;
  }

  // DTS state: Return an array of all stored delayed transactions:
  function getDelayedTransaction() {
    if (isset($this->DelayedTransactions)) {
      // We have delayed transactions. Return them:
      return $this->DelayedTransactions;
    }
    else {
      // We don't have delayed transactions.
      // Return empty array to play it safe.
      return array();
    }
  }

  // Change DTS state:
  function setDTS($bgn) {
    $this->DTS = $bgn;
  }

  // Get DTS state:
  function getDTS() {
    // Define the three states of DTS:
    $beginStates = array("TRUE", "FALSE", "COMMIT");
    // If DTS is not set, DTS is in default state
    // and transactions are NOT delayed.
    if (!isset($this->DTS)) {
      // Return default:
      $this->DTS = "FALSE";
    }
    // Check if the current state is one of the defined (possible)
    // states. If so, return the current state:
    if (in_array($this->DTS, $beginStates)) {
      return $this->DTS;
    }
    else {
      // State is not one of the three default states.
      // So return the default state just to play it safe:
      return "FALSE";
    }
  }

  // Set path to the Unix socket of CCEd:
  function setSocketPath($socketPath) {
    $this->socketPath = $socketPath;
  }

  // Get path to the Unix socket of CCEd:
  function getSocketPath() {
    if (!isset($this->socketPath)) {
      $system = new System();
      $this->socketPath = $system->getConfig("ccedSocketPath");
    }
    return $this->socketPath;
  }

  function setERRNO($errorno) {
    $this->errorno = $errorno;
  }

  function getERRNO() {
    if (isset($this->errorno)) {
      return $this->errorno;
    }
    else {
      return '';
    }
  }

  function setERRSTR($errorstr) {
    $this->errorstr = $errorstr;
  }

  function getERRSTR() {
    if (isset($this->errorstr)) {
      return $this->errorstr;
    }
    else {
      return '';
    }
  }

  // Initialize CCE:
  function ccephp_new($message = "WHOAMI") {

    // Note on $message:
    //
    // If no $message is set, we default on sending a "WHOAMI". The simple
    // reason is that on each connect we need to execute at least one
    // successful command to set self[success] to TRUE to determine if CCEd
    // is working or not. 

    // DTS state:
    // Start sane and check if we are in delayed transaction state (DTS).
    // For more info on that see ccephp_begin() and ccephp_commit()

    // Get DTS state:
    $this->DTS = CCE::getDTS();

    // Get all delayed transactions (if there are any):
    $this->DelayedTransactions = CCE::getDelayedTransaction();

    // Initialize Socket:
    $socketPath = CCE::getSocketPath();

    $this->self['rdsock'] = $socketPath;
    $this->self['wrsock'] = $socketPath;
    $this->self['version'] = '';
    $this->self['suspendedmsg'] = '';
    $this->self['debug'] = '0';
    $this->self['rollbackflag'] = '0';
    $this->self['event'] = '';
    $this->self['event_oid'] = '';
    $this->self['event_namespace'] = '';
    $this->self['opt_namespace'] = '';
    $this->self['event_property'] = '';
    $this->self['event_class'] = '';
    $this->self['event_object'] = '';
    $this->self['event_old'] = '';
    $this->self['event_new'] = '';
    $this->self['event_create'] = '0';
    $this->self['event_destroy'] = '0';
    $this->self['msgref'] = '';
    $this->self['domain'] = '';

    if ($this->DTS == "FALSE") {
      // We are *not* in delayed transaction mode.
      // Execute the commands directly:
      $result = CCE::_cceclient($message);
    }
    elseif ($this->DTS == "COMMIT") {
      // Delayed transaction state has reached the stage where we
      // want to commit the entire set of stored transactions:

      // Add the final "COMMIT" to our array of delayed transactions:
      CCE::addDelayedTransaction("COMMIT");

      // Combine the stored delayed transactions into a single call to CCE:
      if (isset($this->DelayedTransactions)) {
        $combinedMessage = "";
        foreach ($this->DelayedTransactions as $num => $message) {
          $combinedMessage .= $message . "\n";
        }
        // Push out the combined delayed transactions in one go:
        $result = CCE::_cceclient($combinedMessage);
      }
      // Leave delayed transaction state:
      CCE::setDTS("FALSE");

    }
    else {
      // We are in delayed transaction state. We do not execute queries
      // directly. Instead we wait for the COMMIT and just store the
      // delayed transactions away for later execution:
      CCE::addDelayedTransaction($message);
    }

    if (isset($this->self['success'])) {
      if ($this->self['success'] == "1") {
        return TRUE;
      }
      return FALSE;
    }
    else {
      return FALSE;
    }
  }

  // Connect to CCE:
  function ccephp_connect($socketPath) {
    // Set socketPath:
    CCE::setSocketPath($socketPath);
    return CCE::ccephp_new();
  }

  // Disconnect from CCE:
  function ccephp_bye() {
    // We do a "BYE" on every connection. So there is really no need 
    // to send a second "BYE" separately. Hence we just return TRUE
    // and be done with it instead of: return CCE::ccephp_new("BYE");
    return TRUE;
  }

  // Invalidate SessionID:
  function ccephp_endkey() {
    // Here is the catch: 
    // Issuing 'ENDKEY' to CCEd does not (as advertised) invalidate
    // the sessionId. We actually have to manually delete the cookie
    // as well. Or we run into a nice "AUTHKEY failed" loop:
    delete_cookie("sessionId");
    $this->setSessionId('');
    $this->SessionId = '';
    return CCE::ccephp_new("ENDKEY");
  }

  // the legacy find command
  // returns: matching $oids
  // usage: $oids = $cce->find($class, array( 'property' => 'value'));
  function ccephp_find($class, $vars, $key="", $crit="0") {
    if ($vars == "") {
      CCE::ccephp_new("FIND $class");
    }
    else {
      $varline = " ";
      foreach ($vars as $key => $value) {
        $varline .= "$key = \"" . CCE::_escape($value) . "\" ";
      }
      $varline = rtrim($varline);
      CCE::ccephp_new("FIND $class $varline");
    }
    return $this->self['oidlist'];
  }

  // Description: advanced method of finding objects
  // $class : class to find
  // $vars : exact-match criteria
  // $revars : regex-match criteria
  // $sorttype : name of sorttype to use (optional)
  //           : listed in basetypes.schema, valid types are
  //           : ascii, old_numeric, locale, ip, hostname
  // $sortprop : name of property (key) on which to sort
  // returns: matching $oids
  // usage: $oids = $cce->findx($class, $vars, $regex_vars, $sorttype, $sortkey);
  function ccephp_findx($class, $vars, $revars, $sorttype, $sortprop) {
    // This will need more work, as this is cheating on a massive scale.
    // We pretty much ignore $revars, $sorttype and $sortprop for now.
    //
    // The catch is: We don't really need them anymore in any place that
    // I know of. If sorting needs to be done, then DataTables() usually
    // handles that for us. And I don't even know if there is *any* page
    // that actually uses regular expressions for the search ($revars).
    if (($vars == "") && ($revars == "")) {
      // Simple search for $class
      CCE::ccephp_new("FIND $class");
    }
    elseif (($vars != "") && ($revars == "")) {
      // Simple search for $class with $vars:
      $varline = ' ';
      foreach ($vars as $key => $value) {
        $varline .= "$key = \"" . CCE::_escape($value) . "\" ";
      }
      $varline = rtrim($varline);
      CCE::ccephp_new("FIND $class " . $varline);
    }
    elseif (($vars == "") && ($revars != "")) {
      // Complex search for $class with regex-match criteria in $revars:
      //
      // Do a simple search for all members of this $class first:
      CCE::ccephp_new("FIND $class");
      // Store resulting OIDs in $revarResults:
      $revarResults = $this->self['oidlist'];
    }
    else {
      $varline = " ";
      foreach ($vars as $key => $value) {
        $varline .= "$key = \"" . CCE::_escape($value) . "\" ";
      }
      $varline = rtrim($varline);
      CCE::ccephp_new("FIND $class $varline");
    }
    return $this->self['oidlist'];
  }

  // Get the object:
  function ccephp_get($oid, $namespace) {
    if ($namespace == "") {
      CCE::ccephp_new("GET $oid");
    }
    else {
      if (is_array($oid)) {
        $oid = $oid[0];
      }
      CCE::ccephp_new("GET $oid . $namespace");
    }
    if (is_array($this->self['object'])) {
      return $this->self['object'];
    }
    else {
      // Cheating: The returned object is not an array?
      // In that case we return "-1" just as the cce.so PHP module would do.
      // This is apparently done so that a referenced non-found key in the
      // called object doesn't trigger an 'Uninitialized string offset' error.
      // I'd rather return NULL or FAIL, but we have to remain compatible.
      return "-1";
    }
  } 

  // Auth:
  function ccephp_auth($userName, $password) {
    // The password must be escaped and the only valid char for that is a double quote.
    // CCEd will not accept single quoted values:
    CCE::ccephp_new("AUTH ". CCE::_escape($userName) . " \"" . CCE::_escape($password) . "\"");
    if ($this->self['sessionid'] != "") {
      setcookie("loginName", $userName, time()+60*60*24*365, "/");
      $this->setUsername($userName);
      $this->setSessionId($this->self['sessionid']);
    }
    return $this->self['sessionid'];
  }

  // Authkey:
  function ccephp_authkey($userName, $sessionId) {
    CCE::ccephp_new("AUTHKEY ". CCE::_escape($userName) . " $sessionId");
    if ($this->self['success'] == '1') {
      $this->setSessionId($sessionId);
      $this->SessionId = $sessionId;
      $this->self['sessionid'] = $sessionId;
      setcookie("sessionId", $sessionId, "0", "/");
    }
    else {
      delete_cookie("sessionId");
      $this->self['sessionid'] = '';
      $this->setSessionId('');
      $this->SessionId = '';
    }
    return $this->self['sessionid'];
  }

  // WHOAMI
  function ccephp_whoami() {
    CCE::ccephp_new("WHOAMI");
    if (isset($this->self['oidlist'][0])) {
      return $this->self['oidlist'][0];
    }
    return NULL;
  }

  // NAMES
  function ccephp_names($args = "") {
    if ($args == "") {
      CCE::ccephp_new("NAMES");
    }
    else {
      CCE::ccephp_new("NAMES $args");
    }
    return $this->self['namelist'];
  }

  // description: set object properties in CCE
  // returns: boolean true for success, boolean false for failure
  // usage: $ok = $cce->set($oid, $namespace, array( 'property' => 'value'));
  function ccephp_set($oid, $namespace, $vars) {
    $this->OID = $oid;
    $snd_line = "SET $oid";
    if ($namespace != "") {
      $snd_line .= " . " . $namespace;
    }

    $varline = " ";
    foreach ($vars as $key => $value) {
      $varline .= "$key = \"" . CCE::_escape($value) . "\" ";
    }
    $varline = rtrim($varline);
    CCE::ccephp_new($snd_line . " " . $varline);
    $this->OID = '';
    return $this->self['success'];
  }

  // description: set CCE read-only.
  // Requires: systemAdministrator access
  // param: reason: reason for CCE being read-only
  // returns: true is successful
  function ccephp_suspend($reason = '') {
    if ($reason != '') {
      $reason = '"' . CCE::_escape($reason) . '"';
    }

    // Perform the transaction:
    CCE::ccephp_new("ADMIN SUSPEND " . $reason);

    // Return result:
    return $this->self['success'];
  }

  // description: set CCE read-write after a call to suspend().
  // Requires: systemAdministrator access
  // returns: true is successful
  function ccephp_resume() {

    // Perform the transaction:
    CCE::ccephp_new("ADMIN RESUME");

    // Return result:
    return $this->self['success'];
  }

  //@@@@@@@@@@@@@
  // IMPORTANT!
  //@@@@@@@@@@@@@
  //
  // ccephp_begin() and ccephp_commit() start and end delayed trans state (DTS). 
  //
  // Which has three states:
  //
  // FALSE:  DTS disabled. Execute commands directly. This is the default.
  // TRUE:   DTS entered.  Do not execute commands directly. Record them and wait for COMMIT.
  // COMMIT: DTS ending.   Execute all stored commands and once done reset to FALSE.
  //
  // DTS basically allows us to run a whole heap of commands, which might or might not conflict
  // with each others or which might cause multiple handler runs. Instead we issue ...
  // BEGIN, then all commands and then COMMIT. The handlers are only run after COMMIT has been
  // issued and therefore will not run more than once. This can be a time saviour, but we don't
  // really use this often enough in the GUI yet.
  
  // description: begin delayed-handler mode
  // Also known as delayed transaction state (DTS)
  function ccephp_begin() {
    // Enter delayed transaction state:
    CCE::setDTS("TRUE");
    // Issue the BEGIN transaction that tells CCEd to hold its horses on the handlers:
    CCE::ccephp_new("BEGIN");
    // Return TRUE as we have no way of knowing if this failed or not:
    return TRUE;
  }

  // description: trigger all handlers to run since begin() call
  // returns: a success code based on the success or failure
  function ccephp_commit() {
    // Leave delayed transaction state:
    CCE::setDTS("COMMIT");
    // Call cceclient and tell it to COMMIT the changes:
    CCE::ccephp_new("COMMIT");
    // Return result - with a catch: This only shows the success/failure of the final transaction.
    return $this->self['success'];
  }

  // description: determine if CCE is suspended or not
  // returns: reason string if suspended, false otherwise
  function ccephp_suspended() {
    // Bit of a cheating: 
    // System var 'productVendor' is usually unused. We set it to blank and see
    // if we get a 'suspendedmsg':
    CceClient::setObject("System", array('productVendor' => ''));
    return $this->self['suspendedmsg'];
  }

  // description: create a CCE object of type $class, with properties in $vars
  // returns: oid of created object, or 0 on failure
  // usage: $oid = $cce->create($class, array( 'property' => 'value' ));
  function ccephp_create($class, $vars=array()) {

    // Cleanup $vars:
    $varline = " ";
    foreach ($vars as $key => $value) {
      $varline .= "$key = \"" . CCE::_escape($value) . "\" ";
    }
    $varline = rtrim($varline);

    // Do it:
    CCE::ccephp_new("CREATE $class " . $varline);

    // Parse response:
    if ($this->self['success'] == '1') {
      // Return OID of the new Object:
      return $this->self['oidlist']['0'];
    }
    else {
      // Return '0' to indicate that we failed:
      return '0';
    }
  }

  // description: destroy the CCE object with oid $oid
  // returns: boolean true for success, false for failure
  // usage: $ok = $cce->destroy($oid);
  function ccephp_destroy($oid) {
    if (!$oid) {
      // No OID given? Nothing to destroy:
      return FALSE;
    }
    // Do it:
    CCE::ccephp_new("DESTROY $oid");
    // Return the result:
    if ($this->self['success'] == "1") {
      return TRUE;
    }
    else {
      return FALSE;
    }
  }

  // description: determines if the current session is a handler in rollback mode.
  function ccephp_is_rollback() {
    return $this->self['rollbackflag'];
  }

  function _cceclient ($command = "") {

    $this->Username = $this->getUsername();
    $this->SessionId = $this->getSessionId();
    $this->Password = $this->getPassword();

    // Setup $this->self with basic stuff:
    CCE::flushmsgs();

    // Initialize Socket - use the one from the config file:
    $system = new System();
    $socketPath = $system->getConfig("ccedSocketPath");

    // Timeout for CCEd (15 seconds):
    $timeout = 15;
    $socket = @stream_socket_client("unix:///$socketPath", $errorno, $errorstr, $timeout);
    @stream_set_timeout($socket, $timeout);

    if (is_bool($socket)) {
      return FALSE;
    }

    // If we have Username and Password we use AUTH:
    if ((isset($this->Username)) && (isset($this->Password))) {
      if (($this->Username != '') && ($this->Password != '')) {
        fwrite($socket, "AUTH $this->Username \"" . $this->Password . "\"\n");
      }
    }
    elseif ((isset($this->Username)) && (isset($this->SessionId))) {
      // If we have Username and SessionId we use AUTHKEY instead:
      if (($this->Username != '') && ($this->SessionId != '')) {
        $CI =& get_instance();
        $CookSessionId = $CI->input->cookie('sessionId');
        fwrite($socket, "AUTHKEY $this->Username $this->SessionId\n");
      }
    }
    else {
      // No Auth at this time.
    }
    if ($command != "") { // Only issue commands to CCEd if we have something to say.
      if (is_file("/etc/DEBUG")) {
        // DEBUG: Write the transaction to the error_log, too:
        error_log("Command: " . $command);
      }
      // Issue the command to the Unix Socket:
      fwrite($socket, $command . "\n");
    }

    // Adios y hasta luego!
    fwrite($socket, "BYE\n");

    // Get CCEd's collective responses to our transactions:
    $result = CCE::_parse_response(stream_get_contents($socket));

    // Store Socket-Errors:
    CCE::setERRNO($errorno);
    CCE::setERRSTR($errorstr);

    // If we do not have a 'sessionid' reported from the last connection
    // attempt, then we delete the 'sessionid' Cookie and internal vars
    // related to the SessionID. This is needed on logouts or attempted
    // connections after a timeout. Otherwise we'd see repeated attempts
    // to use AUTHKEY, which simply take too long to finish due to the 
    // involved delays of the PAM module. So this speeds up things:
    if ($this->self['sessionid'] == '') {
      delete_cookie("sessionId");
      $this->setSessionId('');
      $this->SessionId = '';
    }

    return $result;
  }

  function flushmsgs() {
    $this->self['success'] = '0';
    $this->self['perm'] = '1';
    $this->self['object'] = '';
    $this->self['old'] = '';
    $this->self['new'] = '';
    $this->self['baddata'] = '';
    $this->self['info'] = '';
    $this->self['oidlist'] = array();
    $this->self['namelist'] = array();
    $this->self['createflag'] = '0';
    $this->self['destroyflag'] = '0';
    $SID = $this->getSessionId();
    if ($SID == "") {
      $this->self['sessionid'] = '';
    }
    else {
      $this->self['sessionid'] = $SID;
    }
    $this->self['classlist'] = array();
  }

  function _parse_response($result) {

    // Start sane:
    CCE::flushmsgs();

    // Explode by newline:
    $resultArr = preg_split("/\\r\\n|\\r|\\n/", $result);

    // Parse line by line:
    foreach ($resultArr as $key => $line) {
      //if (m/^100 CSCP\/(\S+)/) { $self->{version} = $1; next; }
      if (preg_match('/^100 CSCP\/(\S+)/', $line, $matches)) {
        if (isset($matches[1])) {
          $this->self['version'] = $matches[1];
        }
      }

      if (preg_match('/^200 READY$/', $line, $matches)) {
        continue;
      }
      if (preg_match('/^202 GOODBYE$/', $line, $matches)) {
        continue;
      }

      if (preg_match('/^101/', $line, $matches)) {
        $this->self['event'] = 'unknown';
        $this->self['event_oid'] = '0';
        $this->self['event_namespace'] = '';
        $this->self['event_property'] = '';
        // FIXME: this needs to handle multiple header EVENTs
        if (preg_match('/EVENT\s+(\d+)\s*\.\s*(\w*)\s*\.\s*(\w*)/', $line, $matches)) {
          $this->self['event_oid'] = $matches[1];
          $this->self['event_namespace'] = $matches[2];
          $this->self['event_property'] = $matches[3];
        }
        elseif (preg_match('/EVENT\s+(\d+)\s*\.(\w*)/', $line, $matches)) {
          $this->self['event_oid'] = $matches[1];
          $this->self['event_property'] = $matches[2];
        }
      }

      if (preg_match('/^102 \S+ (.*?)\s*=\s*(.*)/', $line, $matches)) {
        if ((isset($matches[1])) && (isset($matches[2]))) {
          $key = $matches[1];
          $val = $matches[2];
          $this->self['old'][$key] = CCE::unescape($val);
        }
      }

      if (preg_match('/^103 \S+ (.*?)\s*=\s*(.*)/', $line, $matches)) {
        if ((isset($matches[1])) && (isset($matches[2]))) {
          $key = $matches[1];
          $val = $matches[2];
          $this->self['new'][$key] = CCE::unescape($val);
        }
      }

      // Handle FIND:
      if (preg_match('/^104 OBJECT (\d+)/', $line, $matches)) {
        if ((isset($matches[0])) && (isset($matches[1]))) {
          $this->self['oidlist'][] = $matches[1];
        }
      }

      if (preg_match('/^105 NAMESPACE (\S+)/', $line, $matches)) {
        if ((isset($matches[0])) && (isset($matches[1]))) {
          $this->self['namelist'][] = $matches[1];
        }
      }

      if (preg_match('/^106 INFO (.*)/', $line, $matches)) {
        if ((isset($matches[0])) && (isset($matches[1]))) {
          $this->self['info'][] = $matches[1];
        }
      }

      if (preg_match('/^107 CREATED/', $line, $matches)) {
        $this->self['createflag'] = '1';
      }

      if (preg_match('/^108 DESTROYED/', $line, $matches)) {
        $this->self['destroyflag'] = '1';
      }

      // SESSIONID:
      if (preg_match('/^109 SESSIONID (\S+)/', $line, $matches)) {
        if ((isset($matches[0])) && (isset($matches[1]))) {
          $this->self['sessionid'] = $matches[1];
          $this->setSessionId($this->self['sessionid']);
        }
      }

      // CLASSLIST:
      if (preg_match('/^110 CLASS (\S+)/', $line, $matches)) {
        if ((isset($matches[0])) && (isset($matches[1]))) {
          $this->self['classlist'][] = $matches[1];
        }
      }

      // ROLLBACK:
      if (preg_match('/^111 ROLLBACK$/', $line, $matches)) {
        $this->self['rollbackflag'] = '1';
      }

      // INFO/ERROR:
      if (preg_match('/^301 UNKNOWN CLASS\s(.*)$/', $line, $matches)) {
        if (isset($matches[1])) {
          $this->self['info'] = $matches[0];
          CCE::setError($matches[0], "", "", $matches[0]);
          continue;
        }
      }

      // 306 ERROR COMMAND PARSE ERROR:
      if (preg_match('/^306 ERROR\s(.*)$/', $line, $matches)) {
        if (isset($matches[1])) {
          $this->self['info'][$this->OID] = $matches[1];
          CCE::setError($matches[1], $this->OID, "", $matches[1]);
          continue;
        }
      }

      // BAD DATA:
      // Example:
      // 302 BAD DATA 3 isLicenseAccepted "[[base-cce.invalidData]]"
      if (preg_match('/^302 BAD DATA\s+(\d+)\s+(\S+)\s*(.*)?/', $line, $matches)) {
        if ((isset($matches[0])) && (isset($matches[1])) && (isset($matches[2]))) {
          $oid = $matches[1];
          $key = $matches[2];
          if (isset($matches[3])) {
            $msg = $matches[3];
          }
          else {
            $msg = "unknown-error";
          }
          $this->self['baddata'][$oid][$key] = $msg;
          CCE::setError("302 BAD DATA", $oid, $key, $msg);
          continue;
        }
      }

      if (preg_match('/^305 WARN\s(.*)$/', $line, $matches)) {
        if (isset($matches[1])) {
          $this->self['info'][$this->OID] = $matches[1];
          CCE::setError($matches[1], $this->OID, "", $matches[1]);
          continue;
        }
      }

      if (preg_match('/30([0-1][3-7])(.*)/', $line, $matches)) {
        if (isset($matches[1])) {
          $this->self['info'][$this->OID] = $matches[0];
          CCE::setError($matches[1], $this->OID, "", $matches[0]);
          continue;
        }
      }

      // SUSPENDED:
      if (preg_match('/^309 SUSPENDED\s+(.*)$/', $line, $matches)) {
        //if ((isset($matches[0])) && (!isset($matches[1]))) {
        if (isset($matches[0])) {
          // We are suspended. Grab the suspend message. If there is none,
          // then simply set it to TRUE:
          $this->self['suspendedmsg'] = CCE::unescape($matches[1]);
          if ($this->self['suspendedmsg'] == "") {
            $this->self['suspendedmsg'] = TRUE;
          }
          continue;
        }
      }

      // General success messages:
      if (preg_match('/^2/', $line, $matches)) {
        $this->self['success'] = '1';
      }

      // General FAIL messages:
      if (preg_match('/^403 BAD PARAMETERS$/', $line, $matches)) {
        $this->self['success'] = '0';
        $this->self['info'] = "403 BAD PARAMETERS";
        CCE::setError("403 BAD PARAMETERS", $this->OID, "", "403 BAD PARAMETERS");
        continue;
      }

      // General FAIL messages:
      if (preg_match('/^4/', $line, $matches)) {
        $this->self['success'] = '0';
        continue;
      }

      // Compose object out of old and new data (new overrides old):
      if ($this->self['destroyflag']) {
        $this->self['object'] = '';
      }
      else {
        $this->self['object'] = $this->self['old'];
      }

      if ($this->self['success']) {
        $this->self['object'] = $this->self['object'];
      }
    }

    // What we return here is irrelevant, as the stuff we really want
    // is in $this->self at this point. That's what counts.
    return $resultArr;

  }

  // unescape: This function is used to clean up data comming
  // from CODB in a fashion that it can be used by the GUI.
  function unescape ($text) {
    // Getting rid of the leading and trailing double 
    // quotation marks of values:
    if (preg_match('/^\"(.*)\"$/', $text, $matches)) {
      if (isset($matches[1])) {
        $text = $matches[1];
      }
    }

    // Replace certain double escapements and safe characters with their unsafe variants:
    $text = str_replace(array('\\\\', '\\"', "\\a", "\\b", "\\f", '\\n', "\\t", "&quot;"), array("\\", '\"', "\a", "\b", "\f", "\n", "\t", '"'), $text); 

    // Split the whole she-bang into individual characters:
    $pattern = str_split($text);

    $out = "";
    for ($i=0; $i < count($pattern); $i++) { 
      if ($pattern[$i] == "\\") {
        // Handles out-of-range octals:
        if (preg_match("/[0-9]/", $pattern[$i+1])) {
          // Fun starts here: Convert from Octal to Decimal and from Decimal to Hex:
          $hex_one = dechex(octdec($pattern[$i+1] . $pattern[$i+2] . $pattern[$i+3]));
          // Grab the second Octal (if present) and convert it, too:
          if ((isset($pattern[$i+5])) && (isset($pattern[$i+6])) && (isset($pattern[$i+7]))) {
            $hex_two = dechex(octdec($pattern[$i+5] . $pattern[$i+6] . $pattern[$i+7]));
          }
          else {
            // No second Octal, so set empty. Might need to change the 
            // first arg of pack() to 'h*' instead. Dunno yet.
            $hex_two = "";
          }
          // Create UTF-8 Character from Hex value:
          $out .= pack('H*', "$hex_one$hex_two");
          // Fast forward to a point after the 2nd Octal:
          $i += 7;
          continue;
        }
        $i++;
      }
      else {
        $out .= $pattern[$i];
      }
    }
    return $out;
  }

  // _escape: This function is used to clean up data comming
  // from the GUI in a fashion so that it can be stored into CODB.
  function _escape($text) {
    if (is_array($text)) {
      // We have an array. Transform it into a scalar for easier processing:
      $text = array_to_scalar($text);
    }

    // Check if this is a simple matter. If so, return right away:
    if (preg_match('/^[a-zA-Z0-9_]+$/', $text)) {
      return $text;
    }

    // Replace unwanted chars with their double escaped counterparts or another safe replacement:
    $out = str_replace(array("\\", "\a", "\b", "\f", "\n", "\t", '"', '$'), array( '\\\\', "\\a", "\\b", "\\f", "\\n", "\\t", "&quot;", "\\$"), $text); 
    return $out;
  }

  // _send(): This function is used to filter and clean up any of the
  // more sophisticated commands that we send to CCEd. That way we can
  // escape and filter key/value pairs into a format that CCEd understands.
  // Currently dormant, as we use _escape() directly in relevant transactions.
  function _send($cmd) {

    // Start sane:
    $encoded = array();

    if (is_array($cmd)) {
      // $cmd is an array:
      foreach ($cmd as $key => $value) {
        $encoded[] = CCE::_escape($key) . '=' . CCE::_escape($value);
      }
    }
    else {
      // Anything else:
      $encoded[] = CCE::_escape($cmd);
    }

    // Puzzle it back together:
    $out = implode(" ", $encoded);
    
    // Return the results:
    return $out;
  }

  function setError($code, $oid, $key="", $msg) {
    $numErrs = count($this->ERRORS);
    $this->ERRORS[$numErrs]['code'] = $code;
    $this->ERRORS[$numErrs]['oid'] = $oid;
    $this->ERRORS[$numErrs]['key'] = $key;
    $this->ERRORS[$numErrs]['message'] = $msg;

    if (preg_match('/\[\[(.*),(.*)\]\]/', $msg, $joinedMatches)) {
      if (count($joinedMatches) == "3") {
        $xvarkRay = explode('=', $joinedMatches[2]);
        if (isset($xvarkRay[1])) {
          if (preg_match('/\"(.*)\"/', $xvarkRay[1], $cleanTagVar)) {
            $xvarkRay[1] = preg_replace('/\\\\/','',$xvarkRay[1]);
            $xvarkRay[1] = rtrim($xvarkRay[1]);
            $xvarkRay[1] = ltrim($xvarkRay[1]);
          }
        }
        $this->ERRORS[$numErrs]['code'] = "[[$joinedMatches[1]]]";
        $this->ERRORS[$numErrs]['key'] = $xvarkRay;
      }
    }

    if ($numErrs != "0") {
      $numErrs++;
    }
  }

  function ccephp_errors() {
    return $this->ERRORS;
  }

}

/*
Copyright (c) 2015 Michael Stauber, SOLARSPEED.NET
Copyright (c) 2015 Team BlueOnyx, BLUEONYX.IT
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