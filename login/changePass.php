<?php
if(isset($_POST["submit"])){
    $name = isset($_POST["name"]) ? str_replace("'", '"', $_POST["name"]) : NULL;
    $passA = isset($_POST["passA"]) ? $_POST["passA"] : NULL;
    $passN = isset($_POST["passN"]) ? password_hash($_POST["passN"], PASSWORD_DEFAULT) : NULL;
    if($name != NULL and $passA != NULL and $passN != NULL){
        $mysqli = mysqli_connect('localhost', 'datalogger', 'hallo123', "geld");
        mysqli_set_charset($mysqli, "utf8mb4");

        $query = "SELECT password FROM `user` WHERE `name` = '" . $name . "'";
        $result = $mysqli->query($query);
        $query = "";
        while($row = $result->fetch_assoc()){
            if(password_verify($passA, $row["password"])){
                $query = "UPDATE `user` SET `password` = '" . $passN . "'";
            }
        }
        if($query != ""){
            if($mysqli->query($query)){
                echo "<script>console.log('erfolg')</script>";
                // Cookies weg :)
                setcookie("name", $name, time()-1, "/");
                setcookie("logged_in", True, time()-1, "/");
                setcookie("key", "IchEsseKinder", time()-1, "/");
                header("Location: login.php");
            }
        }
    }
}
//! Nachrichten, wenn was falsch war
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <title>Document</title>
</head>
<body>
    <div id="wrapper">
        <div id="headline">
            <h1>Passowort Ändern</h1>
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
                    <td>Altes Passwort: </td>
                    <td>
                        <input type="password" placeholder="Passwort" name="passA" id="passA">
                    </td>
                </tr>
                <tr>
                    <td>neues Passwort: </td>
                    <td>
                        <input type="password" placeholder="Passwort" name="passN" id="passN">
                    </td>
                </tr>
            </table>
            <div id="button">
                <button type="submit" name= "submit">Ändern</button>
            </div>
        </form>
    </div>
</body>
</html>