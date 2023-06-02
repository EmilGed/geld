var wasclicked = false
var screenisSame = true
var lastOwner = 0
var insgLastEdit = true
var editwasclicked = false
var lastEditClicked = 0
var somethingWasEdited = false

function checkInvolved(){ //? Ids der Involvierten Leute bei der Rechnung
    if(wasclicked || editwasclicked){
        var involved = []
        $(".rechnCheckBoxTeil").each((i, e) => {if(e.checked) involved.push(e.id.replace("rechnTeil", ""));})
        return involved
    }
    return false
}

function checkPayed(ids){ //? Ids der bereits Bezahlten Leute bei der Rechnung
    if(wasclicked || editwasclicked){
        var payed = []
        ids.forEach((e) => {if($("#rechnBez" + e).prop("checked")) payed.push(e)})
        return payed
    }
    return false
}

function checkKostInd(ids){ //? Kosten jeder Person, wenn kein gleicher Preis p.P.
    if((wasclicked || editwasclicked) && !screenisSame){
        var kostenId = []
        var oneWas0 = false
        ids.forEach((e,i) => {
            var wert = Number($("#preisproPers" + e).val())
            if(wert < 0){
                oneWas0 = true
            }
            kostenId.push(e + ":" + wert)
        })
        return oneWas0 ? false : kostenId
    }
    return false
}

function getPP(){ //? Berechnet Preis p.P. wenn screenSame ist anhand von Anzahl der Involvierten (Ändert direkt auf der Seite)
    var involved = checkInvolved()
    var insg = Number($("#preisInsg").val())
    if(involved !== false){
        involved = involved.length
        var money = insg/involved
        if(money == Infinity){
            money = 0
        }
        $("#preispp").val(money.toFixed(2))
    }
    return false
}

function getInsg(){ //? Berechnet Preis anhand von Preis p.P. anhand von Anzahl der Inolvierten (Ändert direkt auf der Seite)
    var involved = checkInvolved()
    var pp = Number($("#preispp").val())
    if(involved !== false){
        involved = involved.length
        var money = pp*involved
        $("#preisInsg").val(money.toFixed(2))
    }
    return false
}

function updateTo(){ //? Verändert besitzer und entfernt Bezahlt checkmark vom letzten Owner
    if(lastOwner != 0){
        $("#rechnBez" + lastOwner).prop("checked", false)
        $("#rechnTeil" + lastOwner + ", #rechnBez" + lastOwner).attr("disabled", false)
    }
    var owner = $("#geldAn").val()
    $("#rechnTeil" + owner + ", #rechnBez" + owner).prop("checked", true)
    $("#rechnTeil" + owner + ", #rechnBez" + owner).attr("disabled", true)
    lastOwner = owner
}

function updateKosIns(){ //? Kosten Insgesamt, wenn !ScreenSame
    var sum = 0
    checkInvolved().forEach((e, i) => {
        sum += Number($("#preisproPers" + e).val())
    })
    $("#rechnIndvInsgKost").html(sum.toFixed(2) + "€")
}

function update(){ //? Updatet die nötigen Daten je nach offenem Screen
    if(screenisSame){
        if(insgLastEdit){
            getPP()
        }else{
            getInsg()
        }
    }else{
        updateKosIns()
    }
}

function checkEverything(name){ //? Wählt alle Kästchen einer Klasse aus/entwählt sie. Anhand der obersten Person
    var firstPos = null
    $(name).each((i,e) => {
        if(!e.disabled){
            if(firstPos == null){
                firstPos = e.checked
            }
            e.checked = !firstPos
        }
    })
}

function checkCertainUsers(involved, hasPayed){ //? Wählt die gegebenen User im neue Rechnungsfeld aus (Nur user die auch involved sind können bezahlt haben. Welche die bezahlt haben, aber nicht involviert sind werden nicht ausgewählt)
    if(involved == false) return
    involved.forEach((e) => {
        $("#rechnTeil" + e).prop("checked", true) 
        if(hasPayed.includes(e)){
            $("#rechnBez" + e).prop("checked", true)
        }
    })
}

