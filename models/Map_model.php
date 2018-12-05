<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Map_model extends CI_Model {

    public function __construct()    {
        //$this->load->database(); // loaded by default
    }

    public function map_info($id_game = 0) {
        $sql = "select pt.id, pt.id_player, pt.id_territory, pt.armies, t.tname, t.id_continent,t.map_x, t.map_y,
                    p.pname,p.ptype, p.pcolor
                from player_territory pt
                inner join territories t on pt.id_territory=t.id
                inner join players p on pt.id_player=p.id
                where pt.id_game = $id_game and p.id_game=$id_game
                order by t.map_y asc, t.map_x asc";
        $query = $this->db->query($sql);
        return $query->result();
    }
    
    public function save_territory($id,$player,$armies,$id_game) {
        $old_player = $this->db->select("id_player")
                           ->from("player_territory")
                           ->where("id",$id)
                           ->get()->result()[0]->id_player;

        $this->db->trans_begin();
        $this->db->set("id_player",$player)
                 ->set("armies",$armies)
                 ->where("id",$id)
                 ->update("player_territory");
        // now also update the stats in the players table (num_territories and num_armies)         
        $values = $this->db->query("select count(id_territory) as num_terr, sum(armies) as num_armies
                            from player_territory where id_game = $id_game and id_player = $player")
                            ->result()[0];
        $this->db->set("num_territories",$values->num_terr)
                 ->set("num_armies",$values->num_armies)
                 ->where("id",$player)
                 ->update("players");
        if ($old_player != $player) {
            //update also the old player
            $values = $this->db->query("select count(id_territory) as num_terr, sum(armies) as num_armies
                                from player_territory where id_game = $id_game and id_player = $old_player")
                                ->result()[0];
            $this->db->set("num_territories",$values->num_terr)
                     ->set("num_armies",$values->num_armies)
                     ->where("id",$old_player)
                     ->update("players");
        }

        $this->db->trans_commit();
        $sql = "select pt.id, pt.id_player, pt.armies, p.pname 
                    from player_territory pt
                    inner join players p on pt.id_player=p.id
                    where pt.id = $id";
        return $this->db->query($sql)->result()[0];
    }
    
}
    