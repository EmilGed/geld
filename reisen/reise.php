<?php

$rid = isset($_GET["rid"]) ? $_GET["rid"] : NULL;
if($rid == NULL){
    echo "Keine Reise ID gegeben";
    header("Refresh: 1; url=../index.php"); // Nach einer Sekunde zur login-Seite
    exit(); // Damit das HTML nicht angezeigt wird.
}

if(isset($_COOKIE["logged_in"]) and isset($_COOKIE["key"]) and $_COOKIE["key"] == "IchEsseKinder"){ // Eingeloggt
    $mysqli = mysqli_connect('localhost', 'datalogger', 'hallo123', "geld");
    mysqli_set_charset($mysqli, "utf8mb4");
    
    $teilnehmer = []; // Teilnehmer, uid und Rolle
    $teilnehmerIds = [];
    $namen = []; // Namen der User die involviert sind
    $userIDs = [];
    $teilnehmerString = "("; // uids in string (uid, uid...)
    $isPublic = FALSE; // Sichtbarkeit der Reise
    $userIsPart = FALSE; // User teil der Reise
    $reiseDetails = []; // Argumente der Reise
    $username = $_COOKIE["name"]; // Name des Users
    $userID = $_COOKIE["userID"]; // uid des nutzer
    $userRolle = 0;

    //?User Ids und Namen aller User bekommen
    $query = "SELECT userID, name FROM `user` ORDER BY `userID`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $namen[$row["userID"]] = $row["name"];
        array_push($userIDs, $row["userID"]);
    }

    //? Details der Reise bekommen
    $query = "SELECT * FROM `reisen` WHERE `RID` = " . $rid;
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        if($row["isPublic"] == 1){
            $isPublic = TRUE;
        }
        if($row["owner"] === $userID){
            $userIsPart = TRUE;
            $userRolle = 2;
        }
        $teilnehmer[$row["owner"]] = ["uid"=>$row["owner"], "mitwirkend"=>"2", "offeneKos"=>0, "insgKos"=>0];
        // array_push($teilnehmer, array("uid"=>$row["owner"], "mitwirkend"=>"2"));
        array_push($teilnehmerIds, $row["owner"]);
        $teilnehmerString .= $row["owner"] . ",";
        $reiseDetails = array("owner"=>$row["owner"], "name"=>$row["name"], "beschreibung"=>$row["beschreibung"], "erstelltTime"=>$row["erstelltTime"]);
    }

    //? Teilnehmer der Reise und deren Rollen
    $query = "SELECT `userID`, `mitwirkend` FROM `reiseteilnehmer` WHERE `RID` = " . $rid;
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        if($row["userID"] === $userID){
            $userRolle = $row["mitwirkend"] == "1" ? 1 : 0;
            $userIsPart = TRUE;
        }
        $teilnehmer[$row["userID"]] = ["uid"=>$row["userID"], "mitwirkend"=>$row["mitwirkend"], "offeneKos"=>0, "insgKos"=>0];
        // array_push($teilnehmer, array("uid"=>$row["userID"], "mitwirkend"=>$row["mitwirkend"]));
        array_push($teilnehmerIds, $row["userID"]);
        $teilnehmerString .= $row["userID"] . ",";
    }
    $teilnehmerString = substr($teilnehmerString, 0, -1) . ")";

    //? Weiterleiten zur homepage, wenn er nicht teil ist
    if(!$userIsPart and !$isPublic){
        echo "Nicht teil der Reise / Reise nicht öffentlich";
        header("Refresh: 1; url=../index.php");
        exit();
    }

    //? Kategorien
    $kategorien = [];
    // $kategorien = ["0"=>"-"];
    $kategorieAusgaben = [];
    $query = "SELECT * FROM `kategorien`";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $kategorien[$row["KID"]] = $row["name"];
    }

    //? Rechnungen
    $query = "SELECT * FROM `rechnungen` WHERE `RID` = " . $rid . " ORDER BY `time` DESC";
    $result = $mysqli->query($query);
    $rechnungen = [];
    while($row = $result->fetch_assoc()){
        $involved = explode(",", $row["involved"]);
        $hasPayed = explode(",", $row["hasPayed"]);
        $kostenAufteilung = $row["kostenAufteilung"];
        $noch = 0;
        if($kostenAufteilung != NULL){
            $aufteilung = explode(",", $kostenAufteilung);
            $kostenAufteilung = [];
            foreach($aufteilung as $i){
                $asda = explode(":", $i);
                $kostenAufteilung[$asda[0]] = [number_format((float)$asda[1], 2, '.', ''), $asda[0]];
            }
        }
        if($row["samePP"] === "1"){
            $amount = count($involved) - count($hasPayed);
            // print_r($amount . "|" . intval($row["kostenpp"]) . ".");
            $noch = $amount * doubleval($row["kostenpp"]);
        }else{
            foreach($kostenAufteilung as $aufteilung){
                if(!in_array($aufteilung[1], $hasPayed)){
                    $noch += $aufteilung[0];
                }
            }
        }
        $kategorieAusgaben[$row["KID"]] = isset($kategorieAusgaben[$row["KID"]]) ? $kategorieAusgaben[$row["KID"]]+$row["kosten"] : $row["kosten"];
        $noch = number_format((float)$noch, 2, '.', '');
        array_push($rechnungen, array("rechID"=>$row["rechID"], "samePP"=>$row["samePP"], "involved"=>$involved, "hasPayed"=>$hasPayed, "geldAn"=>$row["geldAn"], "kosten"=>number_format((float)$row["kosten"], 2, '.', ''), "kostenpp"=>number_format((float)$row["kostenpp"], 2, '.', ''), "kostenAufteilung"=>$kostenAufteilung, "beglichen"=>$row["beglichen"], "time"=>$row["time"], "beglichenAm"=>$row["beglichenAm"], "noch"=>$noch, "kostenAufteilungString"=>$row["kostenAufteilung"], "kategorie"=>$row["KID"], "notiz"=>$row["notiz"]));
    }

    //? KostenPP
    $query = "SELECT * FROM `rechnungenind` WHERE `RID` = " . $rid . " AND `userID` IN " . $teilnehmerString;
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        if($row["beglichen"] == "0"){
            $teilnehmer[$row["userID"]]["offeneKos"] += $row["betrag"];
        }
        $teilnehmer[$row["userID"]]["insgKos"] += $row["betrag"];
    }

    $query = "SELECT userID, geldAn, SUM(betrag) as betrag FROM `rechnungenind` WHERE `RID` = " . $rid . " AND `beglichen` = 0 GROUP BY `userID`, `geldAn`;";
    $result = $mysqli->query($query);
    $vonGeldAn = [];
    while($row = $result->fetch_assoc()){
        $vonGeldAn[$row["userID"]][$row["geldAn"]] = $row["betrag"];
    }
    foreach(array_keys($vonGeldAn) as $von){
        foreach(array_keys($vonGeldAn) as $an){
            if($von == $an || !isset($vonGeldAn[$von][$an]) || !isset($vonGeldAn[$an][$von])) break;
            if($vonGeldAn[$von][$an] > $vonGeldAn[$an][$von]){
                $vonGeldAn[$von][$an] -= $vonGeldAn[$an][$von];
                unset($vonGeldAn[$an][$von]);
            }else{
                $vonGeldAn[$an][$von] -= $vonGeldAn[$von][$an];
                unset($vonGeldAn[$von][$an]);
            }
        }
    }
}else{ // Nicht eingeloggt
    echo "Nicht eingeloggt";
    header("Refresh: 1; url=../login/login.php"); // Nach einer Sekunde zur login-Seite
    exit(); // Damit das HTML nicht angezeigt wird.
}
?>
<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="UTF-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="reise.css">
        <script>
            var users = [<?php
                $string = "";
                foreach($userIDs as $id){
                    if(!in_array($id, $teilnehmerIds) && $id != $reiseDetails["owner"]){
                        $string .= $id . ",";
                    }
                }
                echo strlen($string) > 0? substr($string, 0, -1) : "";
            ?>]
        </script>
        <script src="../libs/jquery-3.6.4.min.js"></script>
        <script src="../libs/sorttable.js"></script>
        <script src="teilnehmer.js"></script>
        <script src="rechnungen.js"></script>
        <title>Reise: <?php echo $reiseDetails["name"]?></title>
    </head>
    <body>
        <!--//? Navbar -->
        <nav>
            <div class="navLogo">
                <a><?php echo $reiseDetails["name"];?></a>
            </div>
            <div class="beschreibung">
                <a><?php echo $reiseDetails["beschreibung"];?></a>
            </div>
            <div class="logout">
                <form method="post" action="../login/login.php">
                    <button type="submit" name="logout">Ausloggen</button>
                    <br>
                    <a>Eingeloggt als: </a>
                    <br>
                    <a><?php echo $_COOKIE["name"];?></a>
                </form>
            </div>
        </nav>

        <!--//? Teilnehmer -->
        <div class="border">
            <div>
                <a class="title"">Teilnehmer: </a>
                <?php if($userRolle == 2):?>
                    <button id="teilnehmerBearbeiten">Bearbeiten</button>
                <?php endif;?>
            </div>
            <div>
                <ul class="teilnehmerList capitalize" id="teilnehmerList">
                    <li class="teilnehmerLI "><a class="capitalize" id="name<?php echo $reiseDetails["owner"];?>"><?php echo $namen[$reiseDetails["owner"]];?></a> <a class="role owner" id="owner">(owner)</a></li>
                    <?php foreach($teilnehmer as $nehmer):?>
                        <?php if($nehmer["mitwirkend"] != 2):?>
                            <li class="teilnehmerLI" id="teilnehmerLI<?php echo $nehmer["uid"];?>"><a class="capitalize" id="name<?php echo $nehmer["uid"]; ?>"><?php echo $namen[$nehmer["uid"]];?></a> <a id="rolle<?php echo $nehmer["uid"];?>" class="role <?php echo $nehmer["mitwirkend"] == "0" ? "" : "mitarbeiter";?>">(<?php echo $nehmer["mitwirkend"] == "0" ? "Beobachter" : "Mitarbeiter";?>)</a></li>
                        <?php endif;?>
                    <?php endforeach;?>
                </ul>
            </div>
        </div>
        
        <!--//? Geld wer an wen-->
        <div class="border geldvonanDiv capitalize">
            <div>
                <div>
                    <a class="title">Kosten pro Person:</a>
                </div>
                <div class="kostenPP">
                    <table class="kostenPPTab tableBorders sortable">
                        <tr>
                            <th>Person</th>
                            <th class="sorttable_numeric">Noch zu zahlen</th>
                            <th class="sorttable_numeric">Insgesamt Ausgegeben</th>
                        </tr>
                        <?php foreach($teilnehmer as $nehmer):?>
                        <tr>
                            <td><?php echo $namen[$nehmer["uid"]];?></td>
                            <td><?php echo number_format($nehmer["offeneKos"], 2, ".", "");?>€</td>
                            <td><?php echo number_format($nehmer["insgKos"], 2, ".", "");?>€</td>
                        </tr>
                        <?php endforeach;?>
                    </table>
                </div>
            </div>
            <br>
            <div> <!--//? Wer was an wen zahlen muss -->
                <table class="tableBorders geldAn capitalize">
                    <tr>
                        <td class="cell">
                            <a class="cell--topRight">Geld an</a>
                            <a class="cell--bottomLeft">Person</a>
                        </td>
                        <?php foreach($teilnehmerIds as $id):?>
                            <td class="center"><?php echo $namen[$id];?></td>    
                        <?php endforeach;?>
                    </tr>
                    <?php foreach($teilnehmerIds as $person):?>
                        <tr>
                            <td class="center"><?php echo $namen[$person];?></td>
                            <?php foreach($teilnehmerIds as $geldAn):?>
                                <?php if($person == $geldAn || !isset($vonGeldAn[$person][$geldAn])):?>
                                    <td class="<?php echo $person == $geldAn ? "neutral" : "";?>">-</td>
                                <?php else:?>
                                    <td><?php echo number_format($vonGeldAn[$person][$geldAn], 2, ".", "");?>€</td>
                                <?php endif;?>
                            <?php endforeach;?>
                        </tr>
                    <?php endforeach;?>
                </table>
            </div>    
        </div>

        <!--//?Rechnung  -->
        <div class="border rechnungenDiv">
            <div>
                <a class="title">Rechnungen:</a>
                <?php if($userRolle != 0):?>
                    <button id="neueRechnungStart">Neue Rechnung</button>
                <?php endif;?>
            </div>
            <div class="rechnTableDiv">
                <table class="rechnTable sortable" id="rechnungenTable">
                    <tr>
                        <th class="RechnungNotiz">Notiz</th>
                        <th class="sorttable_numeric">Insgesamt</th>
                        <th>An</th>
                        <th class="sorttable_numeric">p.P.</th>
                        <th class="sorttable_nosort">Personen</th>
                        <th>Kategorie</th>
                        <th class="sorttable_nosort">Zeitpunkt</th>
                        <th class="sorttable_nosort">Beglichen Am</th>
                        <?php if($userRolle != 0):?><th class="sorttable_nosort"></th><?php endif;?>
                    </tr>
                    <?php foreach($rechnungen as $rechnung):?>
                    <tr class="<?php echo $rechnung["beglichen"] ? "bezahlt" : "";?>">
                        <td class="RechnungNotiz"><a><?php echo ($rechnung["notiz"] == "" ? "-" : $rechnung["notiz"]);?></a></td>
                        <td><?php echo $rechnung["kosten"];?>€</td>
                        <td class="capitalize"><?php echo $namen[$rechnung["geldAn"]];?></td>
                        <td><?php if($rechnung["samePP"] == "1"){echo $rechnung["kostenpp"] . "€";}else{echo "ind.";}?></td>
                        <td class="capitalize">
                            <ul>
                                <?php foreach($rechnung["involved"] as $involv):?>
                                    <li class="<?php echo in_array($involv, $rechnung["hasPayed"]) ? "strike" : "";?>">
                                    <?php echo $namen[$involv];
                                    if($rechnung["samePP"] == "0"){echo ": " . $rechnung["kostenAufteilung"][$involv][0] . "€";}?>
                                    <?php echo in_array($involv, $rechnung["hasPayed"]) ? "(Bez.)" : "";?>
                                </li>
                                <?php endforeach;?>
                            </ul>
                            <input type="hidden" id="involved<?php echo $rechnung["rechID"];?>" value="<?php echo implode(",", $rechnung["involved"]);?>">
                        </td>
                        <td>
                            <!-- <a><?php echo ($rechnung["notiz"] == "" ? "-" : $rechnung["notiz"]);?></a>
                            <br> -->
                            <a><!--Kategorie: --><?php echo $kategorien[$rechnung["kategorie"]];?></a>
                        </td>
                        <td>
                            <?php echo date("d.m.Y H:i",strtotime($rechnung["time"]));?>
                        </td>
                        <td>
                            <?php echo $rechnung["beglichen"] ? date("d.m.Y H:i",strtotime($rechnung["beglichenAm"])) : "-";?>
                        </td>
                        <?php if($userRolle != 0):?>
                        <td>
                            <button class="begleichen" id="begleichen<?php echo $rechnung["rechID"];?>" <?php echo $rechnung["beglichen"] ? "disabled" : "";?>><img id="begleichen<?php echo $rechnung["rechID"];?>" src="../imgs/icons/check.png" alt=""></button>
                            <button class="edit" id="edit<?php echo $rechnung["rechID"];?>"><img id="edit<?php echo $rechnung["rechID"];?>" src="../imgs/icons/edit.png" alt=""></button>
                            <button class="delete" id="delete<?php echo $rechnung["rechID"];?>"><img id="delete<?php echo $rechnung["rechID"];?>" src="../imgs/icons/redx.png" alt=""></button>
                        </td>
                        <?php endif;?>
                    </tr>
                    <input type="hidden" id="rechnungsDetails<?php echo $rechnung["rechID"];?>" value="<?php echo $rechnung["samePP"] . "|" . $rechnung["rechID"] . "|" . $rechnung["kosten"] . "|" . $rechnung["geldAn"] . "|" . $rechnung["beglichen"] . "|" . implode(",", $rechnung["involved"]) . "|" . implode(",", $rechnung["hasPayed"]) . "|" . $rechnung["time"] . "|" . $rechnung["beglichenAm"] . "|" . ($rechnung["samePP"] ? $rechnung["kostenpp"] : $rechnung["kostenAufteilungString"]) . "|" . $rechnung["kategorie"] . "|" . $rechnung["notiz"];?>">
                    <?php endforeach;?>
                    <!-- samePP, rechID, kosten, geldAn, beglichen, involved, hasPayed, time, beglichenAm, kostenpp/kostenaufteilung -->
                </table>
            </div>
        </div>
        <!--//?Nach Kategorien  -->
        <div class="border">
            <div>
                <a class="title">Ausgaben nach Kategorie</a>
            </div>
            <div class="rechnTableDiv">
                <table class="tableBorders sortable">
                    <tr>
                        <th>Kategorie</th>
                        <th class="sorttable_numeric">Ausgaben</th>
                    </tr>
                    <?php foreach(array_keys($kategorien) as $kategorie):?>
                    <tr>
                        <td><?php echo $kategorien[$kategorie];?></td>
                        <td><?php echo (isset($kategorieAusgaben[$kategorie]) ? number_format((float)$kategorieAusgaben[$kategorie], 2, '.', '') : "0.00");?>€</td>
                    </tr>
                    <?php endforeach;?>
                    <tfoot><tr> <!--in tfoot für die Sortier library -->
                        <td>Insgesamt: </td>
                        <td><?php echo array_sum($kategorieAusgaben);?>€</td>
                    </tr></tfoot>
                </table>
            </div>
        </div>

        <!--//? Templates  -->
