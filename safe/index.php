 <?php
session_start();
$z = $_SERVER['DOCUMENT_ROOT']; $z .= "/cue.php"; require($z); //podpięcie cue
giveMeTheCue(0);

//wczytaj ustawienia
$q = "SELECT * FROM p_opcje";
$r = $conn->query($q) or die($conn->error);
while($a = $r->fetch_assoc()){
    $_OPTIONS[$a['par']] = $a['val'];
}
$r->free_result;

if(!isset($_SESSION['whoami'])){
    $_SESSION['wherewasi'] = $_SERVER['REQUEST_URI'];
}

//ustal dane projektu
$q = "SELECT p.tytuł, p.album, p.inspiracja, c.nazwisko, c.hasło, p.link, q.data_3, q.data_4, q.data_5, c.kio, q.cena, q.zyczenia
    FROM p_projekty p
        JOIN p_klienci c ON p.inspiracja = c.id
        JOIN p_questy q ON p.id = q.id
    WHERE p.id = '".$_GET['p']."'";
$r = $conn->query($q) or die($conn->error);
$project = $r->fetch_assoc();
$r->free_result;

//akceptacja projektu
if(isset($_POST['acc_sub'])){
    $tick3 = ($project['data_3'] == null) ? ", data_3 = '".date('Y-m-d')."'" : "";
    $q = "UPDATE p_questy SET data_4 = '".date('Y-m-d')."'$tick3 WHERE id = '".$_GET['p']."'";
    $conn->query($q) or die($conn->error);
    
    $q = "UPDATE p_projekty SET status = 0, status_opis = 'ukończony', data_out = '".date('Y-m-d')."' WHERE id = '".$_GET['p']."'";
    $conn->query($q) or die($conn->error);
    
    //potwierdzenie mailowe
    $to = "contact@wpww.pl";
    $subject = "Projekt ".$_GET['p']." (".$project['tytuł'].") został zatwierdzony";
    $message = $subject;
    // Always set content-type when sending HTML email
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    mail($to,$subject,$message,$headers);
    
    header("Location: http://projects.wpww.pl/safe/?p=".$_GET['p']."&b=".$_GET['b']);
}

$terminy = ["data_4" => $project['data_4'], "data_5" => $project['data_5']];

//zlicz przeszłość projektową w celu ustalenia stałego klienta
$q = "SELECT count(id) FROM p_questy WHERE klient_id = ".$project['inspiracja']." AND data_4 IS NOT NULL";
$r = $conn->query($q) or die($conn->error);
$exp = $r->fetch_array();
$r->free_result;

//upload plików
if(isset($_POST['u_sub'])){
    $catalogue = $_GET['p']."/";
    for($i=0; $i<count($_FILES["u_files"]["name"]); $i++){
        $file = $catalogue.$_FILES["u_files"]["name"][$i];
	    move_uploaded_file($_FILES['u_files']["tmp_name"][$i], $file);
    }
}

generateHead("Pliki projektu ".$_GET['p'], 0, "pl");
?>

<div class="home">
<h1>Sejf</h1>

