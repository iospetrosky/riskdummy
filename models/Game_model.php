<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Game_model extends CI_Model {

    public function __construct()    {
        //$this->load->database(); // loaded by default
    }

    public function newgame($game_name, $players, $dummies) {
        $this->db->trans_begin();
        $data = array(
                    'gname' => $game_name,
                    'numplayers' => count($players) + count($dummies),
                    'humans' => count($players),
                    'dummies' => count($dummies)
        );
        $this->db->insert("games",$data);
        $id_game = $this->db->insert_id();
        $data = array (
                    "pname" => "",
                    "ptype" => "H",
                    "pcolor" => "yellow",
                    "id_game" => $id_game
        );
        $j = 0;
        $colors = array("yellow","red","blue","black","purple","orange");
        foreach($players as $pl) {
            $data["pname"] = trim($pl);
            $data["pcolor"] = $colors[$j];
            $this->db->insert("players",$data);
            $j++;
        }
        foreach($dummies as $pl) {
            $data["pname"] = trim($pl);
            $data["pcolor"] = $colors[$j];
            $this->db->insert("players",$data);
            $j++;
        }
        $this->db->trans_commit();
        return $id_game;
        
        
        //$query = $this->db->get('tile_sets');
        //return $query->result();
    }
    
    public function get_game($id_game) {
        $query = $this->db->select("*")
                        ->from("games")
                        ->where("id",$id_game)
                        ->get();
        $game = $query->result()[0];
        $game->players = $this->db->select("*")
                            ->from("players")
                            ->where("id_game", $game->id)
                            ->get()->result();
                            
        return $game;
    }
}
    