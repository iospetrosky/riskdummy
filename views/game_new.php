<?php
$bu = config_item('base_url') . '/' . config_item('index_page');
$ajax = $bu . "/xxx/";
?>
<script type='text/javascript'>
var base_url = "<?php echo $bu; ?>"
var ajax_url = "<?php echo $ajax; ?>" 


function run_local() {


            
} // run_local    
    
</script>



<form name=new_game id=new_game method=post action=<?php echo $bu . "/game/newgame"; ?> >
<div class=form_line>
    <label for=gname>Game name</label><input name=gname id=gname size=40>
</div>
<!--
<div class=form_line>
    <label for=humans>Human players</label><input name=humans id=humans size=5>
</div>
<div class=form_line>
    <label for=dummies>Dummy players</label><input name=dummies id=dummies size=5>
</div>
-->
<div class=form_line>
    <label for=player_names">Player names</label><textarea name=player_names id=player_names rows=6 cols=20></textarea>
</div>
<div class=form_line>
    <label for=dummy_names>Dummy names</label><textarea name=dummy_names id=dummy_names rows=6 cols=20></textarea>
</div>
<div class=form_line>
    <button id=save_game ty_pe=button>Create game</button>
</div>


</form>