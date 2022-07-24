function openProject(projectid){
    //język
    var langapp = (lang) ? "l=en&" : "";
    
	//pokaż details
	$('.details').addClass('unvanished');
	//rozmyj tył
	$('.details').prev().addClass('blurred');

	//zmienne labele dla niektórych typów projektów
	if(projekty[projectid]['status'] == "fused"){
	    if(lang) $('.details #d_titlelabel').text("Fused with"); else $('.details #d_titlelabel').text("Połączony z");
	}else{
	    if(lang) $('.details #d_titlelabel').text("Song title"); else $('.details #d_titlelabel').text("Tytuł utworu");
	}
	
	if(projectid.substring(0,1) == "Z" || projectid.substring(0,1) == "K"){
	    if(lang) $('.details #d_albumlabel').text("Original artist"); else $('.details #d_albumlabel').text("Oryginalne wykonanie");
	    if(lang) $('.details #d_inspilabel').text("Client"); else $('.details #d_inspilabel').text("Zleceniodawca");
	}else{
	    $('.details #d_albumlabel').text("Album");
	    if(lang) $('.details #d_inspilabel').text("Inspired by (PL)"); else $('.details #d_inspilabel').text("Inspiracja");
	}
	
	//zmień tytuł i adres strony
	document.title = (lang) ? "Project "+projectid : "Projekt "+projectid;
	    document.title += " | WPWW";
	history.pushState('Project '+projectid, "Project "+projectid, 'http://projects.wpww.pl/?p='+projectid);
	
	//wypełnij details danymi
	$('.details #d_id').text(projectid);
	$('.details #d_name').text(projekty[projectid]['nazwa']);
	$('.details #d_status').text(projekty[projectid]['status_opis']);
	$('.details #d_title').text(projekty[projectid]['tytul']);
	$('.details #d_album').text(projekty[projectid]['album']);
	$('.details #d_inspired').text(projekty[projectid]['inspiracja']);
	$('.details #d_datein').text(projekty[projectid]['data_in']);
	$('.details #d_dateout').text(projekty[projectid]['data_out']);
	
	//wstawianie linku
	if(projekty[projectid]['link'].search("iframe") > -1 || projekty[projectid]['link'].search("brzoskwinia") > -1){ //embed czy link?
	    $(".details #d_link").append(projekty[projectid]['link']);
	}else if(projekty[projectid]['link'].search("drive") > -1){
    	$(".details #d_link").append("<a href=\""+projekty[projectid]['link']+"\" target=\"_blank\" title='Otwórz Google Drive'><img src='/interface/drive.png' alt='Repozytorium'></a>");
	}else if(projekty[projectid]['link'].search("projects") > -1){
    	$(".details #d_link").append("<a href=\""+projekty[projectid]['link']+"\" target=\"_blank\" title='Otwórz sejf'><img src='/interface/projects.png' alt='Repozytorium'></a>");
	}
	
	//przekoloruj header
	$('.details h1').addClass(projekty[projectid]['status']);
	
	//arcymag -- edytuj link do edycji
	$('.details a[href^=archmage]').attr("href", "archmage.php?e="+projectid);
}

function closeInfo(whatinfo){
	$(whatinfo).removeClass('unvanished');
	$(whatinfo).prev().removeClass('blurred'); //usuń rozmycie z panelu wcześniej
	$(whatinfo+" #d_link").empty(); //usuń linki/embedy
	$(whatinfo+" h1").removeClass(); //usuń kolor headera
	document.title = (lang) ? "Project list" : "Lista projektów"; //przywróć tytuł
	    document.title += " | WPWW";
	history.pushState('Project list', "Project list", 'http://projects.wpww.pl/');
}

function am_untick(whattountick, currenttext){
    if(currenttext === '') document.getElementsByName(whattountick)[0].checked = true;
    else document.getElementsByName(whattountick)[0].checked = false;
}

//////////////////////////////

$(function(){

$('.project').click(function(){ openProject($("p", this).text()); });
$('#detailsclose, #d_link').click(function(){ closeInfo('.details'); });

});