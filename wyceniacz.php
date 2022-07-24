<?php
session_start();
$z = $_SERVER['DOCUMENT_ROOT']; $z .= "/cue.php"; require($z); //podpięcie generatora
giveMeTheCue(0); //łączenie z bazą danych

generateHead("Wyceniacz");
#####

$odbiorca = prepareName($_GET['name'])

?>
<style>
    .flexright div{
        display: flex; flex-direction: column; align-items: center;
    }
    input, select{ 
        width: 120px; 
        background: none;
        border-radius: 1em;
        font-family: "Raleway"; font-size: 15px; text-align: center;
    }
</style>
<h1>Wyceniacz</h1>

<h2>Modyfikacje</h2>
<div class=flexright>
    <div>
        <label>Odmienione imię</label>
        <input type=text onchange="w_name(this.value)"<?php if(isset($_GET['name'])) print " value=".$odbiorca['imiewolacz']; ?>></input>
    </div>
    <div>
        <label>Kobieta?</label>
        <input type=checkbox id=kobieta onchange="w_gender(this.value)"<?php if($odbiorca['kobieta']) print " checked=checked"; ?>></input>
    </div>
    <div>
        <label>Które zlecenie?</label>
        <select onchange="w_again(this.value)" id="veteran">
            <option value="0">pierwsze</option>
            <option value="1"<?php if(isset($_GET['name']) && !isset($_GET['vet'])) print " selected"; ?>>kolejne</option>
            <option value="2"<?php if(isset($_GET['name']) && isset($_GET['vet'])) print " selected"; ?>>stały klient</option>
        </select>
    </div>
    <div>
        <label>Tester tytułu</label>
        <script>
        function showHint(str) {
            if (str.length == 0) { 
                document.querySelector(".hint").innerHTML = "";
                return;
            } else {
                var xmlhttp = new XMLHttpRequest();
                xmlhttp.onreadystatechange = function() {
                    if (this.readyState == 4 && this.status == 200) {
                        document.querySelector(".hint").innerHTML = this.responseText;
                    }
                };
                xmlhttp.open("GET", "insert_hint.php?q=" + str, true);
                xmlhttp.send();
            }
        }
        </script>
    	<input type=text name="duplikat" onkeyup="showHint(this.value)" />
    </div>
    <div>
        <label>Kilka projektów</label>
        <input type=checkbox id="multi" onchange="w_multi(this.value)"></input>
    </div>
    <div>
        <label>Linki</label>
        <input type=checkbox id="w_links" onchange="w_links(this.value)"></input>
    </div>
    <div>
        <label<?php if($_GET['vet']) echo " style='font-weight: bold; color: #f6d366; text-shadow: 0 0 10px black;'"; ?>>Cena</label>
        <input type=number id=pricetag onchange="w_price(this.value)"<?php if($_GET['vet']) echo " style='box-shadow: 0 0 20px #f6d366'"; ?>></input>
    </div>
    <div>
        <label>Za</label>
        <input type=text onchange="w_priceJustif(this.value)"></input>
    </div>
    <div>
        <label>Czas przyg.</label>
        <input type=text onchange="w_time(this.value)"></input>
    </div>
    <div>
        <label>Generalnie to</label>
        <select onchange="w_other(this.value)">
            <option value=0>nic nie robię</option>
            <option value=1>studiuję</option>
            <option value=2>mam dużo</option>
            <option value=3 selected>oba</option>
        </select>
    </div>
</div>
<p class="hint" style="text-align: center; color: #333; font-style: italic;"></p>

<?php kalendarz(); ?>

<h3>Wycena intro</h3>
<hr>
<p><span id=gender1>Szanowny Panie</span> <span id=name>XYZ</span>,</p>
<p>uprzejmie dziękuję za <span id="again"></span>zainteresowanie moimi usługami.</p>
<span id=linkIG style="display: none;">
    <p>Wycenę i przygotowanie podkład<span id="multiple1">u</span> będę opierał na <span id=multilinks>tej wersji</span> nagrania:</p>
    <ul>
        <li>//tu wklej linki//</li>
    </ul>
    <p>Jeśli chce <span id=gender2>Pan</span> wprowadzić tu zmiany, proszę o informację.</p>
</span>
<table style="max-width: 80vw; white-space: normal;">
    <tr>
        <th>Wycena</th>
        <th>Czas przygotowania</th>
        <th>Płatność</th>
        <th>Odbiór plików</th>
    </tr>
    <tr>
        <td>
            <b id="price">XYZ zł</b><br>
            (na podstawie wyceny za <span id=pricejustif>podkład XYZ</span><span id="againpricing"></span>)
        </td>
        <td>
            <b id="time">do końca najbliższego weekendu</b><br>
            pod warunkiem otrzymania przeze mnie informacji o zaakceptowaniu przez <span id=gender3>Pana</span> powyższej ceny<br>
            Faktyczny czas wykonania może się zmienić (często na lepsze) w zależności od budżetu czasowego<b id="otherThingsIDo">, choć generalnie studiuję i mam obecnie wiele innych zamówień</b>.
        </td>
        <td>
            przelew na konto (nr: <b>53 1090 1607 0000 0001 1633 2919</b>, proszę w tytule <b>o wzmiankę, jakiego projektu wpłata dotyczy</b>)<br>
            lub przelew BLIKiem na numer telefonu
        </td>
        <td>
            <span id="paynow">Podgląd materiałów będzie możliwy z poziomu mojej strony internetowej, natomiast możliwość ich pobrania zostanie przyznana po otrzymaniu przeze mnie wpłaty.</span>
            <span id="paylater">Podgląd i linki do materiałów będą dostępne z poziomu mojej strony internetowej.</span>
        </td>
    </tr>
