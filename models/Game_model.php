<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Game_model extends CI_Model {

    public function __construct()    {
        //$this->load->database(); // loaded by default
    }

    public function newgame($game_name, $players, $dummies, $auto) {
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
                    "ptype" => "H", // human
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
        $data["ptype"] = "D"; // dummies
        foreach($dummies as $pl) {
            $data["pname"] = trim($pl);
            $data["pcolor"] = $colors[$j];
            $this->db->insert("players",$data);
            $j++;
        }
        if ($auto == 'Y') {
            // auto assign territories
            $players = $this->db->select("id")->from("players")->where("id_game",$id_game)->get()->result();
            $nump = count($players);
            
            $terr = $this->db->select("id")->from("territories")
                        ->where("id <> 0")
                        ->get()->result();
            shuffle($terr);
            $p = 0;
            foreach($terr as $t) {
                $data = array (
                    "id_player" => $players[$p]->id,
                    "id_territory" => $t->id,
                    "id_game" => $id_game
                );
                $this->db->insert("player_territory",$data);
                $p++;
                if ($p>=$nump) $p=0;
            }
            // update the territory count of the players and the default armies
            foreach($players as $pl) {
                $this->db->set("num_territories", $this->get_player_territories($pl->id));
                $this->db->set("num_armies", $this->get_player_armies($pl->id));
                $this->db->where("id", $pl->id)
                         ->update("players");
            }
            
        }
        $this->db->trans_commit();
        return $id_game;
    }
    
    public function get_player_territories($id) {
        $query = $this->db->select("count(id) as numt")
                        ->from("player_territory")
                        ->where("id_player",$id)
                        ->get();
        return $query->result()[0]->numt;
    }

    public function get_player_armies($id) {
        $query = $this->db->select("sum(armies) as numa")
                        ->from("player_territory")
                        ->where("id_player",$id)
                        ->get();
        return $query->result()[0]->numa;
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
    
    public function delete_game($id_game) {
        //this should be done with a cascade delete
        //anyway ...
        $this->db->trans_begin();
        $this->db->delete("players",array("id_game"=>$id_game));
        $this->db->delete("games",array("id"=>$id_game));
        $this->db->delete("player_territory",array("id_game"=>$id_game));
        $this->db->trans_commit();
    }

    public function change_color($player, $color) {
        $this->db->set("pcolor", $color)
                    ->where("id", $player)
                    ->update("players");
    }

    
}
    