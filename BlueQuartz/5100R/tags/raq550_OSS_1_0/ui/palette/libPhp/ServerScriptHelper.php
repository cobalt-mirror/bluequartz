<?php
// Copyright 2000, 2001 Sun Microsystems, Inc.  All rights reserved.
// $Id: ServerScriptHelper.php 259 2004-01-03 06:28:40Z shibuya $

// description:
// This class is designed to ease the development of server-side scripts. It is
// a library of commonly used functions.
//
// applicability:
// Server-side scripts that uses session, UIFC, I18n and/or CCE.
//
// usage:
// Construct a new ServerScriptHelper at the start of every server-side script.
// It will automagically get session information, knows who is the login user
// and connect to CCE to find out more information about the user. The "get"
// methods can be used to get information about the script. Always call
// destructor() at the end of the scripts. Don't forget.

global $isServerScriptHelperDefined;
if($isServerScriptHelperDefined)
  return;
$isServerScriptHelperDefined = true;

include_once("System.php");
include_once("I18n.php");
include_once("ArrayPacker.php");
include_once("uifc/HtmlComponentFactory.php");
include_once("uifc/Stylist.php");
include_once("Capabilities.php");

class ServerScriptHelper {
  //
  // private variables
  //

  var $cceClient;
  // hash of "<domain>/<localePreference>" to i18n objects
  var $i18n;
  var $loginName;
  var $loginUser;
  var $isMonterey;

  var $capabilities;
  var $errors;

  //
  // public functions
  //

