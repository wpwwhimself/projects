<?php
session_start();
$z = $_SERVER['DOCUMENT_ROOT']; $z .= "/cue.php"; require($z); //podpięcie generatora
giveMeTheCue(0); //łączenie z bazą danych

generateHead("Ankieta końcowa");
#####

if(isset($_POST['s_sub'])){
    foreach(['s_film', 's_nuty'] as $x){
        $_POST[$x] = ($_POST[$x] == "on") ? 1:0;
    }
    if($_POST['s_insertflag']){ //dodaj ankietę
        $q = "INSERT INTO p_questy_stats VALUES
                ('".$_POST['s_quest']."',
                '".$_POST['s_czas'].":00',
                ".$_POST['s_sciezki'].",
                ".$_POST['s_zywe'].",
                '".$_POST['s_srodowisko']."',
                '".$_POST['s_gatunek']."',
                ".$_POST['s_film'].",
                ".$_POST['s_nuty']."
                )";
    }else{ //edytuj ankietę
        $q = "UPDATE p_questy_stats SET
                czas_wykonania = '".$_POST['s_czas'].":00',
                ile_sciezek = ".$_POST['s_sciezki'].",
                ile_zywych_ins = ".$_POST['s_zywe'].",
                srodowisko = '".$_POST['s_srodowisko']."',
                gatunek = '".$_POST['s_gatunek']."',
                film = ".$_POST['s_film'].",
                nuty = ".$_POST['s_nuty']."
                WHERE quest = '".$_POST['s_quest']."'";
    }
    $conn->query($q) or die($q."<br>".$conn->error);
    $heraldictext = "<span class='green-tick'>✓</span>Zaktualizowano ankietę";
}

//jeśli już są jakieś dane w ankiecie, to pokaż
$q = "SELECT * FROM p_questy_stats WHERE quest = '".$_GET['id']."'";
$r = $conn->query($q) or die($conn->error);
$ankieta = $r->fetch_assoc();
$r->free_result;

?>
<style>
    .flexright div{
        display: flex; flex-direction: column; align-items: center;
    }
    input, select{
        background: none;
        border-radius: 1em;
        font-family: "Raleway"; font-size: 12px; text-align: center;
    }
    input[type="number"]{ width: 80px; };
</style>
<h1>Ankieta końcowa</h1>
<h2>dla projektu <?php echo $_GET['id']; ?></h2>
<p><?php echo $heraldictext; ?></p>
<form method='post'>
    <div>
        <label>Czas wykonania</label>
        <input type=time name="s_czas" value="<?php print $ankieta['czas_wykonania']; ?>"></input>
    </div>
    <div>
        <label>Ile ścieżek składa się na podkład?</label>
        <input type=number name="s_sciezki" value=<?php print $ankieta['ile_sciezek']; ?>></input>
    </div>
    <div>
        <label>Ile żywych instrumentów użyto?</label>
        <input type=number name="s_zywe" value=<?php print $ankieta['ile_zywych_ins']; ?>></input>
    </div>
    <div>
        <label>Środowisko nagrywania</label>
        <select name="s_srodowisko">
            <?php foreach(['pełne' => "wszystkie instrumenty",
                            'ograniczone' => "część instrumentów",
                            'spartańskie' => "tylko klawiatura",
                            "mieszane" => "roaming"] as $x => $y){
                echo "<option value='$x'";
                    if($ankieta['srodowisko'] == $x) print " selected";
                echo ">$x ($y)</option>";
            } ?>
        </select>
    </div>
    <div>
        <label>Gatunek utworu</label>
        <select name="s_gatunek">
            <?php foreach(["szybki quest", "songwriter", "orkiestra", "gitary", "elektro", "gitary+orkiestra", "gitary+elektro", "elektro+orkiestra", "wszystko", "biesiada",  "jazz", 'same nuty'] as $x){
                echo "<option value='$x'";
                    if($ankieta['gatunek'] == $x) print " selected";
                echo ">$x</option>";
            } ?>
        </select>
    </div>
    <div>
        <label>Czy projekt zawiera filmy?</label>
        <input type='checkbox' name='s_film'<?php if($ankieta['film']) echo " checked"; ?>></input>
    </div>
    <div>
        <label>Czy projekt zawiera nuty?</label>
        <input type='checkbox' name='s_nuty'<?php if($ankieta['nuty']) echo " checked"; ?>></input>
    </div>
    <div>
        <input type='hidden' name="s_quest" value="<?php echo $_GET['id']; ?>"></input>
        <input type='hidden' name="s_insertflag" value=<?php echo ($ankieta == ""); ?>></input>
        <input type='submit' name='s_sub' value="Zaktualizuj"></input>
    </div>
</form>

<p>Wróć do <b><a href="http://projects.wpww.pl/safe?p=<?php echo $_GET['id']; ?>">sejfu projektu</a></b> albo <b><a href="http://projects.wpww.pl/archmage.php?c=clients">szpicy</a></b></p>

<?php
#####
generateBottom("main");
?>