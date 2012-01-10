<?php
// $Id: ecomm_login.lib,v 0.1 2010/11/07 19:00:07 lateporter Exp $

/**
 * @file
 * Classes to implement Authenication
 */


class WtGroupAuthenticator {
	private $_wtgroup_response;
	
	function __construct($wtgroup_response) {
		$this->_wtgroup_response = $wtgroup_response;
  }

	public static function create($post_data) {
		$wtgroup_response = WtGroupRequester::getResponse($post_data);
		return new WtGroupAuthenticator($wtgroup_response);
	}
	
	function cookie() {
		$this->_wtgroup_response->jsession_id();
	}
	
	function message() {
		return $this->_wtgroup_response->message();
	}
	
	function isAuthenticated() {
		return $this->validLogin() && $this->validCode();
	}
	
	function validLogin() {
		return $this->_wtgroup_response->authenticated() == "true";
	}

	function validCode() {
		return $this->_wtgroup_response->code() == "0";
	}
}


class WtGroupRequester {
	private $_post_data;
	private $_headers;
	
	function __construct($post_data) {
		$this->_post_data = $post_data;
		$this->_headers = array('Content-type' => 'application/x-www-form-urlencoded');
	}

	public static function getResponse($post_data) {
		$requester = new WtGroupRequester($post_data);
		return $requester->makeRequest();
	}
	
	function makeRequest() {
		$response = drupal_http_request('https://dev1.wtgroupllc.com/ViewDev/drupal', $this->_headers, "POST", $this->postParams());
		return new WtGroupResponse($response->data, $response->headers["Set-Cookie"]);
	}

	function postParams() {
		$data = "";
		$vars = $this->formData();
		foreach ($vars as $Index => $Value) 
		        $data .= urlencode($Index) . "=" . urlencode($Value) . "&";
		return $data;
	}
	
	function formData() {
		 return array("userid" => $this->_post_data['userid'], "password" => $this->_post_data['password'], "userAction" => "authenticate", "submit" => "submit");
	}	
}

class WtGroupResponse {
	private $_raw_response;
	private $_cookie_data;
	
	function __construct($raw_response, $cookie_data) {
		$this->_raw_response = $raw_response;
		$this->_cookie_data = $cookie_data;
  }

	function authenticated() {
		return $this->get_response_value("Authenticated");
	}
	
	function code() {
		return $this->get_response_value("ResponseCode");
	}
	
	function message() {
		return $this->get_response_value("ResponseMessage");
	}

	function jsession_id() {
		$cookie_array = explode("=", $this->_cookie_data);
		$jsession_array = explode("=", $cookie_array[1]);
		$jsession_id_array = explode(";", $jsession_array[0]);
		return $jsession_id_array[0];
	}

	function get_response_value($key) {
		preg_match('/\b'.$key.':\s+(.+)\b/', $this->_raw_response, $matches);
		return $matches[1];
	}

}