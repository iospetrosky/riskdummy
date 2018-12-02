
<?php
// this is the DATA layer. No output here!!!

defined('BASEPATH') OR exit('No direct script access allowed');

class Editor_model extends CI_Model {

    public function __construct()    {
        //$this->load->database(); // autoloaded
    }
    //************************************************************************
    public function save_continent($data) {
        $this->save_data("continents",$data);
    }
    public function new_continent() {
        $this->new_data("continents","cname","new continent");
    }
    public function delete_continent($id) {
        $this->delete_data("continents", $id);
    }

    public function save_territory($data) {
        $this->save_data("territories",$data);
    }
    public function new_territory() {
        $this->new_data("territories","tname","new territory");
    }
    public function delete_territory($id) {
        $this->delete_data("territories", $id);
    }

    //************************************************************************
    // the generic new - delete - save actions
    //************************************************************************
    private function new_data($table, $main_field, $def_value) {
        $this->db->set($main_field,$def_value);
        $this->db->insert($table);
    }
    private function delete_data($table, $key_val, $key_field = "id") {
        $this->db->where($key_field,$key_val);
        $this->db->delete($table);
    }
    private function save_data($table, $data) {
        //$DATA must have a row_id fields that maps with the ID field of a table
        //this because we can also edit the ID in some cases
        $this->db->where('id',$data['row_id']);
        unset($data["row_id"]);
        $this->db->update($table,$data);
    }
    
    
    
    
    
    
    
}
    