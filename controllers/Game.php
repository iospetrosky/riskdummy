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
        
        if($current_game = get_cookie("current_game")) {
            $data["game"] = $this->game_model->get_game($current_game); // gets an object including players and dummies
            $this->load->view('game_display',$data);
        } else {
            $this->load->view('game_new',$data);
        }
	}

    public function newgame() {
        $players = explode("\n",$this->input->post("player_names"));
        $dummies = explode("\n",$this->input->post("dummy_names"));
        $auto = $this->input->post("autoassign");
        $res = $this->game_model->newgame($this->input->post("gname"),$players,$dummies,$auto);
        //$res = 4;
        set_cookie("current_game",$res,10000000);
        
        $data["url"] = explode("/", $this->uri->uri_string());
        $data["game"] = $this->game_model->get_game($res); // gets an object including players and dummies

        $this->load->view('intro',$data);
        $this->load->view('game_display',$data);
    }
    
    public function delgame($id_game = false) {
        if ($id_game) {
            $current_game = $id_game;
        } else {
            $current_game = get_cookie("current_game");
        }
        $this->game_model->delete_game($current_game);
        set_cookie("current_game",0,-100);

        $data["url"] = explode("/", $this->uri->uri_string());
        $this->load->view('intro',$data);
        $this->load->view('game_new',$data);
    }
    
    public function updcolors() {
        $players = $this->input->post("players");
        $colors = $this->input->post("colors");
        for($j=0; $j<count($players); $j++) {
            $this->game_model->change_color($players[$j], $colors[$j]);
        }
        echo "Colors updated";
    }
    
}
    