  // description: constructor
  // param: sessionId: the session Id in string. Optional.
  //     If not supplied, global $sessionId is used
  // param: loginName: the login name of the user in string. Optional.
  //     If not supplied, global $loginName is used, otherwise PHP_AUTH_USER
  function ServerScriptHelper($sessionId = "", $loginName = "") 
  {
    // use global session ID if ID not supplied through parameter
    if($sessionId == "")
        global $sessionId;

    // use global login name if not supplied through parameter
    if($loginName == "")
        global $loginName;

    // use PHP_AUTH_USER if global loginName is not defined
    global $PHP_AUTH_USER;
    if ( ! $loginName && $PHP_AUTH_USER ) {
        $loginName = $PHP_AUTH_USER;
    }
    
    // save parameter
    $this->loginName = $loginName;

    // initialize cceClient
    // does authentication via CCE
    $system = new System();

    $product = $this->getProductCode();
    $this->isMonterey = ereg("35[0-9][0-9]R", $product);

    if ($this->hasCCE()) 
    {
        include_once('CceClient.php');
        $this->cceClient = new CceClient();
        $cceClient = $this->cceClient;
        if(!$cceClient->connect()) 
        {
            // CCE is down
            // read the reason from message file
            // why did we get another System here?
            // $system = new System();
            // This is commented out because this will override 
            // system settings for products that aren't fully Sausalito
            // $defaultLocale = $system->getConfig("defaultLocale");
            $path = $system->getConfig("ccedMessagePath");
            $messageTag = "[[palette.cceDown]]";
            if(file_exists($path)) 
            {
                $messageFile = fopen($path, "r");
	            $messageTag = fgets($messageFile, 1024);
	            fclose($messageFile);
            }
            // we use default locale here because locale preference is stored in CCE
            $i18n = new I18n;
            $message = $i18n->interpolate($messageTag);

            error_log("ServerScriptHelper.ServerScriptHelper(): CCE is down: $message");
            header("cache-control: no-cache");
            print("
<HTML>
  <HEAD>
    <META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
  <BODY>
    <CENTER>
    <BR><BR><BR><BR>
    <TABLE WIDTH=\"60%\"><TR>
      <TD><FONT COLOR=\"#990000\">$message</FONT></TD>
    </TR></TABLE>
    </CENTER>
  </BODY>
  <HEAD>
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
</HTML>");
            exit;
        }
	
        // only AUTH if not on Monterey
        if (!$this->isMonterey) 
        {
            if (!$cceClient->authkey($loginName, $sessionId)) 
            {
	            error_log("ServerScriptHelper.ServerScriptHelper(): Cannot authenticate to CCE (login name: $loginName, session ID: $sessionId)"); 
	            // tell users their sessions are expired and redirect
	            // set the target here to point to where to go back to after login
	            header("cache-control: no-cache");
	            print("
<HTML>
  <HEAD>
    <META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
  <BODY onLoad=\"redirect()\">
    <SCRIPT LANGUAGE=\"javascript\">
    function redirect() {
      var pathname = top.location.pathname;
      // IE4.0 has a bug that location.pathname contains port at the beginning
      if(top.location.port != null && top.location.port != \"\" && pathname.indexOf(\"/:\"+top.location.port) == 0)
	pathname = pathname.substring(2+top.location.port.length);
      var url = \"/login.php?expired=true&target=\"+escape(pathname+top.location.search+top.location.hash);

      top.location = url;
      top.focus();
    }
    </SCRIPT>
  </BODY>
  <HEAD>
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
</HTML>");
	            exit;
            }
     
            // get the login user from CCE
	    /* this code doesn't make sense
	       you can't auth without a User object
	       so this code will never get run
	       
            $oids = $cceClient->find("User", array("name" => $this->loginName));
            if(count($oids) == 0) 
            {
	            error_log("ServerScriptHelper.ServerScriptHelper(): Cannot get user from CCE (name: $loginName)");
	            // redirect
	            header("cache-control: no-cache");
	            print("
<HTML>
  <HEAD>
    <META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
  <BODY onLoad=\"redirect()\">
    <SCRIPT LANGUAGE=\"javascript\">
    function redirect() {
      top.location.replace(\"/login.php\");
    }
    </SCRIPT>
  </BODY>
  <HEAD>
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
</HTML>");
	            exit;
            }
	    */

            $this->loginUser = $cceClient->get($cceClient->whoami());
        }
    
        // initialize
        $this->i18n = array();
    }
  }

  // description: destructor
  function destructor() 
  {
    $cceClient = $this->cceClient;
//    $cceClient->commit();
    if ($this->hasCCE()) 
    {
        $cceClient->bye();
    }

    // clean up cache
    unset($this->i18n);
  }

  // description: Returns the contents of a file using the unix permissions 
  //   granted to the current CCE user
  // param: filename: The filename of the file to be opened
  // returns: the contents of the file
  function getFile($filename) {
    $rv = $this->shell("/bin/ls -s --block-size=1 $filename", $ls);
    if (!$rv) {
      ereg("^([0-9]+)[[:space:]]", $ls, $regs);
      $size = $regs[1];
      $fh = $this->popen("/bin/cat $filename");
      $ret = fread($fh, $size);
    }
    pclose($fh);
    return $ret;
  }

  // description: Writes the given data into the given file as the currently
  //   logged in user.  The current user becomes the owner, 'users' is the group
  //   and the permissions are 0600.
  // param: filename: Name of the file to write
  // param: data: data to be written to the file
  // TODO: This needs error checking
  function putFile($filename, $data) {
    $fh = $this->popen("/usr/sausalito/sbin/writeFile.pl $filename", "w");
    fwrite($fh, $data);
    pclose($fh);
  }

  // description: checks to see if a user is granted the given capability.
  // param: the name of the CapabilityGroup or CCE-Level capability to check
  // param: the user to check for (default: current)
  // returns: true if the current user has this capability, false otherwise
  function getAllowed($capName, $oid = -1) {
    if ($this->capabilities == null)
      $this->capabilities = getGlobalCapabilitiesObject($this->cceClient);
    return $this->capabilities->getAllowed($capName, $oid);
  }
  

  // description: get a list of all the capabilities the given user has
  // param: the oid of the user to check (defaults: current)
  // returns: a list of all the capabilities the current user has
  function listAllowed($oid = -1) {
    if ($this->capabilities == null)
      $this->capabilities = getGlobalCapabilitiesObject($this->cceClient);
    return $this->capabilities->listAllowed($oid);
  }
  // description: given a capabilitygroup name, this function will expand it
  //   and it's children into a list composed of both capabilitygroup names and
  //   and cce-level capabilities
  // param: capName - the name of the capability to be expanded.
  // returns: an expanded list of the capabilities entailed by $capName 
  function expandCaps($capName, $seen = array()) {
    if ($this->capabilities == null)
      $this->capabilities = getGlobalCapabilitiesObject($this->cceClient);
    return $this->capabilities->expandCaps($capName, seen);
  }

  // description:  gets the capabilityGroup and caches it
  function getCapabilityGroup($capName, $data = null) {
    if ($this->capabilities == null)
      $this->capabilities = getGlobalCapabilitiesObject($this->cceClient);
    return $this->capabilities->getCapabilityGroup($capName, $data);
  }
  
  // description: returns an array of ALL the capabilityGroups
  function getAllCapabilityGroups() {
    if ($this->capabilities == null)
      $this->capabilities = getGlobalCapabilitiesObject($this->cceClient);
    return $this->capabilities->getAllCapabilityGroups();
  }
 
  // description: returns an array of all the declared cce-level capabilities
  function getAllCapabilities() {
    if ($this->capabilities == null)
      $this->capabilities = getGlobalCapabilitiesObject($this->cceClient);
    return $this->capabilities->getAllCapabilities();
  }

  // description: returns the global capabilities object
  function getCapabilitiesObject() {
    if ($this->capabilities == null)
      $this->capabilities = getGlobalCapabilitiesObject($this->cceClient);
    return $this->capabilities;
  }
      

  // description: opens a read-only stream wrapped by CCE
  // param: program: A string containing the program to execute, including
  //   the path and any arguments
  // param: mode: The mode to use in this popen
  // param: runas: the user to run the program as, defaults to the currently
  //	logged in user if not specified
  // returns: a file handle to be read from
  function popen($cmd, $mode = "r", $runas = "")
  {
    global $sessionId;
    $product = $this->getProductCode();
    $this->isMonterey = ereg("35[0-9][0-9]R", $product);

    putenv("CCE_SESSIONID=" . $sessionId);
    putenv("CCE_USERNAME=" . $this->loginName);
    putenv("CCE_REQUESTUSER=" . $runas);
    putenv('PERL_BADLANG=0');

    if ($this->isMonterey) {
      $handle = popen("$cmd", $mode);
    } else {
      $handle = popen("/usr/sausalito/bin/ccewrap $cmd", $mode);
    }
    putenv("CCE_SESSIONID=");
    putenv("CCE_USERNAME=");
    putenv('CCE_REQUESTUSER=');
    
    return $handle;
  }

  // description: allows one to execute a program as
  //   the currently logged in user
  // param: program: A string containing program to execute, including 
  //   path and any arguments
  // param: output variable that picks up the output sent by the program
  // param: the user to run this program as (defaults to the currently
  //   logged in user 
  // returns: 0 an success, errno on error
  function shell($cmd, &$output, $runas="") {
    global $sessionId;
    $product = $this->getProductCode();
    $this->isMonterey = ereg("35[0-9][0-9]R", $product);

    // call ccewrap
    //$cmd = escapeShellCmd($cmd);	
    putenv("CCE_SESSIONID=". $sessionId);
    putenv("CCE_USERNAME=". $this->loginName);
    putenv("CCE_REQUESTUSER=". $runas);
    putenv("PERL_BADLANG=0");

    if ($this->isMonterey) {
      exec("$cmd", $array, $ret);
    } else {
      exec("/usr/sausalito/bin/ccewrap $cmd", $array, $ret);
    }
      
    // prepare return
    while (list($key,$val)=each($array)) 
      $output .= "$val\n";	

    // clean up
    putenv("CCE_SESSIONID=");
    putenv("CCE_USERNAME=");
    putenv("CCE_REQUESTUSER=");

    return $ret;
  }

  // description: allows one to fork a program as
  //   the currently logged in user.  Notice that NO interaction between the 
  //   called program and the caller can be made.
  // param: program: A string containing program to execute, including 
  //   path and any arguments
  // param: the user this program should run as.  Defaults to the currently
  //   logged in user
  // returns: 0 an success, errno on error
  function fork($cmd, $runas = "") {
    $this->shell("$cmd >/dev/null 2>&1 < /dev/null &", $out, $runas);
  }

  // descriptions: get an array of access rights
  // returns: an array of access rights in strings
  function getAccessRights() {
    $product = $this->getProductCode();
    $this->isMonterey = ereg("35[0-9][0-9]R", $product);

    // include rights specified in uiRights property
    $accessRights = stringToArray($this->loginUser["uiRights"]);


    // add the list of capabilityGroups AND cce-level capabilities
    if (isset($this->loginUser["capLevels"]))
      $accessRights = array_merge($accessRights, $this->listAllowed());

    global $PHP_AUTH_USER;
    if ($this->isMonterey && $PHP_AUTH_USER && (($PHP_AUTH_USER == "admin") || ($PHP_AUTH_USER == "alteradmin"))) {
      $accessRights[] = "systemAdministrator"; 
    }

    if ( $this->isMonterey && in_array( $PHP_AUTH_USER, posix_getgrnam("site-adm"))) {
      $accessRights[] = "siteAdministrator";
    }	

    return $accessRights;
  }

  // description: get a connected and authenticated CceClient
  // returns: a CceClient object
  function getCceClient() {
    return $this->cceClient;
  }

  // description: get a HtmlComponentFactory object to construct HtmlComponents
  // param: i18nDomain: the I18n domain used for construction
  // param: formAction: the action of the form where HtmlComponents sit in
  // returns: a HtmlComponentFactory object
  function getHtmlComponentFactory($i18nDomain, $formAction = "") {
    $factory = new HtmlComponentFactory($this->getStylist(), $this->getI18n($i18nDomain), $formAction);
    return $factory;
  }

  // description: represent errors in Javascript
  // param: errors: an array of Error objects
  // returns: Javascript if error occured or "" otherwise
  function toErrorJavascript($errors) {
    $i18n = $this->getI18n();

    for($i = 0; $i < count($errors); $i++) {
      $error = $errors[$i];
      $errorInfo .= $i18n->interpolateJs($error->getMessage(),
                                       $error->getVars())."<BR>";
    }

    if($errorInfo)
      return "var errorInfo ='$errorInfo';\ntop.code.info_show(errorInfo,\"error\");";
    else
      return "top.code.info_show(\"\", null);";
  }

  // description: get the right I18n object
  // param: domain: the domain of the I18n object. Optional
  // param: httpAcceptLanguage: the HTTP_ACCEPT_LANGUAGE header. Optional.
  //     If not supplied, global $HTTP_ACCEPT_LANGUAGE is used
  // returns: an I18n object
  function getI18n($domain = "", $httpAcceptLanguage = "") {
    // use global $HTTP_ACCEPT_LANGUAGE if no $httpAcceptLanguage
    if($httpAcceptLanguage == "") {
      global $HTTP_ACCEPT_LANGUAGE;
      $httpAcceptLanguage = $HTTP_ACCEPT_LANGUAGE;
    }

    // find locale preference
    $localePreference = $this->getLocalePreference($httpAcceptLanguage);

    // make key for i18n hash
    $key = "$domain/$localePreference";

    // put new object in hash if necessary
    if(!is_object($this->i18n[$key]))
      $this->i18n[$key] = new I18n($domain, $localePreference);

    return $this->i18n[$key];
  }

  // description: get the preferred locale specified by the logged in user
  //     if "browser" is preferred, locale from HTTP_ACCEPT_LANGUAGE is used
  //     if no locale is preferred, use defaultLocale specified in ui.cfg
  // param: httpAcceptLanguage: the HTTP_ACCEPT_LANGUAGE header. Optional.
  //     global HTTP_ACCEPT_LANGUAGE is used if not supplied
  // returns: a list of locales in string, comma separated
  function getLocalePreference($httpAcceptLanguage = "") {
    $localePreference = $this->loginUser["localePreference"];

    // use defaultLocale in ui.cfg if user do not have preference
    if(!$this->isMonterey && $localePreference == "") {
      $system = new System();
      return $system->getConfig("defaultLocale");
    }

    // return what the user specified
    if(!$this->isMonterey && $localePreference != "browser")
      return $localePreference;

    // use global HTTP_ACCEPT_LANGUAGE if it is not supplied
    if($httpAcceptLanguage == "") {
      global $HTTP_ACCEPT_LANGUAGE;
      $httpAcceptLanguage = $HTTP_ACCEPT_LANGUAGE;
    }

    // use defaultLocale in ui.cfg as default if preference is "browser" and
    // HTTP accept language is empty
    if($httpAcceptLanguage == "") {
      $system = new System();
      return $system->getConfig("defaultLocale");
    }

    // For HTTP_ACCEPT_LANGUAGE, IE gives something like "en", "en,ja",
    // "sq,ja;q=0.7,en-us;q=0.3" or "ja,en-us;q=0.5"
    // NN gives something like "en", "en, ja"
    // preferrence is from left to right

    // remove all spaces
    $httpAcceptLanguage = str_replace(" ", "", $httpAcceptLanguage);

    $locales = explode(",", $httpAcceptLanguage);
    $httpAcceptLanguage = "";
    for($i = 0; $i < count($locales); $i++) {
      $locale = $locales[$i];

      // remove all the q stuff because IE already sorted the entries
      $index = strpos($locale, ";");
      if($index)
	$locale = substr($locale, 0, $index);

      // make country code uppercase
      if(strlen($locale) > 3)
	$locale = substr($locale, 0, 3).strtoupper(substr($locale, 3, strlen($locale)-3));

      if($i > 0)
	$httpAcceptLanguage .= ",";

      $httpAcceptLanguage .= $locale;
    }

    $httpAcceptLanguage = str_replace("-", "_", $httpAcceptLanguage);

    return $httpAcceptLanguage;
  }

  // description: get the name of the logged in user
  // returns: login name in string
  function getLoginName() {
    return $this->loginName;
  }

  // description: get the style preferred by the logged in user
  //     if user has no preference or if the preference is not available,
  //     use any style available on the system
  // returns: style ID in string
  function getStylePreference() {
    $preference = $this->loginUser["stylePreference"];

    if(!is_object($this->stylist)) {
        $this->stylist = new Stylist();
    }
    $styleIds = $this->stylist->getAllResourceIds();

    // very bad error if the system has no style
    if(count($styleIds) == 0) {
      $err = "Error: No style available on the system.";
      print($err);
      error_log($err, 0);
      exit();
    }

    $product = $this->getProductCode();
    $this->isMonterey = ereg("35[0-9][0-9]R", $product);

    // use preference if it is available
    // then use trueBlue if it is available
    // otherwise, use any style available
    if(in_array($preference, $styleIds)) {
      return $preference;
    } else if ($this->isMonterey && in_array("classic", $styleIds)) {
      return "classic";
    } else if (in_array("trueBlue", $styleIds)) {
      return "trueBlue";
    } else {
      return $styleIds[0];
    }
  }

  // description: get the stylist who gives right styles according to the
  //     style preference of the logged in user
  // returns: a Stylist object
  function getStylist($styleId="") {
    $this->stylist = new Stylist($styleId);

    // get style preference
    $style = $this->getStylePreference();

    // each style has its own i18n domain
    $i18n = $this->getI18n($style);

    // get locale
    $locales = $i18n->getLocales();
    $locale = (count($locales) > 0) ? $locales[0] : "";

    // set style resource
    $this->stylist->setResource($style, $locale);

    return $this->stylist;
  }

  // description: get the HTML page to be printed out by UI page handlers
  // param: returnUrl: the URL the handler returns to. Optional
  // param: errors: an array of Error objects for errors occured within the
  //     handler. Optional
  function toHandlerHtml($returnUrl = "", $errors = array(), $preserveData = true) 
  {
    // use global post vars since it is already there and we eat enough resources as is
    global $HTTP_POST_VARS;

    // see if the post vars should be passed down
    if ($preserveData && (count($errors) > 0))
    {
        // try to maintain as little overhead as possible by only adding stuff that is absolutely needed
        $post_vars_html = "<FORM METHOD=\"POST\" ENCTYPE=\"multipart/form-data\" ACTION=\"$returnUrl\">\n";
       
        // serialize the errors array, to preserve all data for field marking
        $post_vars_html .= "<INPUT TYPE=\"HIDDEN\" NAME=\"_serialized_errors\" VALUE=\"" .
                            urlencode(serialize($errors)) . "\">\n";

        // just in case
        @reset($HTTP_POST_VARS);
        while(list($var, $value) = @each($HTTP_POST_VARS))
        {
            // just pass through all values and assume the other side knows what to do
            $post_vars_html .= "<INPUT TYPE=\"HIDDEN\" NAME=\"$var\" VALUE=\"$value\">\n";
        }

        $post_vars_html .= "</FORM>\n";

        $onLoad = "document.forms[0].submit();";
    }
    else // do things the old way
    {
        // maintain as little overhead as possible	
        $errorJavascript = "<SCRIPT LANGUAGE=\"javascript\">\n";
        $errorJavascript .= $this->toErrorJavascript($errors);
        $errorJavascript .= "</SCRIPT>\n";
        $onLoad = $returnUrl ? "location = '$returnUrl';" : "";
    }

    $onLoad .= (count($errors) == 0) ? "flow_success = true;" : "flow_success = false;";

    if (!headers_sent())
        header("cache-control: no-cache");

    /*
     * make sure the encoding is correct otherwise, if we aren't preserving
     * data, japanese strings may be corrupted, because the browser gets
     * confused.
     */
    $i18n = $this->getI18n();
    $encoding = $i18n->getProperty('encoding', 'palette');
    if ($encoding != 'none') {
    	$encoding = "; charset=$encoding";
    } else {
    	$encoding = '';
    }

    return "
<HTML>
  <HEAD>
    <META HTTP-EQUIV=\"Content-type\" CONTENT=\"text/html$encoding\">
    <META HTTP-EQUIV=\"expires\" CONTENT=\"-1\">
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
  <BODY onLoad=\"$onLoad\">
$errorJavascript
$post_vars_html
  </BODY>
  <HEAD>
    <META HTTP-EQUIV=\"Pragma\" CONTENT=\"no-cache\">
  </HEAD>
</HTML>
";
  }

   /* description: Given a style name and a javascript method, generate
                    style information based on properties and target. 
               This method makes at least these, ahem, assumptions:
               - all style properties beginning with "background" will be handled
                 by the toBackgroundStyle method of Style.php
               - all style properties  beginning with "font" will be handled by
                 the toTextStyle method of Style.php
               - if useTarget is true (default), the property name passed to javascript 
                 method consists of property name + Target (uppercase first char on target). 
                 This is bad but at least consistent
      returns:     Javascript style text in a string
      uses:      Use this method when you have a style with n targets so that you don't
                  need to hard code your targets. You can add new targets just by adding 
                  them to the style file.
      params: 
        styleName - name of a style (e.g. 'info')
        jsMethod  - name of javascript method to call with the style info
        useTarget - construct js property name using property name and target (eg 'nextIconHelp')
      
   */            
  function getStyleJavascript($styleName, $jsMethod, $useTarget=true) {

   $stylist = $this->getStylist();
   $style = $stylist->getStyle($styleName);
   $properties = $style->getPropertyIds();   
   
   while (list(,$P) = each($properties)) {
   
         list($name, $target) = $P;
              //construct js property name using property name and target
         $Utarget = $useTarget ? ucfirst ($target) : ""; 
            
         $prop = $name . $Utarget; 
         $text = "";
         
            // Get the style text
         switch(true) {
            case strpos($name, "background") === 0:   // is 0 and is same type (ie not false).
                // We do bg style based on target, so only do it once for each target
               if(!(${$target ."bgStyle"})) {
                  $text = $style->toBackgroundStyle($target);
                  $prop = "backgroundStyle$Utarget";
                  ${$target . "bgStyle"} = true; // flag for this target
               }
            break;
            
            case strpos($name, "font") === 0:   // is 0 and is same type (ie not false).
               // We do text style based on target, so only do it once for each target
               if(!(${$target ."textStyle"})) {
                  $text = $style->toTextStyle($target);
                  $prop = "textStyle$Utarget";
                  ${$target . "textStyle"} = true; // flag for this target
               }
            break;
                        
            default:
               $text = $style->getProperty($name, $target);
         }
         
            // put the style together
         if($text) {
            $result .= "$jsMethod(\"$prop\", \"$text\");\n";
         }   
   }
    return $result;
  }


  // description: get Javascript to set style for collapsible list
  // returns: Javascript in string
  function getCListStyleJavascript() {
    $stylist = $this->getStylist();
    $style = $stylist->getStyle("collapsibleList");

    $properties = array(
      "aLinkColor" => $style->getProperty("aLinkColor"),
      "backgroundStyleListNear" => $style->toBackgroundStyle("listNear"),
      "backgroundStyleListNormal" => $style->toBackgroundStyle("listNormal"),
      "backgroundStyleListSelected" => $style->toBackgroundStyle("listSelected"),
      "backgroundStylePage" => $style->toBackgroundStyle("page"),
      "borderColor" => $style->getProperty("borderColor"),
      "borderThickness" => $style->getProperty("borderThickness"),
      "collapsedIcon" => $style->getProperty("collapsedIcon"),
      "dividerImage" => $style->getProperty("dividerImage"),
      "expandedIcon" => $style->getProperty("expandedIcon"),
      "selectedIcon" => $style->getProperty("selectedIcon"),
      "unselectedIcon" => $style->getProperty("unselectedIcon"),
      "textStyleNear" => $style->toTextStyle("near"),
      "textStyleNormal" => $style->toTextStyle("normal"),
      "textStyleSelected" => $style->toTextStyle("selected"),
      "width" => $style->getProperty("width")
    );

    $keys = array_keys($properties);
    for($i = 0; $i < count($keys); $i++)
      $result .= "top.code.cList_setStyle(\"".$keys[$i]."\", \"".$properties[$keys[$i]]."\");\n";

    return $result;
  }

  // description: get Javascript to set style for flow navigation
  // returns: Javascript in string
  function getFlowControlStyleJavascript() {
    $stylist = $this->getStylist();
    $style = $stylist->getStyle("flowControl");

    $properties = array(
      "controlBackgroundStyle" => $style->toBackgroundStyle(),
      "backImage" => $style->getProperty("backImage"),
      "backImageDisabled" => $style->getProperty("backImageDisabled"),
      "finishImage" => $style->getProperty("finishImage"),
      "finishImageDisabled" => $style->getProperty("finishImageDisabled"),
      "nextImage" => $style->getProperty("nextImage"),
      "nextImageDisabled" => $style->getProperty("nextImageDisabled"),
    );

    $keys = array_keys($properties);
    for($i = 0; $i < count($keys); $i++)
      $result .= "top.code.flow_setStyle(\"".$keys[$i]."\", \"".$properties[$keys[$i]]."\");\n";

    return $result;
  }

  // description: get Javascript to set style for info
  // returns: Javascript in string
  function getInfoStyleJavascript() {
     return($this->getStyleJavascript("info", "top.code.info_setStyle"));
  }

  // description: get Javascript to set style for tab
  // returns: Javascript in string
  function getTabStyleJavascript() {
    $stylist = $this->getStylist();
    $style = $stylist->getStyle("tab");

    $properties = array(
      "aLinkColor" => $style->getProperty("aLinkColor"),
      "backgroundStyle" => $style->toBackgroundStyle(),
      "logo" => $style->getProperty("logo"),
      "logoutImage" => $style->getProperty("logoutImage"),
      "monitorOffImage" => $style->getProperty("monitorOffImage"),
      "monitorOnImage" => $style->getProperty("monitorOnImage"),
//      "selectedImageBottom" => $style->getProperty("selectedImageBottom"),
      "selectedImageLeft" => $style->getProperty("selectedImageLeft"),
      "selectedImageRight" => $style->getProperty("selectedImageRight"),
//      "selectedImageTop" => $style->getProperty("selectedImageTop"),
      "textStyleSelected" => $style->toBackgroundStyle("selected").$style->toTextStyle("selected"),
      "textStyleUnselected" => $style->toBackgroundStyle("unselected").$style->toTextStyle("unselected"),
      "top" => $style->getProperty("top"),
//      "unselectedImageBottom" => $style->getProperty("unselectedImageBottom"),
      "unselectedImageLeft" => $style->getProperty("unselectedImageLeft"),
      "unselectedImageRight" => $style->getProperty("unselectedImageRight"),
//      "unselectedImageTop" => $style->getProperty("unselectedImageTop"),
      "updateOffImage" => $style->getProperty("updateOffImage"),
      "updateOnImage" => $style->getProperty("updateOnImage"),
      "manualOffImage" => $style->getProperty("manualOffImage")
    );

    $keys = array_keys($properties);
    for($i = 0; $i < count($keys); $i++)
      $result .= "top.code.tab_setStyle(\"".$keys[$i]."\", \"".$properties[$keys[$i]]."\");\n";

    return $result;
  }

    // description: get Javascript to set style for title
    // returns: Javascript in string
    function getTitleStyleJavascript() {
        $stylist = $this->getStylist();
        $style = $stylist->getStyle("title");

        $properties = array(
            "backgroundStyle" => $style->toBackgroundStyle(),
            "descriptionStyle" => $style->toTextStyle("description"),
            "logo" => $style->getProperty("logo"),
            "titleStyle" => $style->toTextStyle("title"),
            );

        $keys = array_keys($properties);
        for($i = 0; $i < count($keys); $i++)
            $result .= "top.code.title_setStyle(\"".$keys[$i]."\", \"".$properties[$keys[$i]]."\");\n";
    
        return $result;
    }

    function hasCCE() {
        // commented out monterey-specific cruft
        // pretty much all future products will have CCE
        // return file_exists("/etc/rc.d/init.d/cced.init");
	return true;
    }

    // this should not be used outside this class
    function getProductCode()
    {
        //Get product info 
        $build_file = "/etc/build";
        $BUILD_FILE = fopen($build_file, "r");
        $buildtext = fread($BUILD_FILE,filesize($build_file)); 
        fclose($BUILD_FILE);
        if (ereg("for a ([A-Za-z0-9\-]+) in", $buildtext, $regs)) 
        {
            $product = $regs[1];
        }
        return $product;
    }

    // unserialize and return the array of Error objects from the
    // handler page
    function &getErrors()
    {
        global $_serialized_errors;
        $errors = array();

        // handle serialized errors if present
        if(isset($_serialized_errors))
        {
            $errors = unserialize(urldecode($_serialized_errors));
        }

        return $errors;
    }

}
/*
Copyright (c) 2003 Sun Microsystems, Inc. All  Rights Reserved.

Redistribution and use in source and binary forms, with or without modification, 
are permitted provided that the following conditions are met:

-Redistribution of source code must retain the above copyright notice, this  list of conditions and the following disclaimer.

-Redistribution in binary form must reproduce the above copyright notice, 
this list of conditions and the following disclaimer in the documentation and/or 
other materials provided with the distribution.

Neither the name of Sun Microsystems, Inc. or the names of contributors may 
be used to endorse or promote products derived from this software without 
specific prior written permission.

This software is provided "AS IS," without a warranty of any kind. ALL EXPRESS OR IMPLIED CONDITIONS, REPRESENTATIONS AND WARRANTIES, INCLUDING ANY IMPLIED WARRANTY OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE OR NON-INFRINGEMENT, ARE HEREBY EXCLUDED. SUN MICROSYSTEMS, INC. ("SUN") AND ITS LICENSORS SHALL NOT BE LIABLE FOR ANY DAMAGES SUFFERED BY LICENSEE AS A RESULT OF USING, MODIFYING OR DISTRIBUTING THIS SOFTWARE OR ITS DERIVATIVES. IN NO EVENT WILL SUN OR ITS LICENSORS BE LIABLE FOR ANY LOST REVENUE, PROFIT OR DATA, OR FOR DIRECT, INDIRECT, SPECIAL, CONSEQUENTIAL, INCIDENTAL OR PUNITIVE DAMAGES, HOWEVER CAUSED AND REGARDLESS OF THE THEORY OF LIABILITY, ARISING OUT OF THE USE OF OR INABILITY TO USE THIS SOFTWARE, EVEN IF SUN HAS BEEN ADVISED OF THE POSSIBILITY OF SUCH DAMAGES.

You acknowledge that  this software is not designed or intended for use in the design, construction, operation or maintenance of any nuclear facility.
*/
?>
