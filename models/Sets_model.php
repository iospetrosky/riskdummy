<?php
// this is the DATA layer. No output here!!!

defined('BASEPATH') OR exit('No direct script access allowed');

class Sets_model extends CI_Model {

    public function __construct()    {
        //$this->load->database(); // autoloaded
    }
    
    public function continent_list() {
        $query = $this->db->select("id, cname")
                          ->from('continents')
                          ->order_by("id asc")
                          ->get();    
        return $query->result();
    }

    public function territory_list() {
        $query = $this->db->select("id, tname, id_continent, map_x, map_y")
                          ->from('territories')
                          ->get();    
        return $query->result();
    }
    
    public function player_territory_info($id_terr) {
        return $this->db->select("*")->from("player_territory")
                        ->where("id",$id_terr)->get()->result()[0];
    }
    
    public function userlist($id_game) {
        return $this->db->select("*")->from("players")
                        ->where("id_game",$id_game)->get()->result();
    }
}