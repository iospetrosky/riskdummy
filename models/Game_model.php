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
    
    public function dummy_first_army_placement($id_game) {
        // there may be more than one dummy so the act in turn
        // dummies look for easy shots
        $dummies = $this->db->query("select id, num_territories, num_armies from players where ptype='D' and id_game = $id_game")
                            ->get()->result();
        $max_armies = 34; // this depends from the number of players... see the rules
        $this->db->trans_begin();
        //do {
            foreach($dummies as &$dummy) {
                if ($dummy->armies < $max_armies) {
                    //look for a potentially easy shot and reinforce nearby
                    $easy = $this->find_easy_shots($id_game, $dummy->id, 2, 1);
                    foreach($easy as $shot) {
                        if ($shot->arm_dest == 1) {
                            if (($left_armies = $max_armies - $dummy->num_armies) > 0) {
                                if ($left_armies >= 2) {
                                    $dummy->armies += 2;
                                    $this->db->set("")
                                }
                            }
                        }
                    }
                }
            }
        //}
    }
    
    protected function find_easy_shots($id_game, $id_player, $enemy_max_armies = 2, $own_min_armi = 3) {
        $sql = "select o.id,o.armies arm_orig,origin,destination,d.id_player as id_enemy,d.armies as arm_dest
                    from player_territory o
                        inner join v_attack_lines al on o.id_territory = al.origin
                        inner join player_territory d on al.destination = d.id_territory
                        where o.id_game = $id_game and d.id_game = $id_game 
                                and o.id_player = $id_player and d.armies <= $enemy_max_armies
                                and o.armies >= $own_min_armi
                        order by d.armies asc";
        
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
    