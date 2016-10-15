<?php
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
$oPAE = new t2tPAE();
if ($oPAE->LoadSettings() === false) {
    $oSettings = new t2tPAE_Settings();
    $oSettings->PiwigoURL = GetBaseURL();
} else {
    $oSettings = $oPAE->Settings;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Piwigo Album Exporter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Bootstrap core CSS -->
    <link href="http://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/css/bootstrap.min.css" rel="stylesheet" media="screen">

    <!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!--[if lt IE 9]>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/html5shiv/3.7.2/html5shiv.js"></script>
    <script src="http://cdnjs.cloudflare.com/ajax/libs/respond.js/1.4.2/respond.js"></script>
    <![endif]-->

</head>
<body>
<div class="container">
    <h1>Piwigo Album Exporter</h1>
    <h3>Setup</h3>
    <hr>

    <div class="row">

        <!-- Content -->
        <div class="col-sm-9">
            <?php
            $arMessage = GetMessage();
            if (is_array($arMessage)) {
                ?>
                <div class="alert alert-<?=($arMessage[1] ? "success" : "danger");?>">
                    <?=$arMessage[0];?>
                </div>
                <?php
            }
            ?>
            <form class="form-horizontal" method="post" action="action.php">
                <input type="hidden" name="fldAction" id="fldAction" value="save-settings">
                <div class="form-group">
                    <label for="fldURL" class="col-sm-2 control-label">Piwigo URL</label>
                    <div class="col-sm-10">
                        <input type="url" class="form-control" id="fldURL" name="fldURL" placeholder="Piwigo URL" value="<?=$oSettings->PiwigoURL;?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="fldRoot" class="col-sm-2 control-label">Document Root</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="fldRoot" name="fldRoot" placeholder="Document Root" value="<?=$oSettings->DocumentRoot;?>">
                        <p class="help-block">Leave blank for default</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="fldPath" class="col-sm-2 control-label">Piwigo Path</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="fldPath" name="fldPath" placeholder="Piwigo Path" value="<?=$oSettings->PiwigoPath;?>">
                        <p class="help-block">Leave blank for system to find automatically</p>
                    </div>
                </div>
                <div class="form-group">
                    <label for="fldUsername" class="col-sm-2 control-label">Admin Username</label>
                    <div class="col-sm-10">
                        <input type="text" class="form-control" id="fldUsername" name="fldUsername" placeholder="Admin Username" value="<?=$oSettings->Username;?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="fldPassword" class="col-sm-2 control-label">Admin Password</label>
                    <div class="col-sm-10">
                        <input type="password" class="form-control" id="fldPassword" name="fldPassword" placeholder="Admin Password" value="<?=$oSettings->Password;?>">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-sm-offset-2 col-sm-10">
                        <button type="submit" class="btn btn-primary">Save</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Navigation -->
        <div class="col-sm-3 well">
            <ul class="nav nav-pills nav-stacked">
                <li role="presentation"><a href="index.php">Album List</a></li>
                <li role="presentation" class="active"><a href="setup.php">Setup</a></li>
            </ul>
        </div>

    </div>

</div>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<!-- Include all compiled plugins (below), or include individual files as needed -->
<script src="http://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/js/bootstrap.min.js"></script>
</body>
</html>
