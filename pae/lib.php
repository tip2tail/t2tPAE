<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 10/10/2016
 * Time: 19:33
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

/**
 * Debug routine - Dump and Die.
 * @param {multiple}
 */
function DND() {
    echo "\n\n\n\n<pre>\n=======================\nDump And Die\n=======================\n\nRemember: Ignore the first array!\n\n";
    var_dump(func_get_args());
    die();
}

/**
 * Debug routine - Dump (no die).
 * @param {multiple}
 */
function Dump() {
    echo "\n\n\n\n<pre>\n=======================\nDump\n=======================\n\nRemember: Ignore the first array!\n\n";
    var_dump(func_get_args());
    echo "\n\n</pre>\n";
}

/**
 * Updates then saves the settings into the config file.
 * @param array $dictPost
 * @return bool
 */
function UpdateSettings($dictPost) {
    $oPAE = new t2tPAE();
    $oPAE->Settings->Username = $dictPost["fldUsername"];
    $oPAE->Settings->Password = $dictPost["fldPassword"];
    $oPAE->Settings->PiwigoURL = $dictPost["fldURL"];
    $oPAE->Settings->DocumentRoot = $dictPost["fldRoot"];
    if ($dictPost["fldRoot"] == "") {
        $oPAE->Settings->DocumentRoot = $_SERVER["DOCUMENT_ROOT"];
    }
    if ($dictPost["fldPath"] == "") {
        $sPath = FindPiwigoInstall($oPAE->Settings->DocumentRoot);
        $oPAE->Settings->PiwigoPath = ($sPath !== false ? $sPath : "Error");
    }
    $oPAE->SaveSettings();
    return true;
}

/**
 * Returns the base URL for this website.
 * @return string
 */
function GetBaseURL() {
    $sProtocol = (IsSecure() ? "http://" : "http://");
    return $sProtocol . $_SERVER["HTTP_HOST"] . "/";
}

/**
 * Is this connection secure?.
 * @return bool
 */
function IsSecure() {
    return
        (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || $_SERVER['SERVER_PORT'] == 443;
}

/**
 * Locates Piwigo based on the document root.
 * @param string $sRoot
 * @return bool|string
 */
function FindPiwigoInstall($sRoot = "") {

    if ($sRoot == "") {
        $sRoot = $_SERVER["DOCUMENT_ROOT"];
    }

    $oRDI = new RecursiveDirectoryIterator($sRoot);
    $oRII = new RecursiveIteratorIterator($oRDI, RecursiveIteratorIterator::SELF_FIRST);
    $arReadme = array();

    foreach ($oRII as $oPath) {
        // Skip the stuff we dont need
        if ($oRII->isDot()) {
            continue;
        }
        if ($oRII->isDir()) {
            continue;
        }
        $sPath = $oRII->getSubPathname();
        if (basename($sPath) == "README.md") {
            $arReadme[] = $sPath;
        }
    }

    $sReturn = false;

    // Now we check each README.md and find the expected string
    $EXPECTED_README = '[![Piwigo](http://piwigo.org/screenshots/logos/piwigo@280.png)](http://piwigo.org)';
    foreach ($arReadme as $sReadmePath) {
        $sReadThis = $sRoot . "/" . $sReadmePath;
        $sLine = trim(fgets(fopen($sReadThis, 'r')));
        $sLine = substr($sLine, 0, 82);
        if (strtoupper($sLine) == strtoupper($EXPECTED_README)) {
            $sReturn = dirname($sReadThis);
            break;
        }
    }
    return $sReturn;
}

/**
 * Returns a file path in the expected format for adding to the ZIP.
 * @param string $sURL
 * @param t2tPAE_Settings $oSettings
 */
function FormatForAdditionToZIP($sURL, $oSettings) {
    $sPath = str_replace($oSettings->PiwigoURL, "", $sURL);
    $sPath = $oSettings->PiwigoPath . "/" . $sPath;
    return $sPath;
}

/**
 * Generates a ZIP file with the photos from a given album ID.
 * @param int $iAlbum
 * @return bool
 */
function CreateZIP($iAlbum) {
    $oPAE = new t2tPAE();
    $oPAE->LoadSettings();

    $dictQuery = [
        "cat_id" => $iAlbum,
        "per_page" => 500
    ];
    $oRequest = $oPAE->PostWebServiceRequest("pwg.categories.getImages", $dictQuery);

    $arImages = $oRequest->result->images;
    $arFiles = array();
    foreach ($arImages as $oImage) {
        $arFiles[] = FormatForAdditionToZIP($oImage->element_url, $oPAE->Settings);
    }

    // Now build a ZIP
    $oZip = new ZipArchive();
    $bResult = $oZip->open("zips/" . $iAlbum . ".zip", ZipArchive::CREATE);
    if ($bResult === true) {
        // Add files
        for ($iX = 0; $iX < count($arFiles); $iX++) {
            $sLocalFileName = basename($arFiles[$iX]);
            $oZip->addFile($arFiles[$iX], $sLocalFileName);
        }
        $oZip->close();
        return true;
    } else {
        return false;
    }
}

/**
 * Deletes an album on the Piwigo gallery.
 * @param int $iAlbum
 * @return bool
 */
function DeleteAlbum($iAlbum) {
    $oPAE = new t2tPAE();
    $bSettings = $oPAE->LoadSettings();
    $bLogin = $oPAE->Login();
    $dictQuery = [
        "category_id" => $iAlbum,
        "pwg_token" => $oPAE->Key
    ];
    $oRequest = $oPAE->PostWebServiceRequest("pwg.categories.delete", $dictQuery);
    return true;
}

/**
 * Returns the current t2tPAE directory.
 * @return string
 */
function GetScriptDir() {
    return __DIR__;
}

/**
 * Deletes a given album ZIP file.
 * @param int $iAlbum
 * @return bool
 */
function DeleteZip($iAlbum) {
    $sPath = GetScriptDir() . "/" . "zips/" . $iAlbum . ".zip";
    $bUnlink = unlink($sPath);
    return $bUnlink;
}

/**
 * Returns true if the ZIP exists.
 * @param int $iAlbumID
 * @return bool
 */
function GetZIPStatus($iAlbumID) {
    if (!file_exists("zips")) {
        mkdir("zips");
        chmod("zips", 0775);
    }
    if (!is_dir("zips")) {
        return false;
    }
    if (file_exists("zips/" . $iAlbumID . ".zip")) {
        return true;
    }
    return false;
}

/**
 * Clears the SESSION message.
 */
function ClearMessage() {
    $_SESSION["MSG_MESSAGE"] = "";
    $_SESSION["MSG_SUCCESS"] = null;
    unset($_SESSION["MSG_MESSAGE"]);
    unset($_SESSION["MSG_SUCCESS"]);
}

function CreateLink($iAlbum) {
    return '<a href="#pae-album-' . $iAlbum . '">' . $iAlbum . '</a>';
}

/**
 * Sets a SESSION message.
 * @param string $sMessage
 * @param bool $bSuccess
 */
function SetMessage($sMessage, $bSuccess) {
    $_SESSION["MSG_MESSAGE"] = $sMessage;
    $_SESSION["MSG_SUCCESS"] = $bSuccess;
}

/**
 * Returns any message stored in the SESSION.
 * @return array|bool
 */
function GetMessage() {
    if (array_key_exists("MSG_MESSAGE", $_SESSION)) {
        $arReturn = [
            $_SESSION["MSG_MESSAGE"],
            $_SESSION["MSG_SUCCESS"]
        ];
        // Clear the message now that it has been used.
        ClearMessage();
        return $arReturn;
    } else {
        return false;
    }
}
