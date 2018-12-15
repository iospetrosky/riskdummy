<?php
$bu = config_item('base_url') . '/' . config_item('index_page');
$ajax = $bu . "/xxx/";
?>
<script type='text/javascript'>
var base_url = "<?php echo $bu; ?>"
var ajax_url = "<?php echo $ajax; ?>" 

function get_this_id(item) {
    // parses the ID property of a jquery item and returns
    // the ID which is always the first before the underscore
    return item.attr("ID").split("_")[0]
}


function run_local() {
    $.ajaxSetup({ cache: false });

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
        //var id = $(this).attr("ID").split("_")[0]
        $(this).addClass("row_edited")
    })
    $(".btn_attack").click(function(e) {
        id = $(this).attr("ID").split("_")[0]
        $.get(base_url + "/game/startattack/"+id, function(data) {
            $("#ACTIONS").html(data)
        } )
    })
    $(".btn_reinforce").click(function(e) {
        id = $(this).attr("ID").split("_")[0]
        $.get(base_url + "/game/reinforce/"+id, function(data) {
            data = JSON.parse(data)
            $("#" + data.id + "_num_armies").html(data.max_armies)
            var testo = "<h3>" + data.message + "</h3>"
            testo += data.army_bonus.standard + "<br>";
            testo += data.army_bonus.continents + "<br>";
            testo += data.army_bonus.cards + "<br>";
            
            for(var i=0; i<data.places.length; i++) {
                testo += data.places[i] + "<br>"
            }
            $("#ACTIONS").html(testo)
        } )
    })
    $("#frm_assign").click(function(){
        $.post(base_url + "/game/addcard", $("form#frm_add_card").serialize(), function(data) {
            $("#PICK").css("visibility","hidden")
            $("#ACTIONS").html(data)
        })
    })
    $(".btn_pickcard").click(function() {
        $("#ACTIONS").html("")
        $("#PICK").css("visibility","visible")
        var id = get_this_id($(this))
        //$("#frm_player").val(id)
        $("input[name=frm_player]").val(id)
    })
    $("#cmd_dummyfirst").click(function(e) {
        //id = $(this).attr("ID").split("_")[0]
        $.get(base_url + "/game/dummyfirst", function(data) {
            // if successful the server redirects to the map
            $("#ACTIONS").html(data)
        } )
    })
    $("#ACTIONS").on("click", ".dice_roll", function(e) {
        $.get(base_url + "/game/roll/"+$(this).attr("ID"), function(data) {
            data = JSON.parse(data)
            $("#" + data.id ).html(data.roll)
        } )
    }).on("click","input",function(e){
        //this is for the dynamically created items
        $(this).select()
    }).on("click","#cmd_reset_dice",function(e) {
        $(".dice_roll").each(function() {
            $(this).html("0")
        })
    });
    $("input").on("click",function(e){
        //this is for the others
        $(this).select()
    } )
} // run_local    
    
</script>


<?php
echo heading($game->gname,3);

echo div(
    div("Player name",array("class"=>"head_display_cell","style"=>"width:150px")) .
    div("",array("class"=>"head_display_cell","style"=>"width:20px")) .
    div("Color",array("class"=>"head_display_cell","style"=>"width:90px")) .
    div("Ter",array("class"=>"head_display_cell","style"=>"width:50px")) .
    div("Army",array("class"=>"head_display_cell","style"=>"width:50px")) .
    div("&nbsp;",array("class"=>"head_display_cell","style"=>"width:200px"))
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
        div($pl->num_armies,array("class"=>"row_edit_cell","style"=>"width:50px","id"=>$pl->id . "_num_armies")) .
        ($pl->ptype == 'D'?div(button("Reinforce",array("id" => $pl->id . "_btn_reinforce", "class" => "btn_reinforce")) . 
                               button("Attack",array("id" => $pl->id . "_btn_attack", "class" => "btn_attack")) .
                               button("Pick card",array("id" => $pl->id . "_btn_pick", "class" => "btn_pickcard")),
                                array("class"=>"row_edit_cell","style"=>"width:200px")):"") 
        
    );
}

$dummy_first = "";
if ($game->dummy_placed == 0) {
    $dummy_first = div(button("First turn dummies",array("id"=>"cmd_dummyfirst")),
            array("class"=>"row_edit_cell","style"=>"width:50px"));
} 
echo div(
    div(button("Quit game",array("id"=>"cmd_delete") ),
            array("class"=>"row_edit_cell","style"=>"width:150px")) .
    div("",array("class"=>"row_edit_cell","style"=>"width:20px")) .
    div(button("Save colors",array("id"=>"cmd_savecolors")), 
            array("class"=>"row_edit_cell","style"=>"width:90px")) .
    $dummy_first .
    div("",array("class"=>"row_edit_cell","style"=>"width:50px")) .
    div("",array("class"=>"row_edit_cell","style"=>"width:200px")) 
);
?>
<div id="ACTIONS">

</div>

<div id="PICK" style="visibility:hidden">
    <div class=the_line>
      <form id="frm_add_card">
        <input type=hidden id=frm_player name=frm_player value=''>
        <label for=frm_cardtype>Pick a card</label>
        <?php        echo form_dropdown("frm_cardtype", array("infantry"=>"Infantry","artillery"=>"Artillery",
                                                "cavalry"=>"Cavalry","jolly"=>"Jolly"),
                                        0 ,array("id"=>"frm_cardtype"));        ?>
        <button type=button id=frm_assign>Assign</button>
      </form>
    </div>
</div>
