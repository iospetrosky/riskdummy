<?php
$bu = config_item('base_url') . '/' . config_item('index_page');
$ajax = $bu . "/xxx/";
?>
<script type='text/javascript'>
var base_url = "<?php echo $bu; ?>"
var ajax_url = "<?php echo $ajax; ?>" 


function run_local() {

    $(".terr_button").click(function() {
        var id = $(this).attr("ID").split("_")[0]
        $.get(base_url + "/map/territoryinfo/" + id, function(data) {
            data = JSON.parse(data)
            $("#terr_id").val(data.id)
            $("#armies").val(data.armies)
            $("#terr_form").css("visibility","")
            
        })
    })
    $("#terr_form_hide").click(function() {
        $("#terr_form").css("visibility","hidden")
    })
            
} // run_local    
    
</script>

<?php
$line = "";
$last_y = 1;
$last_x = 0;
$content = "";
foreach($cells as $cell) {
    if ($cell->map_y != $last_y) {
        echo div($line);
        $line = "";
        $last_y = $cell->map_y;
        $last_x = 0;
    }
    if($cell->map_x > $last_x+1) {
        //ocean cells
        for($x=$last_x+1; $x<$cell->map_x; $x++) {
            //$content = sprintf("%s<br>Y:%s X:%s","ocean", $cell->map_y, $x);
            $line .= div("",array("class"=>"map_cell ocean"));
        }
    }
    //$content = sprintf("%s<br>Y:%s X:%s",$cell->tname, $cell->map_y, $cell->map_x);
    $content = sprintf("<span class=terr_button id=%d_cell>%s</span><br>%s - %d",$cell->id,$cell->tname,$cell->pname,$cell->armies);
    $line .= div($content,array("class"=>"map_cell continent_{$cell->id_continent}","id"=>$cell->id . "_terr"));
    $last_x = $cell->map_x;
}
echo div($line);

?>  
<div class=the_form id=terr_form style="visibility:hidden">
<div class=the_line>
    <label for=terr_id>ID</label><input id="terr_id" size=4 readonly=readonly>
</div>
<div class=the_line>
    <label for=player_id>Player</label><input id="player_id" size=4 >
</div>
<div class=the_line>
    <label for=armies>Armies</label><input id="armies" size=4 >
</div>
<div style="padding:4px">
<button type=button id=terr_form_hide>Hide</button>
</div>
</div>