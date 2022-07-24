<?php

/*****PODSTAWY*STRONY*****/

function generateHead($title, $sheet = 0, $en = false){
	//czyli początek początku
	print "<!DOCTYPE html><html lang=\"";
	print ($en)? "en" : "pl";
	print '\"><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
	
	//konstrukcja tytułu
	print "<title>$title | WPWW</title>";
	
	//metatagi, czyli kolejna porcja bajdurzenia
	print "<meta name=author content=\"Wojciech Przybyła, Wesoły Wojownik\">";
	print "<meta name=description content=\"Lista projektów, jakimi zajmuje się WPWW, wraz z dokładnymi informacjami na ich temat.\">";
	print "<meta name=keywords content=\"Wojciech Przybyła, Wesoły Wojownik, fajna strona, Lightstream, WPWW, podkłady, muzyka\">";
	print "<meta name='viewport' content='width=device-width, initial-scale=1.0'>";
	print "<meta property='og:image' content='http://projects.wpww.pl/interface/thumbnail.jpg' />";
	print "<meta property='og:type' content='website' />";
	print "<meta property='og:url' content='http://projects.wpww.pl/' />";
	print "<meta property='og:title' content='$title | WPWW' />";
	print "<meta property='og:description' content='Lista projektów, jakimi zajmuje się WPWW, wraz z dokładnymi informacjami na ich temat.' />";
		
	//Google Fonts
	print "<link href=\"https://fonts.googleapis.com/css?family=Krona+One\" rel=\"stylesheet\">";
	print "<link href=\"https://fonts.googleapis.com/css?family=Raleway\" rel=\"stylesheet\">";
	
	//główny CSS
	print "<link rel=stylesheet type='text/css' href='/interface/style.css?".time()."'>";
	//opcjonalny CSS
	if($sheet != "0") print "<link rel=stylesheet type='text/css' href='/interface/".$sheet.".css?".time()."'>";
	
	//ikona
	print "<link rel=icon type='image/png' href='/interface/logo.png'>";
	
	//jQuery
	echo<<<CHUJ
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
CHUJ;
	
	//koniec głowy, początek ciała
	print "</head><body>";
	
	nakrzyczNaLudzi();
}

function generateBottom($script = 0){
	//copyright
	print "<footer>Meticulously designed and furiously crafted by<br><a href='http://wpww.pl'>WPWW himself</a> • &copy;2018 – ".date("Y")."<br>";
	print ($_GET['l'] == "en") ? "<a href='/'>Polski</a>" : "<a href='?l=en'>English</a>";
	print "</footer>";

	//opcjonalny skrypt
	if($script != "0") print "<script src='/interface/".$script.".js?".time()."'></script>";
	
	//trigger
	print "<script>";
	require("trigger.php");
	print "</script>";

	//koniec dokumentu - koniec ciała
	print "</body></html>";
	
	//jeśli podano połączenie z bazą w argumencie, wyłącz je
	global $conn;
	$conn->close();
}

/*****BUDULCE*****/

function nakrzyczNaLudzi(){ //jak nazwa wskazuje
	echo<<<CHUJ
<!--
───▄▀▀▀▄▄▄▄▄▄▄▀▀▀▄───
───█▒▒░░░░░░░░░▒▒█───
────█░░█░░░░░█░░█────
─▄▄──█░░░▀█▀░░░█──▄▄─
█░░█─▀▄░░░░░░░▄▀─█░░█
If you came here to judge my style of webpage design, be ready to use logical arguments.
Because if they are a pointless mantra, I do not need to heed your remarks.
-->
CHUJ;
}

