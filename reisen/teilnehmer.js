var teilnehmerChanged = false
$(document).ready(() => {
    $("body").on("click", "#teilnehmerBearbeiten", (e) => {
        if(teilnehmerChanged){
            location.reload()
        }
        var rollen = document.getElementsByClassName("role")
        for(var i = 0; i < rollen.length; i++){
            if(rollen[i].id != "owner"){
                if($("#ändern" + rollen[i].id).length == 0){
                    $("#" + rollen[i].id).after("<button class='ändernrolle' id='ändern" + rollen[i].id + "'>Ändern</button>")
                    $("#ändern" + rollen[i].id).after("<button class='teilnehmerentfernen' id='teilnehmerentfernen" + rollen[i].id.replace("rolle", "") + "'>-</button>")
                }else{
                    $("#ändern" + rollen[i].id).remove()
                    $("#teilnehmerentfernen" + rollen[i].id.replace("rolle", "")).remove()
                }
            }
        }

        if($("#teilnehmerHinzufügen").length == 0){
            $("#teilnehmerList").after(document.getElementsByTagName("template")[0].content.cloneNode(true))
        }else{
            $("#teilnehmerHinzufügen").remove();
        }
    })

    $("body").on("click", "#addButton", (e) => {
        var id = $("#teilnehmerAdd").val()
        var rid = $("#rid").val();
        var name = $("#option" + id).html();
        teilnehmerChanged = true
        $.post("../dbChanger/changer.php", {"key": "Lalilu", "type": "addTeil", "id": id, "rid": rid}).done((d) => {console.log(d)})
        // Benötigte Neue Sachen hinzufügen / Löschen
        $(".teilnehmerList").append('<li class="teilnehmerLI" id="teilnehmerLI' + id + '"><a id="name' + id + '">' + name + '</a> <a id="rolle' + id + '" class="role">(Beobachter)</a></li>') // User hinzzfügen
        $("#option" + id).remove()
        $("#rolle" + id).after("<button class='ändernrolle' id='ändernrolle" + id + "'>Ändern</button>")
        $("#ändernrolle" + id).after("<button class='teilnehmerentfernen' id='teilnehmerentfernen" + id + "'>-</button>")
    })

    $("body").on("click", ".teilnehmerentfernen", (e) => {
        var id =  $(e.target).attr('id').replace("teilnehmerentfernen", "");
        var name = $("#name" + id).html();
        teilnehmerChanged = true
        if(confirm("Möchtes du " + name + " wirklich aus der Reise entfernen?")){ // User entfernen
            $.post("../dbChanger/changer.php", {"key": "Lalilu", "type": "removeTeil", "id": id}).done((d) => {console.log(d)})
            console.log(id + ": entfernt")
            $("#teilnehmerLI" + id).remove();
            $("#teilnehmerAdd").append('<option id="option' + id + '" value="' + id + '">' + name + '</option>')
        }
    })

    $("body").on("click", ".ändernrolle", (e) => {
        var id = "#" + $(e.target).attr('id').replace("ändern", "");
        var idreal = id.replace("#rolle", "");
        var rid = $("#rid").val();
        if($(id).html() == "(Beobachter)"){
            $.post("../dbChanger/changer.php", {"key": "Lalilu", "type": "roleChanger", "id": idreal, "rid": rid, "newRole": "1"}).done((d) => {console.log(d)})
            $(id).html("(Mitarbeiter)");
            $(id).addClass("mitarbeiter")
        }else{
            $.post("../dbChanger/changer.php", {"key": "Lalilu", "type": "roleChanger", "id": idreal, "rid": rid, "newRole": "0"}).done((d) => {console.log(d)})
            $(id).html("(Beobachter)");
            $(id).removeClass("mitarbeiter")
        }
    })
})