<!--//?NR. 0: hinzufügen von neuen Usern(Teilnehmer) -->
        <template>
            <div id="teilnehmerHinzufügen">
                <select name="teilnehmerAdd" id="teilnehmerAdd" class="capitalize">
                    <?php foreach($userIDs as $id):?> 
                        <?php if(!in_array($id, $teilnehmerIds)):?>      
                            <option id="option<?php echo $id;?>" value="<?php echo $id?>"><?php echo $namen[$id];?></option>
                        <?php endif;?>
                    <?php endforeach;?>
                </select>
                <button id="addButton">+</button>
            </div>
        </template>
<!--//?NR. 1: neueRechnung -->
        <template>
            <div class="neueRechnung" id="neueRechnungScreen">
                <div class="headline">
                    <div class="headlineTitle">
                        <a id="NeueRechnungTitle" class="title">Neue Rechnung:</a>
                    </div>
                    <div class="xButtonDiv">
                        <button class="xButton" id="closeNeueRechnung">X</button>
                    </div>
                </div>
                <div class="options">
                    <table class="tableNeueRechnung">
                        <tr>
                            <td>Notiz:</td>
                            <td>
                                <input type="text" id="NotizRechnung" placeholder="Notiz">
                            </td>
                        </tr>
                        <tr>
                            <td>Kategorie:</td>
                            <td>
                                <select id="KategorieRechnung">
                                    <?php foreach(array_keys($kategorien) as $kategorie):?>
                                        <option value="<?php echo $kategorie;?>"><?php echo $kategorien[$kategorie];?></option>
                                    <?php endforeach;?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>Geld an:</td>
                            <td>
                                <select name="geldAn" id="geldAn" class="capitalize">
                                    <?php foreach($teilnehmer as $id):?>
                                        <option value="<?php echo $id["uid"]?>"><?php echo $namen[$id["uid"]];?></option>
                                    <?php endforeach;?>
                                </select>
                            </td>
                        </tr>
                        <tr id="neueRechnungLastTD">
                            <td>Art:</td>
                            <td>
                                <select name="art" id="rechnArt">
                                    <option value="same">Gleiche Kosten p.P.</option>
                                    <option value="indv">Individuelle Kosten</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                </div>
                <button id="rechnSubmit">Submit</button>
                <input type="hidden" id="typeOfRechnungsMenu" value="neue">
                <input type="hidden" id="ridDerEditRechnung" value="">
            </div>
        </template>
