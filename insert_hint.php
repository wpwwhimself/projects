<?php
/* podpowiedzi duplikatów tytułów do wyceniacza */
require("cue.php");
giveMeTheCue(0);

$q = "SELECT p.tytuł, p.album, p.id, k.nazwisko
        FROM p_projekty p LEFT
        JOIN p_klienci k ON p.inspiracja = k.id
        WHERE p.id like 'Z%%' or p.id like 'K%%'
        ORDER BY p.tytuł ASC";
$r = $conn->query($q) or die($conn->error);
while($a = $r->fetch_array()){
    //sprawdzacz duplikatów tytułów
    $i = array_search($a['tytuł'], $tytuły);
    if($i !== FALSE && $artysty[$i] == $a['album']){
        $idki[$i] .= ", ".$a['id'];
        $nazwiska[$i] .= ", ".$a['nazwisko'];
    }else{
        $tytuły[] = $a['tytuł'];
        $artysty[] = $a['album'];
        $idki[] = $a['id'];
        $nazwiska[] = $a['nazwisko'];
    }
}
$r->free_result();

$q = $_REQUEST["q"];

$hint = "";

if ($q !== "") {
    $q = strtolower($q);
    $len = strlen($q);
    for($i = 0; $i < count($tytuły); $i++){
        if (@preg_match("/".$q."/i", $tytuły[$i])) {
            if ($hint !== "") $hint .= " ♦ ";
            $hint .= $tytuły[$i];
            if($artysty[$i] != null) $hint .= " – ".$artysty[$i];
            $hint .= " (".$idki[$i]." dla: ".$nazwiska[$i].")";
        }
    }
}

echo $hint === "" ? "nie wiem..." : $hint;

?>