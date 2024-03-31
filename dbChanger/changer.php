<?php
if(isset($_POST["key"]) && isset($_POST["type"])&& $_POST["key"] == "Lalilu"){
    $myfile = fopen("../libs/DBLogin.txt", "r") or die("Unable to open file!");
    $logindata = explode(" ", fread($myfile,filesize("../libs/DBLogin.txt")));
    $mysqli = mysqli_connect($logindata[0], $logindata[1], $logindata[2], $logindata[3]);
    fclose($myfile);
    mysqli_set_charset($mysqli, "utf8mb4");
    if($_POST["type"] == "roleChanger"){//? Rolle eines Teilnehmers Ändern
        $id = isset($_POST["id"]) ? $_POST["id"] : NULL;
        $rid = isset($_POST["rid"]) ? $_POST["rid"] : NULL;
        $newRole = isset($_POST["newRole"]) ? $_POST["newRole"] : NULL;
        if($id != NULL && $newRole != NULL){
            $query = "UPDATE `reiseteilnehmer` SET `mitwirkend` = " . $newRole . " WHERE `reiseteilnehmer`.`RID` = " . $rid . " AND `reiseteilnehmer`.`userID` = " . $id;
            if($mysqli->query($query)){
                http_response_code(200); // Erfolg
            }else{
                http_response_code(400); // Fehlschlag
            }
        }else{
            http_response_code(400);
        }
    }else if($_POST["type"] == "addTeil"){//? Teilnehmer der Reise hinzufügen
        $id = isset($_POST["id"]) ? $_POST["id"] : NULL;
        $rid = isset($_POST["rid"]) ? $_POST["rid"] : NULL;
        if($id != NULL && $rid != NULL){
            $query = "INSERT INTO `reiseteilnehmer` (`RID`, `userID`, `mitwirkend`) VALUES (" . $rid . ", " . $id . ", 0)"; 
            if($mysqli->query($query)){
                http_response_code(200);
            }else{
                http_response_code(400);
            }
        }else{
            http_response_code(400);
        }
    }else if($_POST["type"] == "removeTeil"){//? Teilnehmer entfernen
        $id = isset($_POST["id"]) ? $_POST["id"] : NULL;
        if($id != NULL){
            $query = "DELETE FROM `reiseteilnehmer` WHERE `userID` = " . $id;
            if($mysqli->query($query)){
                http_response_code(200);
            }else{
                http_response_code(400);
            }
        }else{
            http_response_code(400);
        }
    }else if($_POST["type"] == "addRechnung"){//? Rechnung erstellen
        $rid = isset($_POST["rid"]) ? $_POST["rid"] : NULL;
        $isSame = isset($_POST["isSame"]) ? ($_POST["isSame"] == "true" ? "1" : "0") : NULL;
        $involved = isset($_POST["involved"]) ? $_POST["involved"] : NULL;
        $hasPayed = isset($_POST["hasPayed"]) ? $_POST["hasPayed"] : NULL;
        $geldAn = isset($_POST["geldAn"]) ? $_POST["geldAn"] : NULL;
        $insg = isset($_POST["insg"]) ? $_POST["insg"] : NULL;
        $pp = isset($_POST["pp"]) ? $_POST["pp"] : NULL;
        $kategorie = isset($_POST["kategorie"]) ? $_POST["kategorie"] : NULL;
        $notiz = isset($_POST["notiz"]) ? str_replace("'", '"', $_POST["notiz"]) : NULL;
        if($rid != NULL && $isSame != NULL && $involved != NULL && $hasPayed != NULL && $geldAn != NULL && $insg != NULL){
            $userRechn = []; // uid, hastoPay, hasPayed
            $everybodyPayed = TRUE;
            foreach($involved as $i){ // Packt sachen in userRechn und guckt ob alle Bezahlt haben
                $userRechn[$i] = ["uid"=>$i];
                $userRechn[$i]["hasPayed"] = in_array($i, $hasPayed) ? "1" : "0";
                if(!$userRechn[$i]["hasPayed"]){
                    $everybodyPayed = FALSE;
                }
            }
            if(!$isSame){
                foreach($pp as $p){ // Individuelle Kosten werden in userRechn gepackt
                    $userExepnses = explode(":", $p);
                    $userRechn[$userExepnses[0]]["hastoPay"] = $userExepnses[1];
                }
            }
            $query = "INSERT INTO `rechnungen` (`rechID`, `RID`, `samePP`, `involved`, `hasPayed`, `geldAn`, `kosten`, `kostenpp`, `kostenAufteilung`, `beglichen`, `time`, `beglichenAm`, `KID`, `notiz`) VALUES (NULL, '" . $rid . "', '" . $isSame . "', '" . implode(",", $involved) . "', '" . implode(",", $hasPayed) . "', '" . $geldAn . "', '" . $insg . "', " . ($isSame ? $pp : "NULL") . ", ". (!$isSame ? "'" . implode(",", $pp) . "'" : "NULL") . ", '" . ($everybodyPayed ? "1" : "0") . "', CURRENT_TIMESTAMP, " . ($everybodyPayed ? "CURRENT_TIMESTAMP" : "NULL")  . ", " . $kategorie . ", '" . $notiz . "');";
            
            $result = $mysqli->query($query);
            $rechId = 0;
            $query = "SELECT `rechID` FROM `rechnungen` WHERE `rechID` = @@Identity";
            $result = $mysqli->query($query);
            while($row = $result->fetch_assoc()){
                $rechID = $row["rechID"];
            }
            $query = "INSERT INTO `rechnungenind` (`userID`, `RID`, `rechnID`, `geldAn`, `betrag`, `beglichen`, `time`, `beglichenAm`) VALUES";
            foreach($userRechn as $user){
                $query .= " ('" . $user["uid"] . "', '" . $rid . "', '" . $rechID . "', '" . $geldAn . "', " . ($isSame ? $pp : $user["hastoPay"]) . ", '" . $user["hasPayed"] . "', CURRENT_TIMESTAMP, " . ($user["hasPayed"] == "1" ? "CURRENT_TIMESTAMP" : "NULL") . "),";
            }
            $query = rtrim($query, ",") . ";";
            $mysqli->multi_query($query);
        }
    }else if($_POST["type"] == "removeRechnung"){//? Rechnung löschen
        $rid = isset($_POST["rid"]) ? $_POST["rid"] : NULL;
        $rechID = isset($_POST["rechID"]) ? $_POST["rechID"] : NULL;
        $involved = isset($_POST["involved"]) ? "(" . $_POST["involved"] . ")" : NULL;
        if($rid != NULL && $rechID != NULL AND $involved != NULL){
            $query = "DELETE FROM `rechnungen` WHERE `rechID` = " . $rechID . " AND `RID` = " . $rid . "; DELETE FROM `rechnungenind` WHERE `rechnID` = " . $rechID . " AND `RID` = " . $rid . " AND `userID` IN " . $involved . ";";
            if($mysqli->multi_query($query)){
                http_response_code(200);
            }else{
                http_response_code(400);
            }
        }
    }else if($_POST["type"] == "begleichRechnung"){//? Rechnung begleichen
        $rid = isset($_POST["rid"]) ? $_POST["rid"] : NULL;
        $rechID = isset($_POST["rechID"]) ? $_POST["rechID"] : NULL;
        $involved = isset($_POST["involved"]) ? $_POST["involved"] : NULL;
        if($rid != NULL && $rechID != NULL AND $involved != NULL){
            $query = "UPDATE `rechnungen` SET `beglichen` = '1', `beglichenAm` = CURRENT_TIMESTAMP, `hasPayed` = '" . $involved . "' WHERE `rechID` =  " . $rechID . " AND `RID` = " . $rid . "; UPDATE `rechnungenind` SET `beglichen` = '1', `beglichenAm` = CURRENT_TIMESTAMP WHERE `rechnID` =  " . $rechID . " AND `RID` = " . $rid . " AND `userID` IN (" . $involved . ")";
            if($mysqli->multi_query($query)){
                http_response_code(200);
            }else{
                http_response_code(400);
            }
        }
    }else if($_POST["type"] == "editRechnung"){//? Rechnung editieren
        $geldAn = $_POST["geldAn"]; // int
        $hasPayed = $_POST["hasPayed"]; // Array
        $involved = $_POST["involved"]; // Array
        $kategorie = $_POST["kategorie"]; // int
        $kosten = $_POST["kosten"]; // float
        $notiz = $_POST["notiz"]; // string
        $pp = $_POST["pp"]; // float oder araray (Wenn samePP == 1)
        $rechID = $_POST["rechID"]; // int
        $rid = $_POST["rid"]; // int
        $givenCols = explode("|", $_POST["givenCols"]); // Array
        $samePP = $_POST["samePP"]; // 0 oder 1
        $usersToAdd = $_POST["usersToAdd"] == "" ? [] : explode(",", $_POST["usersToAdd"]); // Array
        $usersToRemove = $_POST["usersToRemove"] == "" ? [] : explode(",", $_POST["usersToRemove"]); // Array
        $changeAbleData = ["geldAn"=>$geldAn, "hasPayed"=>implode(",",$hasPayed), "involved"=>implode(",",$involved), "KID"=>$kategorie, "kosten"=>$kosten, "notiz"=>$notiz, "pp"=>($samePP == "0" ? implode(",",$pp) : $pp), "samePP"=>$samePP];

        $query = "UPDATE `rechnungen` SET ";
        foreach($givenCols as $cols){ // Ändert alle geänderten Sachen der Rechnung in der Main Rechnung
            if($cols == "pp"){
                $query .= "kostenpp = " . ($samePP == "1" ? $pp : "NULL") . ", kostenAufteilung = " . ($samePP == "1" ? "NULL" : "'" . implode(",", $pp) . "'") . ",";
            }else{
                $query .= $cols . "='" . $changeAbleData[$cols] . "',";
            }
        }
        // Überprüfen, ob die Rechnung nun beglichen ist und beglichen und beglichenAm ändern
        if(in_array("hasPayed", $givenCols) || in_array("involved", $givenCols)){// Überprüft, ob die Teilnehmer, oder die die bezahlt haben sich verändert
            $beglichen = TRUE;
            print_r($involved);
            print_r($hasPayed);
            foreach($involved as $inv){
                if(!in_array($inv, $hasPayed)){
                    $beglichen = FALSE;
                }
            }
            if($beglichen){
                $query .= "`beglichen` = 1, `beglichenAm` = COALESCE(`beglichenAm`, CURRENT_TIMESTAMP),"; // Coalesce ändert den Wert nur, wenn er zuvor NULL war.
            }else{
                $query .= "`beglichen` = 0, `beglichenAm` = NULL,";
            }
        }
        $query = substr($query, 0, -1) .  " WHERE `rechID` = " . $rechID . " AND `RID` = " . $rid . "; ";
        // Individuelle Rechnungen müssen geändert werden wenn diese Zeilen verändert wurden: geldAn, betrag, hasPayed (beglichenAm)
        $userData = [];
        foreach($involved as $user){
            $userData[$user] = ["beglichen" => (in_array($user, $hasPayed) ? "1" : "0")];
        }
        if(in_array("geldAn", $givenCols) || in_array("pp", $givenCols) || in_array("hasPayed", $givenCols)){
            $usersToEdit = array_diff($involved, $usersToRemove, $usersToAdd);
            print_r(gettype($samePP));
            if($samePP === "0"){
                foreach($pp as $user){
                    $ppData = explode(":", $user);
                    $userData[$ppData[0]] = ["betrag" => $ppData[1], "beglichen" => (in_array($ppData[0], $hasPayed) ? "1" : "0")];
                }
            }
            print_r($userData);
            foreach($usersToEdit as $user){
                $query .= "UPDATE `rechnungenind` SET `geldAn` = " . $geldAn . ", `betrag` = " . ($samePP == "0" ? $userData[$user]["betrag"] : $pp) . ", `beglichen` = " . $userData[$user]["beglichen"] . ", `beglichenAm` = " . ($userData[$user]["beglichen"] == "1" ? "COALESCE(`beglichenAm`, CURRENT_TIMESTAMP)" : "NULL") . " WHERE `userID` = " . $user . " AND `RID` = " . $rid . " AND `rechnID` = " . $rechID . ";";
            }
        }
        // User entfernen
        foreach($usersToRemove as $user){
            $query .= "DELETE FROM `rechnungenind` WHERE `userID` = " . $user . ", `RID` = " . $rid . ", `rechnID` = " . $rechID . ";";
        }
        //User hinzufügen
        foreach($usersToAdd as $user){
            $query .= "INSERT INTO `rechnungenind` (`userID`, `RID`, `rechnID`, `geldAn`, `betrag`, `beglichen`, `beglichenAm`) VALUES (" . $user . ", " . $rid . ", " . $rechID . ", " . $geldAn . ", " . ($samePP == "0" ? $userData[$user]["betrag"] : $pp) . ", " . $userData[$user]["beglichen"] . ", " . ($userData[$user]["beglichen"] == "1" ? "COALESCE(`beglichenAm`, CURRENT_TIMESTAMP)" : "NULL") . ");";
        }

        print_r($query);
        $mysqli->multi_query($query);
    }else if($_POST["type"] == "editReise"){
        $rid = isset($_POST["rid"]) ? $_POST["rid"] : NULL;
        $name = isset($_POST["name"]) ? $_POST["name"] : NULL;
        $beschreibung = isset($_POST["beschreibung"]) ? $_POST["beschreibung"] : NULL;
        $isPublic = isset($_POST["isPublic"]) ? $_POST["isPublic"] : NULL;
        if($name || $beschreibung || $isPublic){
            $query = "UPDATE `reisen` SET " . (!is_null($name) ? ("`name` = '" . $name . "'" . ((!is_null($beschreibung) || !is_null($isPublic)) ? ", " : "")) : "") .  (!is_null($beschreibung) ? ("`beschreibung` = '" . $beschreibung . "'" . ((!is_null($isPublic)) ? ", " : "")) : "") . (!is_null($isPublic) ? "`isPublic` = " . $isPublic : "") . " WHERE `reisen`.`RID` = " . $rid;
            print_r($query);
            if($mysqli->multi_query($query)){
                http_response_code(200);
            }else{
                http_response_code(400);
            }
        }
    }
}