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
    $content = sprintf("<span class=clickable id=cell_%d>%s</span><br>%s - %d",$cell->id,$cell->tname,$cell->pname,$cell->armies);
    $line .= div($content,array("class"=>"map_cell continent_{$cell->id_continent}","id"=>"terr_" . $cell->id));
    $last_x = $cell->map_x;
}
echo div($line);

?>  