<?php
//ustal, kto jest chciany
if($project['inspiracja'] != $_SESSION['clientid'] && $_SESSION['whoami'] != "archmage" && $_GET['b'] != $project['hasło']) print "<img src='/interface/wykrzyknik.svg' class=achtung alt='warning'>Wybacz, przyjacielu, ale nie masz dostępu do tych plików. <b><a href='/auth.php'>Zaloguj się</a></b>";
else{
?>
<h3 style='margin: 0;'><?php echo $project['album']; ?></h3>
<h2><?php echo ($project['tytuł'] === null) ? "<i>Utwór bez tytułu</i>" : $project['tytuł']; ?></h2>
<h3>Projekt <a href='/?p=<?php echo $_GET['p']; ?>' title='Informacje o projekcie'><?php echo $_GET['p']; ?></a>
<?php
print ($project['data_5'] !== null) ? "<span style='color: lime; text-shadow: 0 0 15px white; user-select:none;' title='Projekt opłacony'>&#x2713;</span>" :
    "<span style='color: #f6d366; font-weight: normal;' title='Cena projektu'>".$project['cena']." zł</span>";
?>
</h3>
<?php echo ($project['zyczenia'] != null) ? "<p class='zyczenia'><strong>Życzenia dotyczące projektu:</strong><br>".nl2br($project['zyczenia'])."</p>" : ""; ?>

<?php
//zbierz pliki
$folder = "/safe/".$_GET['p'];
if(!is_dir($_SERVER['DOCUMENT_ROOT'].$folder)){ print "<img src='/interface/wykrzyknik.svg' class=achtung alt='warning'>Projekt nie posiada swojego repozytorium w Sejfie."; }
else{
    $files = scandir($_SERVER['DOCUMENT_ROOT'].$folder);
    if(count($files) == 2) print "<img src='/interface/wykrzyknik.svg' class=achtung alt='warning'>Na chwilę obecną sejf jest pusty.";
    else{
        //oglądać może jedynie niewygnany
        $cansee = ($project['kio'] < 1) ? 1 : 0;
        
        //pobierać może jedynie opłacający, niewygnany weteran i nadzwyczajnie zaufany
        $candownload = ($project['data_5'] != null || ($exp[0]>$_OPTIONS['veteranlevel'] && $project['kio'] < 1) || $project['kio'] == -1) ? 1 : 0;
        
        //etykieta informacyjna o pobieraniu
        print ($candownload) ? "<p style='margin-top: 0'><i>Kliknij na odpowiedni kafelek, aby pobrać plik</i></p>" :
            "<p style='margin-top: 0'><i>Pobieranie wyłączone – do momentu otrzymania przeze mnie wpłaty możesz jedynie przeglądać pliki.</i></p>";
        for($i = 2; $i<count($files); $i++){
            if(strpos($files[$i], "_")){
                $vname = substr($files[$i], 0, strpos($files[$i], "_"));
                $vsname = substr($files[$i], strpos($files[$i], "_")+1, strpos($files[$i], ".")-strpos($files[$i], "_")-1);
            }else{
                $vname = substr($files[$i], 0, strpos($files[$i], "."));
                $vsname = "";
            }
            $versions[$vname][$vsname][] = $files[$i];
            
            //najnowsze wersje
            if(!isset($freshver[$vname])) $freshver[$vname] = 0;
            if(filemtime($_SERVER['DOCUMENT_ROOT'].$folder."/".$files[$i]) >= @filemtime($_SERVER['DOCUMENT_ROOT'].$folder."/".$freshver[$vname])) $freshver[$vname] = $files[$i];
        }
        krsort($versions); //sortuj wielkie oznaczenia wersji
        krsort($freshver);

        print "<div class='flexright'>";
        foreach($versions as $name => $cont){ //dla każdej wersji
            print "<div class='safe_ver'>";
            print "<h4 title='Nazwa wersji'>$name</h4>";
            foreach($versions[$name] as $subname => $subcont){ //dla każdej z subwersji
                $actualname = ($subname == "") ? $name : $name."_".$subname;
                print "<div class='safe_tile'>";
                
                $currentverflag = (substr($freshver[$name], 0, strpos($freshver[$name], ".")) == $actualname) ? " style='background-color: #f6d366cc'" : "";
                if(count($versions[$name]) >1) print ($subname == "") ? "<h5$currentverflag>wersja zero</h5>" : "<h5$currentverflag>$subname</h5>";
                print "<div class='flexright'>";
                
                $wasthere = null; //flagi sprawdzacza, czy jest tam coś odtwarzalnego -- jeśli tak, przygotuje player
                for($i=0; $i<count($subcont); $i++){ //dla każdego pliku
                    $ext = substr($subcont[$i], strpos($subcont[$i], ".")+1);
                    //przyciski do pobierania
                    print ($candownload) ? "<a href='$folder/".$subcont[$i]."' title='Pobierz plik $ext' class='".substr($subcont[$i], strpos($subcont[$i], ".")+1)."' download>
                            <div>
                                <img src='/interface/fileicons/download.png' alt='download'>
                                <img src='/interface/fileicons/$ext.png' alt='$ext'>
                            </div>    
                            ".$ext."
                        </a>" : "";
                    if($ext == "mp3" || $ext == "mp4") $wasthere = $ext;
                }
                print "</div>";
                
                //jeśli można drukuj player; priorytet dla MP4
                if($cansee){
                    if($wasthere == "mp4") print "<video controls controlsList='nodownload'><source src='$folder/$actualname.mp4' type='video/mp4'>Odtwarzanie niemożliwe</video>";
                        else if($wasthere == "mp3") print "<audio controls controlsList='nodownload'><source src='$folder/$actualname.mp3' type='audio/mpeg'>Odtwarzanie niemożliwe</audio>";
                }else{
                    print "Opłać projekt, aby móc odtworzyć";
                }
                print "</div>";
            }
            print "</div>";
        }
        print "</div>";
        
        echo "<div><p>Dostęp do katalogu wyłącznie dla: <b>".str_replace(" ", "&nbsp;", $project['nazwisko'])."</b>.<br>";
        if($terminy['data_4'] !== null && $terminy['data_5'] !== null){
            $terminy['data_4'] = date_diff(date_create(), date_create($terminy['data_4']));
            $terminy['data_5'] = date_diff(date_create(), date_create($terminy['data_5']));
            $deadline = $_OPTIONS['filesexpiration']-min($terminy['data_4']->format("%a"), $terminy['data_5']->format("%a"));
            echo "Folder zostanie automatycznie usunięty ";
            echo ($deadline == 1) ? "<b style='color: red;'>jutro</b>." : "za <b>$deadline&nbsp;dni</b>.";
        }else{
            echo "Folder zostanie automatycznie usunięty <b>".$_OPTIONS['filesexpiration']."&nbsp;dni</b> po akceptacji i opłaceniu projektu.";
        }
        echo "</p></div>";
        
        //formularz akceptacji
        if($terminy['data_4'] === null){
?>
<form method=post>
    Jeżeli akceptujesz projekt <b>bez konieczności wprowadzania dalszych poprawek</b>, kliknij przycisk poniżej.<br>
    <input type=submit name='acc_sub' value='Wszystko jest OK'></input>
</form>
<?php
        }
    } // koniec else'a pustego sejfu
    
    //arcymag może wrzucać pliki
    if($_SESSION['whoami'] == "archmage"){
        echo "<div class='flexright'>";
        
        echo "<div class='safe_ver'><h4>Dodaj pliki</h4>";
        echo "<a target='_blank' href='https://hydromancer.xaa.pl:2083/cpsess4473617397/frontend/paper_lantern/filemanager/upload-ajax.html?file=&fileop=&dir=%2Fhome%2Fp497635%2Fpublic_html%2Fprojects%2Fsafe%2F".$_GET['p']."&dirop=&charset=&file_charset=&baseurl=&basedir='>cPanel</a>";
        //echo "<form method=post action='?p=".$_GET['p'];
        //if(isset($_GET['b'])) echo "&b=".$_GET['b'];
        //echo "' enctype='multipart/form-data'><input type='file' name='u_files[]' multiple><br><input type='submit' name='u_sub'></form>";
        echo "</div>";
        
        //arcymag może dodawać czas wykonania
        echo "<div class='safe_ver'><a href='/quest_stats_survey.php?id=".$_GET['p']."'><h4>Ankieta końcowa</h4></a>";
        //czy ankieta już jest
        $q = "SELECT quest FROM p_questy_stats WHERE quest LIKE '".$_GET['p']."'";
        $r = $conn->query($q) or die($conn->error);
        $a = $r->fetch_assoc();
        $r->free_result;
        echo (count($a) >0) ? 
            "<span style='color: lime; text-shadow: 0 0 15px white; user-select:none;'>&#x2713;</span> Ankieta wypełniona" :
                "<span style='color: red; text-shadow: 0 0 15px white; user-select:none;'>X</span> Brak ankiety";
        echo "</div>";
        
        echo "</div>";
    }
} // koniec else'a błędu
} // koniec else'a "kto jest chciany"
?>

</div>

<br>
<?php
//inteligentne cofanie do twoich projektów czy też nie
if(isset($_GET['b']) && !isset($_SESSION['whoami'])){
    echo "<a href='/?b=".$_GET['b']."'><b>Zobacz wszystkie swoje projekty</b></a>";
}else{
    ?><a href="/"><b>Cofnij do projektów</b></a><?php
}
?>



<?
generateBottom("main");
?>