<!--//?NR. 2: SameScreen für neueRechnung -->
        <template> 
            <tr id="rechnSame" class="rechnSame">    
                <table>
                    <tr class="rechnSame">
                        <td>Preis Insg.: <input type="number" name="preisInsg" id="preisInsg" class="numIn" value="0.00" min="0">€</td>
                        <td>Preis p.P.: <input type="number" name="preispp" id="preispp" class="numIn" value="0.00" min="0">€</td>
                    </tr>
                </table>
                <table class="rechnZahl rechnSame">
                    <tr class="rechnSame">
                        <th>Wer</th>
                        <th>Hat schon bezahlt</th>
                    </tr>
                    <tr class="rechnSame">
                        <td><button id="rechnAlleTeil" class="alle">Alle</button></td>
                        <td><button id="rechnAlleBez" class="alle">Alle</button></td>
                    </tr>
                    <?php foreach($teilnehmer as $id):?>
                    <tr class="rechnSame">
                        <td>
                            <label class="capitalize"><input type="checkbox" class="rechnCheckBoxTeil" id="rechnTeil<?php echo $id["uid"];?>"><?php echo $namen[$id["uid"]];?></label>
                        </td>
                        <td>
                            <label>Bezahlt:<input type="checkbox" class="rechnCheckBoxBez" id="rechnBez<?php echo $id["uid"];?>"></label>
                        </td>
                    </tr>
                    <?php endforeach;?>
                </table>
            </tr>
        </template>
