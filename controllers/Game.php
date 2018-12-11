<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Game extends CI_Controller {
    
    public function __construct() {
        parent::__construct();
        $this->load->helper('html_gen_helper');
        $this->load->model('game_model');
        $this->load->model('sets_model');
    }
    
    public function joingame($id) {
        set_cookie("current_game",$id,10000000);
        echo "Game set to $id - now load another page";
    }
    
	public function index()	{
        $data["url"] = explode("/", $this->uri->uri_string());
        $data["css"] = array("combat");
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
     
    public function finalizeattack() {
        $this->game_model->finalize_attack(
            $this->input->post("oid"),
            $this->input->post("id_attacker"),
            (int)$this->input->post("attack_loss"),
            (int)$this->input->post("attack_with"),
            $this->input->post("did"),
            $this->input->post("id_defender"),
            (int)$this->input->post("defense_loss")
            //$this->input->post("invasion")
        );
        header("Location: " . config_item('base_url') . '/' . config_item('index_page') . "/play/map");
        die();
    }
    
    public function reinforce($id_player) {
        $res = $this->game_model->dummy_place_bonus_armies($id_player);
        echo json_encode($res);
    }
    public function dummyfirst() {
        $res = $this->game_model->dummy_first_army_placement(get_cookie("current_game"));
        if($res) {
            header("Location: " . config_item('base_url') . '/' . config_item('index_page') . "/play/map");
            die();
        } else {
            echo "Dummies already placed!";
        }
    }

    public function roll($dice_id) {
        $ret = new stdClass();
        $ret->id = $dice_id;
        $ret->roll = rand(1,6);
        echo json_encode($ret);
    }
    
    public function startattack($id_player) {
        $res = $this->game_model->start_attack($id_player, get_cookie("current_game"));
        //return the form to manage the attack
        $form = form_open(config_item('base_url') . '/' . config_item('index_page') . '/game/finalizeattack', "", array(
                                "id_attacker" => $res->id_attacker,
                                "id_defender" => $res->id_defender,
                                "oid" => $res->oid, // player_territory ID of the origin
                                "did" => $res->did, // player_territory ID of the destination
                                "id_origin" => $res->origin,
                                "id_destination" => $res->destination
                    ));
        $form .= div(
                    div($res->attacker_name, array("class"=>"cmb_cell cmb_info")) .
                    div($res->defender_name, array("class"=>"cmb_cell cmb_info")) 
                    , array("class"=>"cmb_line","style"=>"border-top:4px solid black")
                );
        $form .= div(
                    div($res->origin_name, array("class"=>"cmb_cell cmb_info")) .
                    div($res->dest_name, array("class"=>"cmb_cell cmb_info")) 
                    , array("class"=>"cmb_line")
                );
        
        $attack_rolls_a = "";
        $attack_rolls_b = "";
        for ($d=10;$d<$res->army_attacker-1+10;$d++) {
            $attack_rolls_a .= div("0", array("class"=>"dice_roll","id"=>"{$d}_dice","style"=>"border-color:red") );
            if ($d == 12) break;
        }
        for ($d=20;$d<$res->army_defender+20;$d++) {
            $attack_rolls_b .= div("0", array("class"=>"dice_roll","id"=>"{$d}_dice","style"=>"border-color:green") );
            if ($d == 22) break;
        }
        $attack_rolls_b .= button("Reset",array("id"=>"cmd_reset_dice"));
        
        $form .= div(
                    div($attack_rolls_a, array("class"=>"cmb_cell")) .
                    div($attack_rolls_b, array("class"=>"cmb_cell")),
                    array("class"=>"cmb_line","style"=>"height:60px")
                );
        
        $form .= div(
                    form_label("Attacking with", "att_with" ) .
                    form_input("attack_with", $res->army_attacker-1,"readonly=readonly id=att_with size=2")
                    ,array("class"=>"cmb_line")
                    );
        $form .= div(
                    form_label("Attacker losses", "att_loss")  .
                    form_input("attack_loss", 0,"id=att_loss size=2")
                    ,array("class"=>"cmb_line")
                    );
        $form .= div(
                    form_label("Defender losses", "def_loss")  .
                    form_input("defense_loss", 0,"id=def_loss size=2") .
                    span(" (of {$res->army_defender})"),
                    array("class"=>"cmb_line") 
                    );
        $form .= div(
                    form_submit("att_save", "Save")
                    ,array("class"=>"cmb_line")
                    );
        
        $form .= form_close();
        
        echo $form;
    }
    
}