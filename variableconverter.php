<script>
//język
<?php $lang = ($_GET['l'] == "en") ? true : false; ?>
var lang = <?php echo ($lang) ? "true" : "false"; ?>;

//betoniarka zmiennych
var projekty = new Array();
<?php

//tłumaczenie klientów - wprowadzenie
$q = "SELECT id, nazwisko FROM p_klienci";
$r = $conn->query($q) or die($conn->error);
while($a = $r->fetch_assoc()){
    $client[$a['id']] = $a['nazwisko'];
}
$r->free_result;

//informacje na temat wszystkich projektów
$q = "SELECT * FROM p_projekty";
$r = $conn->query($q) or die($q.$conn->error);
while($a = $r->fetch_assoc()){
    //brak nazwy to kreska
    if($a['nazwa'] === null) $a['nazwa'] = "—";
    
    //tłumaczenie statusu
    switch($a['status']){
        case 0: $a['status'] = "done"; break;
        case 1: $a['status'] = "undone"; break;
        case 2: $a['status'] = "scrapped"; break;
        case 3: $a['status'] = "fused";
    }
    
    //tłumaczenie klientów
    if(is_numeric($a['inspiracja'])){
        $a['inspiracja'] = ($client[$a['inspiracja']] != null) ? $client[$a['inspiracja']] : "–x–";
    }
    
    
    //czego nie widzi incognito
    if($_SESSION['whoami'] == 'incognito'){
        //nazwiska
        setlocale(LC_CTYPE, "pl_PL");
        $a['inspiracja'] = str_replace("/", ", ", $a['inspiracja']); //gdyby w nazwie były slashe
        $names = explode(" ", $a['inspiracja']);
        for($i=0;$i<count($names);$i++){
            if(ctype_upper(substr($names[$i], 0, 1))){
                $initial = substr($names[$i], 0, 1);
                if(substr($names[$i], -1) == ",") $names[$i] = $initial.".,"; //jeśli przecinek, to zachowaj go
                    else $names[$i] = $initial.".";
            }
        }
        $a['inspiracja'] = str_replace(". ", ".", implode(" ", $names));
        
        //repozytoria
        if(strstr($a['link'], "drive") || strstr($a['link'], "projects")) $a['link'] = null;
    }
    
    //linki do brzoskwini na albumy
    if(strstr($a['link'],"brzoskwinia")){
        $tytul1 = str_replace("%27", "''", str_replace("%20", " ", substr(strstr($a['link'], "="), 1)));
        $q1 = "SELECT id, album FROM p_projekty WHERE tytuł = '$tytul1'";
        $r1 = $conn->query($q1) or die($conn->error);
        $a1 = $r1->fetch_assoc();
        $r1->free_result();
        //jeśli nie ma albumu, załóż, że cover lub brak albumu
        if(!$a1['album']){
            $q1 = "SELECT cover FROM s_cnb WHERE id = '".$a1['id']."'";
            $r1 = $conn->query($q1) or die($conn->error);
            $a1 = $r1->fetch_assoc();
            $r1->free_result();
            $a1['album'] = ($a1['cover']) ? "Covery" : "brak albumu";
        }
        
        $a['link'] = "<a href='".$a['link']."' target='_blank' class='albumcontainer drop-shadow'><img src='http://brzoskwinia.wpww.pl/library/".$a1['album'].".png' alt='Brzoskwinia'><div class='hoverplay interactive'>&#8658;</div></a>";
    }
?>
projekty['<? echo str_replace("'", "\'", $a['id']); ?>'] = new Array('nazwa', 'status', 'status_opis', 'tytul', 'album', 'inspiracja', 'data_in', 'data_out', 'link');
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['nazwa'] = "<? echo $a['nazwa']; ?>";
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['status'] = "<? echo $a['status']; ?>";
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['status_opis'] = "<? echo $a['status_opis']; ?>";
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['tytul'] = "<? echo ($a['tytuł'] == null)? "—" : $a['tytuł']; ?>";
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['album'] = "<? echo ($a['album'] == null)? "—" : $a['album']; ?>";
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['inspiracja'] = "<? echo ($a['inspiracja'] == null)? "—" : $a['inspiracja']; ?>";
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['data_in'] = "<? echo ($a['data_in'] == null)? "—" : date_format(date_create($a['data_in']),"j.m.Y"); ?>";
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['data_out'] = "<? echo ($a['data_out'] == null)? "—" : date_format(date_create($a['data_out']),"j.m.Y"); ?>";
projekty['<? echo str_replace("'", "\'", $a['id']); ?>']['link'] = "<? echo ($a['link'] == null)? "—" : str_replace("\"", "\\\"", $a['link']); ?>";
<?php
}
$r->free_result();
?>
</script>