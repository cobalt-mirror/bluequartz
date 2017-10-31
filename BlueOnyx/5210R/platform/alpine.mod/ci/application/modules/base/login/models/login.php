<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

class LoginModel extends CI_Model {

	var $username_field;
	var $password_field;
	var $secureConnect;

    function __construct()
    {
        parent::__construct();
    }

}
