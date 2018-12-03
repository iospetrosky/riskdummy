<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Game extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('html_gen_helper');
        $this->load->model('game_model');
        $this->load->model('sets_model');
    }
    
	public function index()	{
        $data["url"] = explode("/", $this->uri->uri_string());
        $this->load->view('intro',$data);
        // load different views according to cookie settings
        //set_cookie("last_user",$current_user,100000);
        
        if($current_game = get_cookie("current_game")) {
            $this->load->view('game_display',$data);
        } else {
            $this->load->view('game_new',$data);
        }
	}

    public function newgame() {
        print_r($this->input->post());
    }
    
}
    