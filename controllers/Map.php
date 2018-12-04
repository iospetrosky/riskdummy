<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Map extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('html_gen_helper');
        $this->load->model('map_model');
        $this->load->model('sets_model');
    }
    
	public function index()	{
        $data["url"] = explode("/", $this->uri->uri_string());
        $data["css"] = array("map");
        $this->load->view('intro',$data);
        $data["cells"] = $this->map_model->map_info(get_cookie("current_game"));
        $this->load->view('map_form',$data);
	}
}
    