<?php
$bu = config_item('base_url') . '/' . config_item('index_page');
$ajax = $bu . "/xxx/";
?>
<script type='text/javascript'>
var base_url = "<?php echo $bu; ?>"
var ajax_url = "<?php echo $ajax; ?>" 


function run_local() {
    $("#cmd_delete").mouseup(function(e) {
        window.location.href = base_url + "/game/delgame"
    })
    $("#cmd_savecolors").mouseup(function(e) {
        var colors = [];
        var players = [];
        $(".color_field").each(function(e) {
            colors.push ($(this).val())
            players.push ($(this).attr("ID").split("_")[1])
            $(this).removeClass("row_edited")
        })
        $.post(base_url + "/game/updcolors", 
                {"colors[]":colors, "players[]":players},
                function(data) {
                    alert(data)
                })
        
    })
    $(".editable").change(function(e) {
        //var id = $(this).attr("ID").split("_")[1]
        $(this).addClass("row_edited")
    })

            
} // run_local    
    
</script>


<?php
echo heading($game->gname,3);

echo div(
    div("Player name",array("class"=>"head_display_cell","style"=>"width:150px")) .
    div("",array("class"=>"head_display_cell","style"=>"width:20px")) .
    div("Color",array("class"=>"head_display_cell","style"=>"width:90px")) .
    div("Ter",array("class"=>"head_display_cell","style"=>"width:50px")) .
    div("Army",array("class"=>"head_display_cell","style"=>"width:50px")) 
);

foreach($game->players as $pl) {
    $data = array(
        "id" => "color_" . $pl->id,
        "value" => $pl->pcolor,
        "style" => "width:80px",
        "class" => "editable color_field"
    );

    echo div(
        div($pl->pname,array("class"=>"row_edit_cell","style"=>"width:150px")) .
        div($pl->ptype,array("class"=>"row_edit_cell","style"=>"width:20px")) .
        div(form_input($data), array("class"=>"row_edit_cell","style"=>"width:90px")) .
        div($pl->num_territories,array("class"=>"row_edit_cell","style"=>"width:50px")) .
        div($pl->num_armies,array("class"=>"row_edit_cell","style"=>"width:50px")) 
    );
}

echo div(
    div(button("Quit game",array("id"=>"cmd_delete") ),
            array("class"=>"row_edit_cell","style"=>"width:150px")) .
    div("",array("class"=>"row_edit_cell","style"=>"width:20px")) .
    div(button("Save colors",array("id"=>"cmd_savecolors")), 
            array("class"=>"row_edit_cell","style"=>"width:90px")) .
    div("",array("class"=>"row_edit_cell","style"=>"width:50px")) .
    div("",array("class"=>"row_edit_cell","style"=>"width:50px")) 
);
?>