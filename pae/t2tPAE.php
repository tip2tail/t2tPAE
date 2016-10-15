<?php
/**
 * Created by PhpStorm.
 * User: Mark
 * Date: 10/10/2016
 * Time: 19:31
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

session_start();
require_once "lib.php";
require_once "t2tPAE_Settings.php";

class t2tPAE {

    /** @var t2tPAE_Settings */
    public $Settings = null;
    public $Key = "";
    public $IsLoggedIn = false;

    /** @var resource */
    private $Context = null;
    private $Cookies = false;

    /**
     * t2tPAE constructor.
     */
    public function __construct() {
        $this->Context = stream_context_create();
    }

    /**
     * Will log the Piwigo user in based ont he username/password stored in the settings.
     * @return bool|string
     */
    public function Login() {
        if ($this->Settings->Username == "") {
            SetMessage("Login failure. No username set in settings.", false);
            return false;
        }

        $dictQuery = [
            "username" => $this->Settings->Username,
            "password" => $this->Settings->Password
        ];
        $oResponse = $this->PostWebServiceRequest("pwg.session.login", $dictQuery);
        if ($oResponse->result !== true) {
            SetMessage("Login failure. Album delete disabled.", false);
            return false;
        }

        $oResponse = $this->PostWebServiceRequest("pwg.session.getStatus");
        if ($oResponse->result->username != $this->Settings->Username) {
            SetMessage("Login failure - unable to obtain token. Album delete disabled.", false);
            return false;
        }

        // All OK
        $this->Key = $oResponse->result->pwg_token;

        $this->IsLoggedIn = true;
        return true;
    }

    /**
     * Saves the settings to the pae.conf file.
     */
    public function SaveSettings() {
        $sSettings = base64_encode(serialize($this->Settings));
        file_put_contents("pae.conf", $sSettings);
    }

    /**
     * Validates that the settings loaded from pae.conf match the format expected.
     * @param stdClass $oConfigFileSettings
     * @return bool
     */
    private function ValidateSettings($oConfigFileSettings) {
        $bValid = false;
        do {
            if (!property_exists($oConfigFileSettings, 'PiwigoURL')) {
                break;
            }
            if (!property_exists($oConfigFileSettings, 'Username')) {
                break;
            }
            if (!property_exists($oConfigFileSettings, 'Password')) {
                break;
            }
            if (!property_exists($oConfigFileSettings, 'DocumentRoot')) {
                break;
            }
            if (!property_exists($oConfigFileSettings, 'PiwigoPath')) {
                break;
            }
            // All the required properties are on the object
            $bValid = true;
        } while (false);
        return $bValid;
    }

    /**
     * Loads the settings from pae.conf.
     * @return bool
     */
    public function LoadSettings() {
        if (!file_exists("pae.conf")) {
            SetMessage("Please setup the config before use", true);
            return false;
        }
        $sSettings = file_get_contents("pae.conf");
        if ($sSettings === false) {
            SetMessage("Unable to load settings from config file", false);
            return false;
        }
        $oSettings = unserialize(base64_decode($sSettings));
        $this->Settings = $oSettings;
        if (!$this->ValidateSettings($oSettings)) {
            SetMessage("Unable to validate settings loaded from config file", false);
            return false;
        }
        return true;
    }

    /**
     * Obtains a valid header for the
     * @return string
     */
    private function GetCookiesForHeader() {
        $sReturn = "Cookie: " . implode("; ", $this->Cookies);
        return $sReturn;
    }

    /**
     * Stores the HTTP cookies from the file_get_contents calls.
     * @param array $arHeaders
     * @return string
     */
    private function StoreCookies($arHeaders) {
        $sCookies = "";
        $sValue = "";
        foreach ($arHeaders as $sHeader) {
            $sUpper = strtoupper($sHeader);
            if (strpos($sUpper, "SET-COOKIE:") === false) {
                continue;
            }
            // Found a cookie - lets get the value...
            $sValue .= trim(substr($sHeader, 11)) . ";";
        }
        $arCookies = explode(";", $sValue);
        foreach ($arCookies as $iIndex => $sCookie) {
            $arCookies[$iIndex] = trim($sCookie);
        }
        return $arCookies;
    }

    /**
     * Executes a call to the PWG web api.
     * @param string $sMethod
     * @param array $dictQuery
     * @param bool $bDebug
     * @return stdClass
     */
    public function PostWebServiceRequest($sMethod, $dictQuery = array(), $bDebug = false) {
        $sPostURL = $this->Settings->PiwigoURL . "ws.php?format=json";
        $dictQuery["method"] = $sMethod;
        $oPostData = http_build_query($dictQuery);
        $oHTTPOpts = array('http' =>
            array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => $oPostData
            )
        );

        // Cookie time?
        if ($this->Cookies !== false) {
            $arHTTP = $oHTTPOpts["http"];
            $arHTTP["header"] = $arHTTP["header"] . "\r\n" . $this->GetCookiesForHeader();
            $oHTTPOpts["http"] = $arHTTP;
        }

        stream_context_set_option($this->Context, $oHTTPOpts);
        $sResult = file_get_contents($sPostURL, false, $this->Context);
        if ($this->Cookies === false) {
            $this->Cookies = $this->StoreCookies($http_response_header);
        }
        $oObject = json_decode($sResult);

        if ($bDebug) {
            // We need to debug this?  OK - Dump everything...
            Dump($sPostURL, $dictQuery, $sResult, $oObject, $http_response_header);
        }

        return $oObject;
    }

}
