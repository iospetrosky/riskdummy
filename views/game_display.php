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


<?php
echo heading($game->gname,3);
foreach($game->players as $pl) {
    echo div(
        $pl->pname
    );
}
?>