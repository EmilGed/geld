wasClicked = false;
wasEdited = false;

$(document).ready(() => {

    $("html").on("click", "#closeBearbeiten", (e) => {
        $("#bearbeitenScreen").remove();
        wasClicked = false;
        wasEdited = false;
    })

    $("html").on("click", "#editTitle", (e) => {
        if(!wasClicked){
            $("body").before(document.getElementsByTagName("template")[5].content.cloneNode(true))
            wasClicked = true;
        }
    })

    $("html").on("click", "#editSubmit", (e) => {
        if(wasEdited){
            var olddata = $("#bearbDetails").val().split("|!§|")
            var rid = $("#rid").val();
            var siteData = {"name": $("#name").val() == olddata[0] ? null : $("#name").val(), "beschreibung": $("#beschreibung").val() == olddata[1] ? null : $("#beschreibung").val(), "isPublic": $("#visibility").val() == olddata[2] ? null : ($("#visibility").val() == "public" ? "1" : "0")}
            var data = {"key": "Lalilu", "type": "editReise", "rid": rid}
            Object.entries(siteData).forEach(([key, value]) => {
                if(value != null) data[key] = value;
            })

            $.post("../dbChanger/changer.php", data).done((d) => {
                console.log(d)
                $("#bearbeitenScreen").remove();
                wasClicked = false;
                wasEdited = false;
                location.reload();
            })

        }
    })

    $("html").on("change", "#name, #beschreibung, #visibility", (e) => {
        wasEdited = true;
    })

})