<!--//?NR. 3: indiv für neueRechnung -->
        <template> 
            <tr id="rechnIndv" class="rechnIndv">
                <table class="rechnIndv">
                    <tr class="rechnIndv">
                        <th>Wer</th>
                        <th>Kosten</th>
                        <th>Hat schon bezahlt</th>
                    </tr>
                    <tr class="rechnIndv">
                        <td><button id="rechnAlleTeil" class="alle">Alle</button></td>
                        <td></td>
                        <td><button id="rechnAlleBez" class="alle">Alle</button></td>
                    </tr>
                    <?php foreach($teilnehmer as $id):?>
                    <tr class="rechnIndv">
                        <td>
                            <label class="capitalize"><input type="checkbox" class="rechnCheckBoxTeil" id="rechnTeil<?php echo $id["uid"];?>"><?php echo $namen[$id["uid"]];?></label>
                        </td>
                        <td class="rechnKostMid">
                            <input type="number" name="preisInsg" id="preisproPers<?php echo $id["uid"];?>" class="numIn" value="0.00" min="0">
                        </td>
                        <td>
                            <label>Bezahlt:<input type="checkbox" class="rechnCheckBoxBez" id="rechnBez<?php echo $id["uid"];?>"></label>
                        </td>
                    </tr>
                    <?php endforeach;?>
                </table>
            </tr>
        </template>
<!--//?NR. 4: indiv unten, unter dem Table anfügen -->
        <template> 
            <div class="rechnIndv">
                <a>Kosten insgesamt: <a id="rechnIndvInsgKost">0.00€</a></a>
            </div>
        </template>
        <!--//? Values für Javascript -->
        <input type="hidden" id="rid" value="<?php echo $rid;?>">
    </body>
</html>