<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Game extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('html_gen_helper');
        $this->load->model('game_model');
        $this->load->model('sets_model');
    }
    
	public function index($current_game = false)	{
        $data["url"] = explode("/", $this->uri->uri_string());
        $this->load->view('intro',$data);
        // load different views according to cookie settings
        //set_cookie("last_user",$current_user,100000);
        if ($current_game) {
            $data["game"] = $this->game_model->get_game($current_game); // gets an object including players and dummies
            $this->load->view('game_display',$data);
        } elseif($current_game = get_cookie("current_game")) {
            $data["game"] = $this->game_model->get_game($current_game); // gets an object including players and dummies
            $this->load->view('game_display',$data);
        } else {
            $this->load->view('game_new',$data);
        }
	}

    public function newgame() {
        $players = explode("\n",$this->input->post("player_names"));
        $dummies = explode("\n",$this->input->post("dummy_names"));
        $res = $this->game_model->newgame($this->input->post("gname"),$players,$dummies);
        //$res = 4;
        set_cookie("current_game",$res,10000000);
        $this->index($res);
    }
    
}
    