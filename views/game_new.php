<?php
$bu = config_item('base_url')  . config_item('index_page');
$ajax = $bu . "/xxx/";
?>
<script type='text/javascript'>
//var ajax_url = "<?php echo $ajax; ?>" 


function run_local() {


            
} // run_local    
    
</script>



<form name=new_game id=new_game method=post action=<?php echo $bu . "/game/newgame"; ?> >
<div class=form_line>
    <label for=gname>Game name</label><input name=gname id=gname size=40>
</div>
<div class=form_line>
    <label for=player_names">Player names</label><textarea name=player_names id=player_names rows=6 cols=20></textarea>
</div>
<div class=form_line>
    <label for=dummy_names>Dummy names</label><textarea name=dummy_names id=dummy_names rows=6 cols=20></textarea>
</div>
<div class=form_line>
    <label for=autoassign>Auto assign territories</label><input type=checkbox name=autoassign id=autoassign value="Y">
</div>
<div class=form_line>
    <button id=save_game>Create game</button>
</div>
</form>

<hr>

<form name=join_game id=join_game method=get action=<?php echo $bu . "/game/joingame"; ?> >
<div class=form_line>
    <label for=id_game">Game ID</label><input name=game_id id=game_id size=12></textarea>
</div>

<div class=form_line>
    <button id=save_game>Join game</button>
</div>
</form>