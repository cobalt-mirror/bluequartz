<?php (defined('BASEPATH')) OR exit('No direct script access allowed');

/* load MX core classes */
require_once dirname(__FILE__).'/Lang.php';
require_once dirname(__FILE__).'/Config.php';

/**
 * Modular Extensions - HMVC
 *
 * Adapted from the CodeIgniter Core Classes
 * @link	http://codeigniter.com
 *
 * Description:
 * This library extends the CodeIgniter CI_Controller class and creates an application 
 * object allowing use of the HMVC design pattern.
 *
 * Install this file as application/third_party/MX/Base.php
 *
 * @copyright	Copyright (c) 2011 Wiredesignz & Copyright (c) 2017 Michael Staiber
 * @version 	5.4
 * 
 *  Extended for BlueOnyx Chorizo GUI by Michael Stauber:
 *  ======================================================
 * 
 *  This Class has been extended to act as a storage for essential CCEd related
 *  information so that the Chorizo GUI Classes, Libraries and Controllers have
 *  easy access to our internals:
 * 
 *  The stored info includes:
 *
 * - loginName
 * - sessionId
 * - The entire "User" Object of the logged in User from CODB.
 * - If the User has Shell enabled (saves one frequent cap related lookup)
 * - Entire CODB "System" Object
 * - Entire Namespace "Support" of the "System" Object (for the Wiki)
 * - Instance of $serverScriptHelper
 * - Instance of $cceClient
 *
 * All these internals are now reachable via $CI after instantiation. \o/
 *
 * This allows us to massively cut down on CODB related database transactions.
 * Of course this depends on the page, but even simple GUI pages that previously
 * did ~70 CODB transactions now make do with 11 transactions. 
 * 
 * License:
 * ========
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 **/
class CI extends CI_Controller
{
	public static $APP;
	public $BX_SESSION;
	public $serverScriptHelper;
	public $cceClient;
	public $BX_System;
	public $BX_Support;
	
	public function __construct() {
		
		/* assign the application instance */
		self::$APP = $this;
		
		global $LANG, $CFG;
		
		/* re-assign language and config for modules */
		if ( ! is_a($LANG, 'MX_Lang')) $LANG = new MX_Lang;
		if ( ! is_a($CFG, 'MX_Config')) $CFG = new MX_Config;
		
		parent::__construct();

		$this->BX_SESSION = array(
            'loginName' => '', 
            'sessionId' => '', 
            'loginUser' => '', 
            'userShell' => '' 
            );

        // Get $sessionId and $loginName from Cookie (if they are set) and store them in $CI->BX_SESSION:
        $this->BX_SESSION['sessionId'] = $this->input->cookie('sessionId');
        $this->BX_SESSION['loginName'] = $this->input->cookie('loginName');
	}

    public function setSSH($SSH) {
        $this->serverScriptHelper = $SSH;
    }

    public function getSSH() {
        if (isset($this->serverScriptHelper)) {           
            return $this->serverScriptHelper;
        }
        else {
            return NULL;
        }
    }

    public function setCCE($CCE) {
        $this->cceClient = $CCE;
    }

    public function getCCE() {
        if (isset($this->cceClient)) {           
            return $this->cceClient;
        }
        else {
            return NULL;
        }
    }

    public function setSystem($sys) {
        $this->BX_System = $sys;
    }

    public function getSystem() {
        if (isset($this->BX_System)) {           
            return $this->BX_System;
        }
        else {
	        // Find out if serverScriptHelper has already been initialized:
	        $this->serverScriptHelper = $this->getSSH();
	        if (!$this->serverScriptHelper) {
	            // It has not been initialized yet, so we do it here:
	            $this->serverScriptHelper = new ServerScriptHelper($this->BX_SESSION['sessionId'], $this->BX_SESSION['loginName']);
	            $this->cceClient = $this->serverScriptHelper->getCceClient();
	        }
	        else {
	            // Was already initialized. Reuse it:
	            $this->cceClient = $this->getCCE();
	        }
			$this->BX_System = $this->cceClient->getObject('System');
			$this->BX_Support = $this->cceClient->get($this->BX_System['OID'], "Support");
            return $this->BX_System;
        }
    }

    public function setSupport($sys) {
        $this->BX_Support = $sys;
    }

    public function getSupport() {
        if (isset($this->BX_Support)) {
        	return $this->BX_Support;
        }
        else {
	        // Find out if serverScriptHelper has already been initialized:
	        $this->serverScriptHelper = $this->getSSH();
	        if (!$this->serverScriptHelper) {
	            // It has not been initialized yet, so we do it here:
	            $this->serverScriptHelper = new ServerScriptHelper($this->BX_SESSION['sessionId'], $this->BX_SESSION['loginName']);
	            $this->cceClient = $this->serverScriptHelper->getCceClient();
	        }
	        else {
	            // Was already initialized. Reuse it:
	            $this->cceClient = $this->getCCE();
	        }
			$this->BX_System = $this->cceClient->getObject('System');
			$this->BX_Support = $this->cceClient->get($this->BX_System['OID'], "Support");
            return $this->BX_Support;
        }
    }

    public function setBX_SESSION($loginName='', $sessionId='', $loginUser='', $userShell='') {
    	if ($loginName != '') {
    		$this->BX_SESSION['loginName'] = $loginName;
    	}
    	if ($loginName != '') {
    		$this->BX_SESSION['sessionId'] = $sessionId;
    	}
    	if ($loginName != '') {
    		$this->BX_SESSION['loginUser'] = $loginUser;
    	}
    	if ($loginName != '') {
    		$this->BX_SESSION['userShell'] = $userShell;
    	}
    }

    public function getBX_SESSION() {
        if (isset($this->BX_SESSION)) {           
            return $this->BX_SESSION;
        }
        else {
            return NULL;
        }
    }

}

/* create the application object */
new CI;