function prepareName($name){ //odmieniacz imion
    //zakładam że x jest przedstawicielem płci
    $imie = (strpos($name, " ") == FALSE) ? $name : substr($name, 0, strpos($name, " "));
    $kobieta = (substr($imie, -1) == "a") ? true : false;
    
    //odmieniacz imion
    $imiewolacz = $imie; //failsafe
    if(preg_match("/a$/", $imie)) $imiewolacz = substr($imie, 0, -1)."o";
    if(!$kobieta){
        if(preg_match("/r$/", $imie)) $imiewolacz = $imie."ze";
            if(preg_match("/er$/", $imie)) $imiewolacz = substr($imie, 0, -2)."rze";
        if(preg_match("/d$/", $imie)) $imiewolacz = $imie."zie";
        if(preg_match("/t$/", $imie)) $imiewolacz = substr($imie, 0, -1)."cie";
            if(preg_match("/st$/", $imie)) $imiewolacz = substr($imie, 0, -2)."ście";
        if(preg_match("/[bzmnsfwp]$/", $imie)) $imiewolacz = $imie."ie";
        if(preg_match("/(l|j|h|k|g|sz|cz|rz)$/", $imie)) $imiewolacz = $imie."u";
        if(preg_match("/v$/", $imie)) $imiewolacz = substr($imie, 0, -1)."wie";
        if(preg_match("/x$/", $imie)) $imiewolacz = substr($imie, 0, -1)."ksie";
        if(preg_match("/(ei|ai)$/", $imie)) $imiewolacz = substr($imie, 0, -1)."ju";
        if(preg_match("/(ek|eg)$/", $imie)) $imiewolacz = substr($imie, 0, -2)."ku";
        if(preg_match("/niec$/", $imie)) $imiewolacz = substr($imie, 0, -4)."ńcu";
        if(preg_match("/yk$/", $imie)) $imiewolacz = $imie."u";
        if(preg_match("/ł$/", $imie)) $imiewolacz = substr($imie, 0, -1)."le";
            if(preg_match("/eł$/", $imie)) $imiewolacz = substr($imie, 0, -3)."le";
    }
    
    return ['imiewolacz' => $imiewolacz, 'kobieta' => $kobieta];
}

function kalendarz(){
    global $conn;
    
    echo "
    <h2>Kalendarz</h2>
    <div class='calendar'>";
    
    //zebranie terminów
    $q = "SELECT q.id, p.tytuł, q.deadline FROM p_questy q
            LEFT JOIN p_projekty p ON p.id = q.id
            WHERE q.data_2 IS NULL";
    $r = $conn->query($q) or die($q."<br>".$conn->error);
    while($a = $r->fetch_assoc()){
        $terminy[$a['deadline']] .= "<span title=\"".$a['tytuł']."\">".$a['id']."</span>";
    }
    $r->free_result;
    
    //przygotowanie kalendarza
    $currentday = date_create();
    $thelastday = date_create();
    date_add($thelastday, date_interval_create_from_date_string('14 days')); //x dni naprzód
    
    do{
        if(!isset($terminy[$currentday->format("Y-m-d")])) $terminy[$currentday->format("Y-m-d")] = "";
        
        print "<div style='background: ";
            if($currentday->format("N") > 5) print "#f6d366"; //czy to weekend
            else if($currentday->format("N") == 5) print "#555555"; //nie pracuję w piątki
            else print "#ffffff";
            print ($terminy[$currentday->format("Y-m-d")] == "") ? "33" : "99"; //zajęte dni jaśniejsze
        print "'><h4>".$currentday->format("j.m")."</h4>";
        print "<p>".$terminy[$currentday->format("Y-m-d")]."</p>";
        print "</div>";
        
        date_add($currentday, date_interval_create_from_date_string('1 day'));
    }while($currentday->format("Y-m-d") != $thelastday->format("Y-m-d"));
    echo "</div>";
}

/*****BAZA*DANYCH*****/

function giveMeTheCue($b){
	//łączenie z bazą danych
	global $conn;
	$conn = new mysqli("localhost", "p497635_archmage", "viper400X", "p497635_$b");
	
	//błędy
	if($conn->connect_error) echo "Nie można się połączyć z bazą: ".$conn->connect_error;
	
	//charset, bo świra dostaje
	$conn->set_charset("utf8");
}
?>