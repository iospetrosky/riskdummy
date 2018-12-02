<?php
class Welcome extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->model('welcome_model');
    }


    public function index()  {
        $data["url"] = explode("/", $this->uri->uri_string());
        $this->load->view('intro',$data);
    }
    
    public function ajax()  {

    }

}