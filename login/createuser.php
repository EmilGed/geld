<?php
if(isset($_POST["submit"])){
    $name = isset($_POST["name"]) ? str_replace("'", '"', $_POST["name"]) : NULL;
    $pass = isset($_POST["pass"]) ? password_hash($_POST["pass"], PASSWORD_DEFAULT) : NULL;
    if($name != NULL and $pass != NULL){
        $myfile = fopen("../libs/DBLogin.txt", "r") or die("Unable to open file!");
        $logindata = explode(" ", fread($myfile,filesize("../libs/DBLogin.txt")));
        $mysqli = mysqli_connect($logindata[0], $logindata[1], $logindata[2], $logindata[3]);
        fclose($myfile);
        mysqli_set_charset($mysqli, "utf8mb4");
        $query = "INSERT INTO `user` (name, password) VALUES ('" . $name . "', '" . $pass . "');";
        $result = $mysqli->query($query);
        echo "<script>console.log('User erstellt')</script>";
    }
}
//! User erstellt Nachricht
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
            <h1>User erstellen</h1>
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
            <div id="button">
                <button type="submit" name="submit">Erstellen</button>
            </div>
        </form>
    </div>
</body>
</html>