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
    
}