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
            $("#player_id").val(data.id_player)
            $("#terr_form").css("visibility","")
            
        })
    })
    $("#terr_form_hide").click(function() {
        $("#terr_form").css("visibility","hidden")
    })
    $("#terr_form_save").click(function(e) {
        saveterritory()
    })
    $("#armies").keyup(function(e) {
        if (e.keyCode == 13) saveterritory()
    })
    $(".army_plus_button").click(function() {
        var id = $(this).attr("ID").split("_")[0]
        $.get(base_url + "/map/army/P/" + id, function(data) {
            data = JSON.parse(data)
            $("#"+data.id+"_celldata").html("<b>" + data.pname + "</b> - " + data.armies)
        })
    })
    $(".army_minus_button").click(function() {
        var id = $(this).attr("ID").split("_")[0]
        $.get(base_url + "/map/army/M/" + id, function(data) {
            data = JSON.parse(data)
            $("#"+data.id+"_celldata").html("<b>" + data.pname + "</b> - " + data.armies)
        })
    })
            
} // run_local    

function saveterritory() {
    $.post(base_url + "/map/saveterritory", 
                {"id": $("#terr_id").val(), "player": $("#player_id").val(), "armies": $("#armies").val() },
                 function(data) {
                    $("#terr_form").css("visibility","hidden")
                    data = JSON.parse(data)
                    $("#"+data.id+"_celldata").html("<b>" + data.pname + "</b> - " + data.armies)
                 } )
}

    
</script>
<div style="" id="map_border">
<?php
$line = "";
$last_y = 1;
$last_x = 0;
$content = "";
foreach($cells as $cell) {
    if ($cell->map_y != $last_y) {
        if ($last_x < 10) {
            // there are be some ocean cells on the right
            for ($x=$last_x+1; $x<=10; $x++) {
                $line .= div("",array("class"=>"map_cell ocean"));
            }
        }
        
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
    $content = sprintf("<span class=terr_button id=%d_cell>%s</span><br><span id=%d_celldata style='color:%s'><b>%s</b> - %d</span>",
                            $cell->id,
                            $cell->tname,
                            $cell->id,
                            $cell->pcolor,
                            $cell->pname,
                            $cell->armies);
    $content .= button("+",array("class"=>"army_plus_button","id"=>$cell->id . "_plus")) .
                button("-",array("class"=>"army_minus_button","id"=>$cell->id . "_minus"));
    $line .= div($content,array("class"=>"map_cell continent_{$cell->id_continent}","id"=>$cell->id . "_terr"));
    $last_x = $cell->map_x;
}
if ($last_x < 10) {
    // there are be some ocean cells on the right
    for ($x=$last_x+1; $x<=10; $x++) {
        $line .= div("",array("class"=>"map_cell ocean"));
    }
}
echo div($line);
?>  
</div>
<div class=the_form id=terr_form style="visibility:hidden">
<div class=the_line>
    <label for=terr_id>ID</label><input id="terr_id" size=4 readonly=readonly>
</div>
<div class=the_line>
    <label for=player_id>Player</label>
    <?php
        echo form_dropdown("player_id", $players,0,array("id"=>"player_id"));
        //print_r($players);
    ?>
</div>
<div class=the_line>
    <label for=armies>Armies</label><input id="armies" size=4 >
</div>
<div style="padding:4px">
<button type=button id=terr_form_hide>Hide</button><button type=button id=terr_form_save>Save</button>
</div>
</div>