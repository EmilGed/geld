var wasClicked = false;
$(document).ready(() => {

    $("#newButton").click(() =>{
        if(wasClicked == false){
            showTemplate();
            wasClicked = true;
        }
    })

    // $("#close").click(() => {
    //     console.log("sad")
    //     $("#neueReise").remove();
    //     wasClicked = false;
    // })
});

function log(message){
    console.log(message);
}

function showTemplate(){
    var template = document.getElementsByTagName("template")[0].content.cloneNode(true)
    document.body.insertBefore(template, document.body.firstChild);
    
    $("#close").click(() => { // Muss hier sein, da hier erst das template zum HTML hinzugefügt wird.
        $("#neueReise").remove();
        wasClicked = false;
    })
}