function setKostenIndv(string){
    string.split(",").forEach((e) => {
        var indvKost = e.split(":")
        $("#preisproPers" + indvKost[0]).val(Number(indvKost[1]).toFixed(2))
    });
}

function createKostenString(involved, pP){
    var output = ""
    involved.forEach((e) => {
        output += e + ":" + pP + ","
    })
    return output.substring(0, output.length-1)
}

function addRechnungsfenster(){
    if(wasclicked ||editwasclicked){
        $("#neueRechnungLastTD").after(document.getElementsByTagName("template")[2].content.cloneNode(true))
    }
}

$(document).ready(() => {    
    $("body").on("click", "#neueRechnungStart", (e) => { //? Erstellt den Neue Rechnungsscreen
        if((wasclicked && editwasclicked) == false){
            $("body").before(document.getElementsByTagName("template")[1].content.cloneNode(true))
            wasclicked = true
            screenisSame = true
            addRechnungsfenster()
            updateTo()
        }
    })
    
    $("html").on("click", "#closeNeueRechnung", (e) => { //? Entfernt Neue Rechnungsscreen
        $("#neueRechnungScreen").remove();
        wasclicked = false;
        editwasclicked = false;
        somethingWasEdited = false
    })

    $("html").on("change", "#rechnArt", (e) => { //? Ändert vom gleicher Preis p.P. zum anderen Screen. Personen und Bezahlte werden übernommen. Wenn von same->indv p.P. wird zu den Kosten aller. wenn indv->same gesamtkosten/involved wird zu p.P.
        somethingWasEdited = true
        var involved = checkInvolved()
        var payed = checkPayed(involved)
        if(e.target.value == "indv"){ // same->indv
            screenisSame = false;
            var pP = $("#preispp").val()
            $("#neueRechnungLastTD").after(document.getElementsByTagName("template")[3].content.cloneNode(true))
            $(".options").after(document.getElementsByTagName("template")[4].content.cloneNode(true))
            $(".rechnSame").remove()
            setKostenIndv(createKostenString(involved, pP)) // Setzt Preis p.P. zu den Individuellen Kosten aller.
            $("#rechnIndvInsgKost").html((involved.length*pP).toFixed(2) + "€");
        }else{ //indv->same
            screenisSame = true;
            var kostIngs = (Number($("#rechnIndvInsgKost").html().replace("€", ""))/involved.length).toFixed(2);
            $(".rechnIndv").remove()
            $("#neueRechnungLastTD").after(document.getElementsByTagName("template")[2].content.cloneNode(true))
            $("#preispp").val(kostIngs)
            $("#preisInsg").val((kostIngs*involved.length).toFixed(2))  
        }
        updateTo()
        checkCertainUsers(involved, payed)
    })

    $("html").on("click", "#rechnAlleTeil", (e) => { //? (Ent)Wählt alle Teilnehmer aus
        somethingWasEdited = true
        checkEverything(".rechnCheckBoxTeil")
        update()
    })
    $("html").on("click", "#rechnAlleBez", (e) => { //? (Ent)Wählt alle Teilnehmer aus die Bezahlt haben
        somethingWasEdited = true
        checkEverything(".rechnCheckBoxBez")
    })

    $("html").on("change", ".rechnCheckBoxTeil", (e) => { //? Wenn die Anzahl der Teilnehmer sich verändert werden alle Werte geupdated
        somethingWasEdited = true
        update()
    })

    $("html").on("change", ".rechnCheckBoxBez", (e) => { //? Wenn die Anzahl der Teilnehmer sich verändert werden alle Werte geupdated
        somethingWasEdited = true
    })

    $("html").on("change", "#preisInsg", (e) => { //? Wenn die Preis Insgesamt Textbox verändert wird(Screensame), wird der Preis p.P. verändert 
        if(screenisSame){
            somethingWasEdited = true
            insgLastEdit = true
            var val = Number(e.target.value)
            var amountInvolved = checkInvolved()
            if(amountInvolved !== false){
                amountInvolved = amountInvolved.length
                var pp = val/amountInvolved
                if(pp == Infinity){
                    pp = 0
                }
                $("#preispp").val(pp.toFixed(2))
            }
        }
    })

    $("html").on("change", "#preispp", (e) => { //? Gleiche wie oben nur andersherum
        if(screenisSame){
            somethingWasEdited = true
            insgLastEdit = false
            var val = Number(e.target.value)
            var amountInvolved = checkInvolved()
            if(amountInvolved !== false){
                amountInvolved = amountInvolved.length
                var insg = val * amountInvolved
                $("#preisInsg").val(insg.toFixed(2))
            }
        }
    })

    $("html").on("change", "#geldAn", (e) => { //? Wenn die Person, an die das Geld geht sich verändert
        somethingWasEdited = true
        updateTo()
        getPP()
    })

    $("html").on("change", ".numIn", (e) => { //? Wenn Inputfelder für Zahlen sich verändern werden sie, wenn sie unter 0 sind rot hinterlegt
        somethingWasEdited = true
        updateKosIns()
        if(Number(e.target.value) < 0){
            $("#" + (screenisSame ? "preispp, #preisInsg" : e.target.id)).css("border-color", "red").css("color", "red");
        }else{
            $("#" + (screenisSame ? "preispp, #preisInsg" : e.target.id)).css("border-color", "").css("color", "");
        }
    })

    $("html").on("click", "#rechnSubmit", (e) => { //? Submit, alle Daten werden gesammelt und dann in DB gepackt
        var involved = checkInvolved()
        var hasPayed = checkPayed(involved)
        var kategorie = $("#KategorieRechnung").val()
        var notiz = $("#NotizRechnung").val();
        var geldAn = $("#geldAn").val()
        var rid = $("#rid").val();
        var insg;
        var pp;
        if(screenisSame){ // Gleiche Kosten p.P.
            insg = Number($("#preisInsg").val())
            pp = Number($("#preispp").val())
            if(insg < 0 || pp < 0){
                alert("Kosten können nicht negativ sein!")
                return false
            }
        }else{
            pp = checkKostInd(involved)
            insg = Number($("#rechnIndvInsgKost").html().replace("€", ""))
            if(insg < 0 || pp === false){
                alert("Kosten können icht  negativ sein!")
                return false
            }
        }
        var data = {"key": "Lalilu", "type": "addRechnung", "rid": rid, "isSame": screenisSame, "geldAn": geldAn, "involved": involved, "hasPayed": hasPayed, "notiz": notiz, "kategorie": kategorie, "insg": insg, "pp": pp}
        if($("#typeOfRechnungsMenu").val() == "edit"){
            if(somethingWasEdited == false){ 
                alert("Du hast nichts an der Rechnung verändert")
                return;
            }
            var newdata = {"key": "Lalilu", "type": "editRechnung", "rid": rid, "rechID": lastEditClicked, "givenCols": "", "usersToRemove": "", "usersToAdd": ""}
            var olddata = $("#rechnungsDetails" + lastEditClicked).val().split("|")
            
            // samePP
            newdata.samePP = screenisSame ? "1" : "0";
            if(olddata[3] != screenisSame){
            newdata.givenCols += (newdata.givenCols.length == 0 ? "" : "|") + "samePP"
            }
            // Geld an
            newdata.geldAn = geldAn
            if(olddata[3] != geldAn){
            newdata.givenCols += (newdata.givenCols.length == 0 ? "" : "|") + "geldAn"
            }
            // kosten
            newdata.kosten = insg
            if(olddata[2] != insg){
                newdata.givenCols += (newdata.givenCols.length == 0 ? "" : "|") + "kosten"
            }
            // userToAdd und userToRemove
            var oldInvolved = olddata[5].split(",")
            newdata.usersToAdd = (newdata.usersToRemove.length == 0 ? "" : ",") + involved.filter(x => !oldInvolved.includes(x))//.toString()
            newdata.usersToRemove = (newdata.usersToRemove.length == 0 ? "" : ",") + oldInvolved.filter(x => !involved.includes(x))//.toString();
            // hasPayed
            newdata.hasPayed = hasPayed
            if(olddata[6] != hasPayed){
            newdata.givenCols += (newdata.givenCols.length == 0 ? "" : "|") + "hasPayed"
            }
            //involved
            newdata.involved = involved
            if(olddata[5] != involved){
                newdata.givenCols += (newdata.givenCols.length == 0 ? "" : "|") + "involved"
            }
            //pp
            newdata.pp = pp
            if(olddata[9] != pp){
                newdata.givenCols += (newdata.givenCols.length == 0 ? "" : "|") + "pp"
            }
            //kategorie
            newdata.kategorie = kategorie
            if(olddata[10] != kategorie){
                newdata.givenCols += (newdata.givenCols.length == 0 ? "" : "|") + "KID"
            }
            //notiz
            newdata.notiz = notiz
            if(olddata[11] != notiz){
                newdata.givenCols += (newdata.givenCols.length == 0 ? "" : "|") + "notiz"
            }

            console.log(data)
            console.log(olddata)
            console.log(newdata)
            data = newdata;
        }

        $.post("../dbChanger/changer.php", data).done((d) => {
            console.log(d)
            $("#neueRechnungScreen").remove();
            wasclicked = false;
            editwasclicked = false;
            location.reload()
        })
    })

    $("html").on("click", ".begleichen", (e) => { //? Rechnung wird komplett beglichen
        if(confirm("Willst du diese Rechnung komplett begleichen?")){
            var id = e.target.id.replace("begleichen", "")
            var rid = $("#rid").val();
            var involved = $("#involved" + id).val();
            $.post("../dbChanger/changer.php", {"key": "Lalilu", "type": "begleichRechnung", "rid": rid, "rechID": id, "involved": involved}).done((d) => {
                console.log("Rechnung: " + id + " beglichen");
                location.reload()
                console.log(d)
            })
        }
    })

    /*
0 samePP: 1 
1 rechID: 37 
2 kosten: 20.00 
3 geldAn: 1 
4 beglichen: 1 
5 involved: 1,2,3,4 
6 hasPayed: 1,2,3,4 
7 time: 2023-05-24 17:39:12 
8 beglichenAm: 2023-05-24 17:41:37 
9 kostenpp/kostenaufteilung: 20.00
10 kategorie: 1
11 notiz blablablabla
*/
    $("html").on("click", ".edit", (e) => { //? Editieren der Rechnung ermöglichen
        if(!editwasclicked){
            editwasclicked = true;
            somethingWasEdited = false;
            var id = e.target.id.replace("edit", "")
            var data = $("#rechnungsDetails" + id).val().split("|")
            lastEditClicked = id
            $("body").before(document.getElementsByTagName("template")[1].content.cloneNode(true))
            $("#typeOfRechnungsMenu").val("edit");
            $("#ridDerEditRechnung").val(data[1]);
            $("#KategorieRechnung").val(data[10]);
            $("#NotizRechnung").val(data[11]);
            $("#geldAn").val(data[3]);
            if(data[0] == 1){
                screenisSame = true
                $("#neueRechnungLastTD").after(document.getElementsByTagName("template")[2].content.cloneNode(true))
                $("#preisInsg").val(data[2]);
                $("#preispp").val(data[9]);
            }else{
                screenisSame = false;
                $("#neueRechnungLastTD").after(document.getElementsByTagName("template")[3].content.cloneNode(true))
                $(".options").after(document.getElementsByTagName("template")[4].content.cloneNode(true))
                $("#rechnArt").val("indv")
                setKostenIndv(data[9])
            }

            checkCertainUsers(data[5].split(","), data[6].split(","))

            $("#NeueRechnungTitle").html("Rechnung Editieren")

            update()
            updateTo()
        }
    })

    $("html").on("click", ".delete", (e) => { //? Rechnung wird gelöscht
        if(confirm("Willst du diese Rechnung wirklich löschen?")){
            var rid = $("#rid").val();
            var id = e.target.id.replace("delete", "")
            var involved = $("#involved" + id).val();
            $.post("../dbChanger/changer.php", {"key": "Lalilu", "type": "removeRechnung", "rid": rid, "rechID": id, "involved": involved}).done((d) => {
                console.log("Rechnung: " + id + " gelöscht");
                location.reload()
            })
        }
    })

    $("html").on("change", "#KategorieRechnung, #NotizRechnung", (e) =>{
        if(editwasclicked){
            somethingWasEdited = true
        }
    })
})