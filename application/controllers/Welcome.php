<?php

defined('BASEPATH') or exit('No direct script access allowed');
require APPPATH . '/libraries/REST_Controller.php';

use PhpParser\Node\Expr\Cast\String_;
use Restserver\Libraries\REST_Controller;

class Welcome extends REST_Controller
{

	public function __construct($config = 'rest')
	{
		parent::__construct($config);
	}

	public function index_get()
	{
		$this->response(array(
			"status"        => true,
			"respon_code"   => REST_Controller::HTTP_FORBIDDEN,
			"respon_mess"   => "Forbidden",			
		), REST_Controller::HTTP_OK);
	}
}
