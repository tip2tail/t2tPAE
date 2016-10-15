<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 10/10/2016
 * Time: 21:15
 */
/*
MIT License

Copyright © 2016 tip2tail Ltd

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
 */

require_once "t2tPAE.php";

$bOK = false;
$sReturn = "index.php";
do {

    if (!array_key_exists("fldAction", $_POST)) {
        break;
    }

    switch ($_POST["fldAction"]) {
        case "save-settings":
            $sReturn = "setup.php";
            if (UpdateSettings($_POST)) {
                SetMessage("Settings saved OK", true);
            } else {
                SetMessage("Unable to save settings", false);
            }
            break;
        case "create-zip":
            $sReturn = "json";
            $arJSON = [
                "result" => "OK"
            ];
            if (CreateZIP($_POST["album"])) {
                SetMessage("ZIP file generated for album " . CreateLink($_POST["album"]), true);
            } else {
                SetMessage("Unable to generate ZIP file", false);
            }
            break;
        case "delete-zip":
            $sReturn = "json";
            $arJSON = [
                "result" => "OK"
            ];
            if (DeleteZip($_POST["album"])) {
                SetMessage("ZIP file deleted for album " . CreateLink($_POST["album"]), true);
            } else {
                SetMessage("Unable to delete ZIP file", false);
            }
            break;
        case "delete-album":
            $sReturn = "json";
            $arJSON = [
                "result" => "OK"
            ];
            if (DeleteAlbum($_POST["album"])) {
                SetMessage("Album " . $_POST["album"] . " deleted", true);
            } else {
                SetMessage("Unable to delete album " . CreateLink($_POST["album"]), true);
            }
            break;
    }

    $bOK = true;

} while (false);

if ($sReturn == "json") {
    die(json_encode($arJSON));
}

header("Location: " . $sReturn . "\r\n");
