
<?php
//this is the presentation layer - get data from a model and return to the caller
//this is an interface between views and models
defined('BASEPATH') OR exit('No direct script access allowed');

class Editor extends CI_Controller {
    
    public function __construct()
    {
        parent::__construct();
        $this->load->helper('html_gen_helper');
        $this->load->model('editor_model');
        $this->load->model('sets_model');
    }
    
	public function index()
	{
	    // load all the data needed in the views in variables to be passed as second parameter
	    
        $data["url"] = explode("/", $this->uri->uri_string());
        $this->load->view('intro',$data);
	}
    
    public function continent($action = false, $id = false) {   
        switch($action) {
            case 'save':
                $this->editor_model->save_continent($this->input->post(NULL,false));
                break;
            case 'new':
                $this->editor_model->new_continent();
                break;
            case 'del':
                $this->editor_model->delete_continent($id);
                break;
        }
        //print_r($this->players_model->players_list());
        $data['list'] = $this->sets_model->continent_list(); 
        $this->index();
        $this->load->view('continent_form',$data);
    }

    public function territory($action = false, $id = false)    {   
        switch($action) {
            case 'save':
                $this->editor_model->save_territory($this->input->post(NULL,false));
                break;
            case 'new':
                $this->editor_model->new_territory();
                break;
            case 'del':
                $this->editor_model->delete_territory($id);
                break;
        }
        //print_r($this->players_model->players_list());
        $data['list'] = $this->sets_model->territory_list(); 
        $data['majors'] = array();
        foreach($this->sets_model->continent_list() as $cnt) {
            $data['continents'][$cnt->id] = $cnt->cname;
        }
        $this->index();
        $this->load->view('territory_form',$data);
    }
    
    
    
}
    