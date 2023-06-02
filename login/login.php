<?php
function passwordRight($name, $pass){
    global $userID;
    $mysqli = mysqli_connect('localhost', 'datalogger', 'hallo123', "geld");
    mysqli_set_charset($mysqli, "utf8mb4");
    $query = "SELECT `password`, `userID` FROM `user` WHERE `name` = '" . str_replace("'", '"', $name) . "'";
    $result = $mysqli->query($query);
    while($row = $result->fetch_assoc()){
        if(password_verify($pass, $row["password"])){
            $userID = $row["userID"];
            return true;
        } 
    }
    return false;
}


$cookieLen = 60*60*24*30; // 1 Monat in sekunden

if(isset($_POST["logout"])){ // User ausloggen
    setcookie("name", "", time()-1, "/");
    setcookie("logged_in", True, time()-1, "/");
    setcookie("key", "IchEsseKinder", time()-1, "/");
}

if(isset($_COOKIE["logged_in"]) and isset($_COOKIE["key"]) and $_COOKIE["key"] == "IchEsseKinder" and !isset($_POST["logout"])){ // Bereits Eingeloggt
    echo "Bereits eingeloggt";
    header("refresh: 1; url=../index.php");
}

if(isset($_POST["submit"])){ // Auf Anmelden gedrückt
    $name = isset($_POST["name"]) ? $_POST["name"] : NULL;
    $pass = isset($_POST["pass"]) ? $_POST["pass"] : NULL;
    if($name != NULL and $pass != NULL){
        $userID = "";
        if(passwordRight($name, $pass)){
            echo "<script>console.log('ashjdahfg')</script>";
            setcookie("name", $name, time()+$cookieLen, "/");
            setcookie("logged_in", True, time()+$cookieLen, "/");
            setcookie("key", "IchEsseKinder", time()+$cookieLen, "/");
            setcookie("userID", $userID, time()+$cookieLen, "/");
            header("Location: ../index.php");
        }else{
            echo "<script>console.log('Passwort falsch')</script>";
        }
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Login</title>
</head>
<body>
    <div id="wrapper">
        <div id="headline">
            <h1>Login</h1>
        </div>
        <form method="post">
            <table>
                <tr>
                    <td>Name: </td>
                    <td>
                        <input type="text" placeholder="Name" name="name" id="name">
                    </td>
                </tr>
                <tr>
                    <td>Passwort: </td>
                    <td>
                        <input type="password" placeholder="Passwort" name="pass" id="pass">
                    </td>
                </tr>
            </table>
            <a href="changePass.php">Passwort ändern...</a>
            <div id="button">
                <button type="submit" name="submit" id="submit">Anmelden</button>
            </div>
        </form>
    </div>
</body>
</html>