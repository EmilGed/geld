<?php
function getPictureNumber($rid){
    return $rid % 10;
}

if(isset($_COOKIE["logged_in"]) and isset($_COOKIE["key"]) and $_COOKIE["key"] == "IchEsseKinder"){ // Eingeloggt

    $mysqli = mysqli_connect('localhost', 'datalogger', 'hallo123', "geld");
    mysqli_set_charset($mysqli, "utf8mb4");

    if(isset($_POST["submit"])){
        print_r($_POST);
        $uidP = isset($_POST["uid"]) ? $_POST["uid"] : NULL;
        $name = isset($_POST["name"]) ? str_replace("'", '"', $_POST["name"]) : NULL;
        $desc = isset($_POST["beschreibung"]) ? str_replace("'", '"', $_POST["beschreibung"]) : NULL;
        $isPublic = isset($_POST["visibility"]) ? $_POST["visibility"] : NULL;
        if($uidP != NULL and $name != NULL and $desc != NULL and $isPublic != NULL){
            $query = "INSERT INTO `reisen` (`owner`, `name`, `beschreibung`, `isPublic`, `abgeschlossen`) VALUES (" . $uidP . ", '" . $name . "', '" . $desc . "', " . ($_POST["visibility"] == "private" ? "0" : "1") . ", 0)";
            $mysqli->query($query);
            $query = "SELECT `RID` FROM `reisen` WHERE `RID` = @@Identity";
            $rid = "";
            $result = $mysqli->query($query);
            while($row = $result->fetch_assoc()){
                $rid = $row["RID"];
            }
            print_r($query);
            header("Location: reisen/reise.php?rid=" . $rid);
        }
    }

    $query = "SELECT userID FROM `user` WHERE `name` = '" . $_COOKIE['name'] . "';";
    $result = $mysqli->query($query);
    
    $uid = "";
    while($row = $result->fetch_assoc()){
        $uid = $row["userID"];
    }

    $reisen = [];
    $teilReise = "(";
    
    $query = "SELECT * FROM `reiseteilnehmer` WHERE `userID` = " . $uid . ";";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        $teilReise .= $row['RID'] . ",";
    }
    $teilReise = substr($teilReise, 0, -1) . ")";

    $query = "SELECT * FROM `reisen` WHERE `owner` = " . $uid;
    if($teilReise != ")") $query .= " OR `RID` IN " . $teilReise . ";";
    $owner = [];
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        array_push($owner, $row["owner"]);
        array_push($reisen, array("rid"=>$row["RID"], "owner"=>$row["owner"], "name"=>$row["name"], "desc"=>$row["beschreibung"], "isPublic"=>$row["isPublic"], "abgeschlossen"=>$row["abgeschlossen"], "abgeschlossenTime"=>$row["abgeschlossenTime"], "erstelltTime"=>$row["erstelltTime"]));
    }

    if(count($owner) > 0){
        $query = "SELECT * FROM `user` WHERE `userID` IN (";
        foreach($owner as $userID){
            $query .= $userID . ",";
        }
        $query = substr($query, 0,-1) . ")";
        $result = $mysqli->query($query);
        $owner = [];
        while($row = $result->fetch_assoc()){
            $owner[$row["userID"]] = $row["name"];
        }
    }
    // print_r($ownedReise);

}else{ // Nicht eingeloggt
    echo "Nicht eingeloggt";
    header("Refresh: 1; url=login/login.php"); // Nach einer Sekunde zur login-Seite
    exit(); // Damit das HTML nicht angezeigt wird.
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <script src="libs/jquery-3.6.4.min.js"></script>
    <script src="index.js"></script>
    <title>Geld</title>
</head>
<body>
    <nav>
        <div class="navLogo">
            <a>Geld für Reisen</a>
        </div>
        <div class="logout">
            <form method="post" action="login/login.php">
                <button type="submit" name="logout">Ausloggen</button>
                <br>
                <a>Eingeloggt als: </a>
                <br>
                <a><?php echo $_COOKIE["name"];?></a>
            </form>
        </div>
    </nav>
    <?php foreach($reisen as $reise):?>
    <div class="container" onclick="location.assign('reisen/reise.php?rid=<?php echo $reise["rid"];?>')">
        <div class="pic">
            <img class="logo" src="imgs/reisePic/<?php echo getPictureNumber($reise["rid"]);?>.jpg" alt="cooles Bild einer Reise">
        </div>
        <div class="text">
            <div class="name">
                <a><?php echo $reise["name"];?></a>
            </div>
            <div class="teilnehmer">
                <a>Von: <?php echo $owner[$reise["owner"]];?></a>
            </div>
            <div class="desc">
                <a><?php echo $reise["desc"];?></a>
            </div>
        </div>
    </div>
    <?php endforeach;?>
    <div class="new">
        <button id="newButton">
            <img src="imgs/icons/plus.png" alt="+" class="plus">
            Neue Reise
        </button>
    </div>
<!--//? Neue Rechnung  -->
    <template>
        <div class="neueReise" id="neueReise">
            <div class="nrtop">
                <div class="nrHeadline">
                    <h1>Neue Reise erstellen</h1>
                </div>
                <div class="xButtonDiv">
                    <button class="xButton" id="close">X</button>
                </div>
            </div>
            <div class="nrText">
                <form method="post">
                    <table>
                        <tr>
                            <td>Name: </td>
                            <td>
                                <input type="text" name="name" id="name" placeholder="Name deiner Reise">
                            </td>
                        </tr>
                        <tr>
                            <td><a class="top">Beschreibung: </a></td>
                            <td>
                                <textarea name="beschreibung" id="beschreibung" cols="20" rows="3" placeholder="Beschreibung deiner Reise"></textarea>
                            </td>
                        </tr>
                        <tr>
                            <td>Sichtbarkeit: </td>
                            <td>
                                <select name="visibility" id="visibility">
                                    <option value="private" selected >Privat</option>
                                    <option value="public" >Öffentlich</option>
                                </select>
                            </td>
                        </tr>
                    </table>
                    <div class="button">
                        <button type="submit" name="submit">Erstellen</button>
                    </div>
                    <input type="hidden" name="uid" value="<?php echo $uid;?>">
                </form>
            </div>
        </template>
    </div>
</body>
</html>