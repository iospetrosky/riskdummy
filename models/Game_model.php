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
            $id_player = $this->db->insert_id();
            $this->db->insert("dummy_cards",array("id_player"=>$id_player, "id_game"=>$id_game));
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
    
    public function get_bonus_armies($id_player) {
        // 1 army every 3 territories
        // armies for full continents
        // armies from cards 
        $ret = new stdClass();
        
        $ret->armies = (int)$this->db->select("(num_territories / 3) as armies")
                           ->from("players")
                           ->where("id",$id_player)
                           ->get()->result()[0]->armies;
        $ret->standard = "Assigned {$ret->armies} for the territories";
        // check if the player has a full continent
        $sql = "select count(pt.id_player) as player_territories, c.id, c.num_territories, c.bonus_armies
                from player_territory pt
                inner join territories t on pt.id_territory=t.id
                inner join continents c on t.id_continent = c.id
                where pt.id_player = $id_player
                group by c.id, c.num_territories, c.bonus_armies
                order by 2 asc";
        $stats = $this->db->query($sql)->result();
        $ret->continents = 0;
        foreach($stats as $st) {
            if ($st->player_territories == $st->num_territories) {
                $ret->continents += $st->bonus_armies;
            }
        }
        $ret->armies += $ret->continents;
        $ret->continents = "Assigned {$ret->continents} for full contintent(s)";
        // check the cards
        $cards = $this->db->select("infantry, cavalry, artillery, jolly")
                          ->from("dummy_cards")
                          ->where("id_player",$id_player)
                          ->get()->result()[0];
        $ret->cards = "No cards bonus";
        if ($cards->jolly>0) {
            if ($cards->artillery>=2) {
                $ret->armies += 12;
                $this->use_cards($id_player, "jolly","artillery","artillery");
                $ret->cards = "Assigned 10 for Jolly and 2 Artillery";
            } elseif ($cards->infantry>=2) {
                $ret->armies += 12;
                $this->use_cards($id_player, "jolly","infantry","infantry");
                $ret->cards = "Assigned 10 for Jolly and 2 Infantry";
            } elseif ($cards->cavalry>=2) {
                $ret->armies += 12;
                $this->use_cards($id_player, "jolly","cavalry","cavalry");
                $ret->cards = "Assigned 10 for Jolly and 2 Cavalry";
            }
        } else {
            //three different
            if (($cards->artillery > 0) && ($cards->infantry > 0) && ($cards->cavalry > 0)) {
                $ret->armies += 10;
                $this->use_cards($id_player, "infantry","cavalry","artillery");
                $ret->cards = "Assigned 12 for 3 different cards";
            } elseif ($cards->artillery >= 3) {
                $ret->armies += 4;
                $this->use_cards($id_player, "artillery","artillery","artillery");
                $ret->cards = "Assigned 8 for 3 Artillery";
            } elseif ($cards->infantry >= 3) {
                $ret->armies += 6;
                $this->use_cards($id_player, "infantry","infantry","infantry");
                $ret->cards = "Assigned 6 for 3 Infantry";
            } elseif ($cards->cavalry >= 3) {
                $ret->armies += 8;
                $this->use_cards($id_player, "cavalry","cavalry","cavalry");
                $ret->cards = "Assigned 7 for 3 Cavalry";
            }
        }
        return $ret;
    }

    public function dummy_place_bonus_armies($id_player) {
        $dummy = $this->db->select("id, num_armies, pname, id_game")
                          ->from("players")
                          ->where("id",$id_player)
                          ->get()->result()[0];

        $this->db->trans_begin();
        $ret = new stdClass();
        $ret->army_bonus = $this->get_bonus_armies($id_player); // this changes the cards so it must be in the transaction
        $armies = $ret->army_bonus->armies; // for local calculation only
        $ret->message = "Assigned $armies armies to {$dummy->pname}";
        $ret->id = $id_player;
        $ret->max_armies = $armies + $dummy->num_armies;
        $ret->places = array(); // list of army assignments
        $k = 0;
        do {
            $next_loop = false;
            if ($dummy->num_armies < $ret->max_armies) {
                $next_loop = true;
                $put_armies = 0;
                //look for a potentially easy shot and reinforce nearby
                if ($shots = $this->find_weak_territories($dummy->id_game, $dummy->id)) {
                    //try to put 3 armies more than the defender
                    $shot = $shots[0];
                    $put_armies = $shot->army_defender - $shot->army_attacker + 3;
                } else {
                    // put an army on the weakest territory
                    $shot = $this->find_my_weaker_territories($dummy->id_game, $dummy->id)[0];
                    $put_armies = 1;
                }
                if ($put_armies <= 0) $put_armies = 1;
                //armies available 
                if (($left_armies = $ret->max_armies - $dummy->num_armies) > 0) {
                    if ($left_armies < $put_armies) $put_armies = $left_armies;
                    $dummy->num_armies += $put_armies;
                    $this->db->query("update player_territory set armies = armies + ? where id = ?",array($put_armies, $shot->oid));
                    $orig_name = $this->db->select("tname")->from("territories")
                                      ->where("id",$shot->origin)->get()->result()[0]->tname;
                    $ret->places[] = "$put_armies positioned on {$orig_name}";
                }
            }
        } while(($next_loop) && ($k++<10)); // max 10 attempts then exit anyway
        // update the dummy
        unset($dummy->id_game); unset($dummy->pname);
        $this->db->set($dummy)
            ->where("id",$dummy->id)
            ->update("players");
            
        $this->db->trans_commit();
        return $ret;
    }


    public function dummy_first_army_placement($id_game) {
        // there may be more than one dummy so the act in turn
        // dummies look for easy shots
        $dummies = $this->db->query("select id, num_territories, num_armies, pname from players where ptype='D' and id_game = $id_game")->result();
        $max_armies = 35; // this depends from the number of players... see the rules
        $this->db->trans_begin();
        do {
            // loop goes on until there is at least one dummy with armies
            foreach($dummies as &$dummy) {
                if ($dummy->num_armies < $max_armies) {
                    $next_loop = true;
                    //look for a potentially easy shot and reinforce nearby
                    $shot = $this->find_weak_territories($id_game, $dummy->id)[0];
                    //try to put 3 armies more than the defender
                    $put_armies = $shot->army_defender - $shot->army_attacker + 3;
                    //armies available 
                    if (($left_armies = $max_armies - $dummy->num_armies) > 0) {
                        if ($left_armies < $put_armies) $put_armies = $left_armies;
                        $dummy->num_armies += $put_armies;
                        $this->db->query("update player_territory set armies = armies + ? where id = ?",array($put_armies, $shot->oid));
                    }
                }
            }
        } while($next_loop);
        foreach($dummies as $dummy) {
            $this->db->set($dummy)
                        ->where("id",$dummy->id)
                        ->update("players");
        }
        $this->db->set("dummy_placed",1)
                 ->where("id",$id_game)
                 ->update("games");

        $this->db->trans_commit();
    }
    
    protected function find_my_weaker_territories($id_game, $id_player) {
        //returns the territories of a player sorted by armies
        $sql = "select o.id_player as id_attacker, o.id as oid,  o.armies army_attacker, origin
                    from player_territory o
                        inner join v_attack_lines al on o.id_territory = al.origin
                        where o.id_game = $id_game 
                                and o.id_player = $id_player 
                            order by  o.armies asc";
        //echo $sql . "\n"; die();
        return $this->db->query($sql)->result();
    }
        

    protected function find_weak_territories($id_game, $id_player) {
        //returns a list of confining territories ordered by number of armies
        $sql = "select o.id_player as id_attacker, o.id as oid, d.id as did, o.armies army_attacker, origin, destination,
                    d.id_player as id_defender,d.armies as army_defender
                    from player_territory o
                        inner join v_attack_lines al on o.id_territory = al.origin
                        inner join player_territory d on al.destination = d.id_territory
                        where o.id_game = $id_game and d.id_game = $id_game 
                                and o.id_player = $id_player and d.id_player <> $id_player
                                and o.armies < 4
                            order by d.armies asc, o.armies asc";
        //echo $sql . "\n"; 
        return $this->db->query($sql)->result();
    }
    
    protected function find_easy_attacks($id_game, $id_player) {
        // returns a list of possible attacks ordered by the ratio between own and enemy armies
        $sql = "select o.id_player as id_attacker, o.id as oid, d.id as did, o.armies army_attacker, origin, destination,
                    d.id_player as id_defender,d.armies as army_defender,
                    (o.armies*1.0)/d.armies as ratio
                    from player_territory o
                        inner join v_attack_lines al on o.id_territory = al.origin
                        inner join player_territory d on al.destination = d.id_territory
                        where o.id_game = $id_game and d.id_game = $id_game 
                                and o.id_player = $id_player and d.id_player <> $id_player
                        order by 9 desc";
        return $this->db->query($sql)->result();
    }
    public function start_attack($id_player, $id_game) {
        //the player identifies an easy shot 
        $easy = $this->find_easy_attacks($id_game, $id_player)[0];
        // actually this is not true. The attack is made with 3 or more armies
        // but the player can throw only 3 dice. Unless we don't refresh every time
        //if ($easy->army_attacker > 4) $easy->army_attacker = 4;
        //if ($easy->army_defender > 3) $easy->army_defender = 3;
        //add some cosmetic info
        //name of territories
        $easy->origin_name = $this->db->select("tname")
                                        ->from("territories")
                                        ->where("id",$easy->origin)
                                        ->get()->result()[0]->tname;
        $easy->dest_name = $this->db->select("tname")
                                        ->from("territories")
                                        ->where("id",$easy->destination)
                                        ->get()->result()[0]->tname;
        //name of the players
        $easy->attacker_name = $this->db->select("pname")
                                        ->from("players")
                                        ->where("id",$easy->id_attacker)
                                        ->get()->result()[0]->pname;
        $easy->defender_name = $this->db->select("pname")
                                        ->from("players")
                                        ->where("id",$easy->id_defender)
                                        ->get()->result()[0]->pname;
        return $easy;
    }
    
    public function finalize_attack($oid, $att_id, $att_loss, $att_with, $did, $def_id, $def_loss) {
        //takes the result of an attack , remove lost armies and exchange of territories
        //oid / did are ID in the table player_territory
        //att_with = how many armies in the attack?
        //att/def_loss = how many armies were lost?
        //att/def_id = the id of the player
        $conquer = false;
        $defender_armies = $this->db->select("armies")->from("player_territory")
                                    ->where("id",$did)->get()->result()[0]->armies;
        if (($defender_armies - $def_loss) <= 0) $conquer = true;

        $this->db->trans_begin();
        $sql = "update player_territory set armies = armies - ? where id = ?";
        $this->db->query($sql,array($att_loss,$oid));
        $this->db->query($sql,array($def_loss,$did));
        
        $sql = "update players set num_armies = num_armies - ? where id = ?";
        $this->db->query($sql,array($att_loss,$att_id));
        $this->db->query($sql,array($def_loss,$def_id));
        
        // is the territory conquered?
        if ($conquer) {
            $this->db->query("update players set num_territories = num_territories + 1 where id = $att_id");
            $this->db->query("update players set num_territories = num_territories - 1 where id = $def_id");
            //move the victorious armies into the territory
            $this->db->query("update player_territory set id_player = ?, armies = ? where id = ?", 
                               array($att_id, $att_with-$att_loss, $did)  );
            $this->db->query("update player_territory set armies = armies - ? where id = ?",
                             array($att_with-$att_loss, $oid) );
        }
        $this->db->trans_commit();
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
    
    public function add_card($id_player, $card, $id_game) {
        // id_game added for security reasons since id_player is linked 1-1 to the game
        $sql = "update dummy_cards set $card = $card + 1 where id_player = $id_player and id_game = $id_game";
        $this->db->query($sql);
        return true;
    }

    public function use_cards($id_player, $card1, $card2, $card3) {
        $sql = "update dummy_cards set $card1 = $card1 - 1 where id_player = $id_player";
        $this->db->query($sql);
        $sql = "update dummy_cards set $card2 = $card2 - 1 where id_player = $id_player";
        $this->db->query($sql);
        $sql = "update dummy_cards set $card3 = $card3 - 1 where id_player = $id_player";
        $this->db->query($sql);
    }

    public function delete_game($id_game) {
        //this should be done with a cascade delete
        //anyway ...
        $this->db->trans_begin();
        $this->db->delete("players",array("id_game"=>$id_game));
        $this->db->delete("games",array("id"=>$id_game));
        $this->db->delete("player_territory",array("id_game"=>$id_game));
        $this->db->delete("dummy_cards",array("id_game"=>$id_game));
        $this->db->trans_commit();
    }

    public function change_color($player, $color) {
        $this->db->set("pcolor", $color)
                    ->where("id", $player)
                    ->update("players");
    }

    
}