</table>
<p>Proszę o wiadomość, czy odpowiadają <span id=gender5>Panu</span> te warunki. <b>Po otrzymaniu pozytywnej odpowiedzi</b> przystąpię do realizacji projekt<span id="multiple2">u</span>.</p>
<p>Oczekuję na odpowiedź i pozdrawiam serdecznie,</p>
<hr>

<h3>Podziękowanie na koniec</h3>

<hr>
<p><span id=gender4>Szanowny Panie</span> <span id=name2>XYZ</span>,</p>
<p>cieszę się, że jest <span id=gender22>Pan</span> zadowolon<span id=gender24>y</span> ^^</p>
<p>Jak tylko otrzymam przelew, wyślę informację o udostępnieniu możliwości pobrania plików z katalogu.</p>
<p><a href='https://www.facebook.com/wpwwCytryna'>Gorąco zachęcam do polajkowania mojego fanpage’a na facebooku</a> <a href='https://www.instagram.com/wpwwhimself/'>oraz do obserwowania mojego instagrama</a>, a także promocji moich usług wśród znajomych. Polecam się także do dalszych zleceń.</p>
<p>Pozdrawiam serdecznie,</p>
<hr>

<a href='mailto:<?php echo $a['kontakt']; ?>?subject=[WPWW] ws. utworu – <?php echo $a['tytuł']; ?>'>Nieusatysfakcjonowany? <b>Napisz maila ręcznie!</b></a>

<p>Wróć do <b><a href="http://projects.wpww.pl/archmage.php?c=clients">szpicy</a></b> albo <b><a href="http://projects.wpww.pl">listy projektów</a></b></p>

<script>
    function w_name(newName){
        document.getElementById("name").innerHTML = newName;
        document.getElementById("name2").innerHTML = newName;
    }
    
    function w_gender(yn){
        yn = document.querySelector('#kobieta').checked;
        document.getElementById("gender1").innerHTML = (yn) ? "Szanowna Pani" : "Szanowny Panie";
            document.getElementById("gender4").innerHTML = (yn) ? "Szanowna Pani" : "Szanowny Panie";
        document.getElementById("gender2").innerHTML = (yn) ? "Pani" : "Pan";
            document.getElementById("gender22").innerHTML = (yn) ? "Pani" : "Pan";
            document.getElementById("gender24").innerHTML = (yn) ? "a" : "y";
        document.getElementById("gender3").innerHTML = (yn) ? "Panią" : "Pana";
        document.getElementById("gender5").innerHTML = (yn) ? "Pani" : "Panu";
    }
    
    function w_again(yn){
        document.getElementById("again").innerHTML = (yn > 0) ? "ponowne " : "";
        document.getElementById("againpricing").innerHTML = (yn == 2) ? " dla stałego klienta" : "";
        w_price(document.getElementById("pricetag").value);
        w_gender(document.getElementById("kobieta").checked);
    }
    <?php if(isset($_GET['name'])){ ?> 
    w_name("<?php echo $odbiorca['imiewolacz']; ?>");
    w_again(1);
    <?php } ?>
    <?php if($_GET['vet']) { ?> w_again(2); <?php } ?>
    
    function w_multi(yn){
        yn = document.querySelector('#multi').checked;
        for(var i = 1; i <= 2; i++){ document.getElementById("multiple"+i).innerHTML = (yn) ? "ów" : "u"; }
        //document.getElementById("multiple4").innerHTML = (yn) ? "ami" : "em";
        document.getElementById("multilinks").innerHTML = (yn) ? "tych wersjach" : "tej wersji";
    }
    
    function w_links(yn){
        yn = document.querySelector('#w_links').checked;
        document.getElementById("linkIG").style.display = (yn) ? "block" : "none";
    }
    function w_price(price){
        var veteran = document.getElementById("veteran").value;
        document.getElementById("price").innerHTML = price+" zł";
        if(veteran < 2){ //po wpłacie
            document.getElementById("paylater").style.display = "none";
            document.getElementById("paynow").style.display = "inline";
        }else{ //od razu, bo weteran
            document.getElementById("paylater").style.display = "inline";
            document.getElementById("paynow").style.display = "none";
        }
    }
    function w_priceJustif(what){
        document.getElementById("pricejustif").innerHTML = what;
    }
    function w_time(time){
        document.getElementById("time").innerHTML = (time.length > 0) ? time : "do końca najbliższego weekendu";
    }
    function w_other(mode){
        var teksty = [", choć generalnie", "studiuję", "i", "mam obecnie wiele innych zamówień"];
        switch(parseInt(mode)){
            case 0: teksty = ""; break;
            case 1: teksty = [0, 1].map(i => teksty[i]).join(" "); break;
            case 2: teksty = [0, 3].map(i => teksty[i]).join(" "); break;
            case 3: teksty = teksty.join(" "); break;
        }
        document.getElementById("otherThingsIDo").innerHTML = teksty;
    }
</script>

<?php
#####
generateBottom("main");
?>