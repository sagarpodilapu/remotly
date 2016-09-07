<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Home extends CI_Controller {

	/**
	 * Index Page for this controller.
	 *
	 * Maps to the following URL
	 * 		http://example.com/index.php/welcome
	 *	- or -
	 * 		http://example.com/index.php/welcome/index
	 *	- or -
	 * Since this controller is set as the default controller in
	 * config/routes.php, it's displayed at http://example.com/
	 *
	 * So any other public methods not prefixed with an underscore will
	 * map to /index.php/welcome/<method_name>
	 * @see https://codeigniter.com/user_guide/general/urls.html
	 */
	public function index()
	{
		$this->load->view('index');
	}

  public function how_it_works(){
    $this->load->view('how_it_works');
  }

	public function get_started(){
		$this->session->set_userdata('state',hash('sha256', microtime(TRUE).rand().$_SERVER['REMOTE_ADDR']));
		unset($_SESSION['access_token']);
		$params = array(
	    'client_id' => GIT_OAUTH2_CLIENT_ID,
	    'redirect_uri' => base_url().'complete',
	    'scope' => 'user',
	    'state' => $this->session->userdata('state')
	  );
		$authorizeURL = 'https://github.com/login/oauth/authorize';
		$tokenURL = 'https://github.com/login/oauth/access_token';
		$apiURLBase = 'https://api.github.com/';
		redirect($authorizeURL . '?' . http_build_query($params),'refresh');
	}

	public function complete(){
		if($this->input->get('code')) {
  		if(!$this->input->get('state') || $this->session->userdata('state') != $this->input->get('state')) {
    		echo 'state does not exist or mismatched';
    		exit;
			}
			$params = array(
		    'client_id' => GIT_OAUTH2_CLIENT_ID,
		    'client_secret' => GIT_OAUTH2_CLIENT_SECRET,
		    'redirect_uri' => base_url().'complete',
		    'state' => $this->session->userdata('state'),
		    'code' => $this->input->get('code')
		  );
			$token = $this->apiRequest($tokenURL, $params);
			echo '<pre>',var_dump($params); exit;
			$this->session->set_userdata('access_token',$token->access_token);
		}
		if($this->session->userdata('access_token')) {
		  $user = $this->apiRequest($apiURLBase . 'user');
		  echo '<h3>Logged In</h3>';
		  echo '<h4>' . $user->name . '</h4>';
		  echo '<pre>';
		  print_r($user);
		  echo '</pre>';
		} else {
		  echo 'login';
		}

	}

	private function apiRequest($url, $post=FALSE, $headers=array()) {
	  $ch = curl_init($url);
	  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
	  if($post)
	    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
	  $headers[] = 'Accept: application/json';
	  if($this->session->userdata('access_token'))
	    $headers[] = 'Authorization: Bearer ' . $this->session->userdata('access_token');
	  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	  $response = curl_exec($ch);
	  return json_decode($response);
	}
}
