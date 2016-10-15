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
    header("Location: setup.php\r\n");
}
$bLogin = $oPAE->Login();

// Get list of albums
$oAlbums = $oPAE->PostWebServiceRequest("pwg.categories.getList");
$arCats = $oAlbums->result->categories;
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
    <h3>Listing Albums</h3>
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
            <table class="table table-striped table-bordered table-hover">
                <thead>
                <tr>
                    <th>Album Name</th>
                    <th>Status</th>
                    <th class="col-sm-1">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($arCats as $oCat) {
                    $iAlbumID = $oCat->id;
                    $sAlbumName = $oCat->name;
                    $sThumbURL = $oCat->tn_url;
                    $iImageCount = $oCat->nb_images;
                    $oDate = new DateTime($oCat->max_date_last);
                    $bStatus = GetZIPStatus($iAlbumID);
                    if ($bStatus) {
                        $sStatus = "ZIP Ready" . "<br><button class=\"pae-btn btn btn-xs btn-danger\" data-action=\"delete-zip\" data-album=\"" . $iAlbumID . "\">Delete ZIP</button>";
                    } else {
                        $sStatus = "No ZIP Created";
                    }
                    $sViewURL = $oCat->url;
                    ?>
                    <tr id="pae-album-<?=$iAlbumID;?>">
                        <td>
                            <img class="pull-right" src="<?=$sThumbURL;?>" alt="thumbnail">
                            <p>
                                <strong><?=$sAlbumName?></strong>
                                <br>
                                <?=$iImageCount;?> Images
                                <br>
                                <?=$oDate->format("j F Y");?>
                                <br>
                                <a class="small" href="<?=$sViewURL;?>" target="_blank">View Album &raquo;</a>
                            </p>
                        </td>
                        <td>
                            <?=$sStatus;?>
                        </td>
                        <td class="col-sm-1 text-center">
                            <div class="btn-group-vertical" role="group">
                                <button data-action="create-zip" data-album="<?=$iAlbumID;?>" class="pae-btn btn btn-primary<?=($bStatus ? " disabled" : "");?>">Generate ZIP</button>
                                <button data-action="download-zip" data-album="<?=$iAlbumID;?>" class="pae-btn btn btn-success<?=($bStatus ? "" : " disabled");?>">Download ZIP</button>
                                <button data-action="delete-album" data-album="<?=$iAlbumID;?>" class="pae-btn btn btn-danger<?=($bLogin ? "" : " disabled");?>">Delete Album</button>
                            </div>
                        </td>
                    </tr>
                    <?php
                }
                ?>
                </tbody>
            </table>
            <!--<?php Dump($oPAE); ?>-->
        </div>

        <!-- Navigation -->
        <div class="col-sm-3 well">
            <ul class="nav nav-pills nav-stacked">
                <li role="presentation" class="active"><a href="index.php">Album List</a></li>
                <li role="presentation"><a href="setup.php">Setup</a></li>
            </ul>
        </div>

    </div>

    <hr>

    <!-- Footer -->
    <div class="row">
        <div class="col-xs-12 text-muted small">
            <p>
                t2tPAE - tip2tail Piwigo Extractor
                <br>
                Version 2016.10.15.01
            </p>
            <p>
                Copyright &copy; <?=date("Y");?> tip2tail Ltd.  Developed by Mark Young.
                <br>
                Released under XXXXXXXXXXXXXXXXXXXXXXXX at Github.
            </p>
        </div>
    </div>

</div>

<!-- Modals -->
<div class="modal fade" id="myModal" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel">Please Wait!</h4>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <p><img src="gears.gif" alt="Loading"></p>
                    <p class="text-info lead" id="pae-reason">Generating ZIP File</p>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalDeleteAlbum" tabindex="-1" role="dialog" aria-labelledby="modalDeleteAlbumLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalDeleteAlbumLabel">Confirm Delete</h4>
            </div>
            <div class="modal-body">
                <p class="text-danger lead">Are you sure you want to delete this album?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnDeleteAlbum">Delete</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalDeleteZip" tabindex="-1" role="dialog" aria-labelledby="modalDeleteZipLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalDeleteZipLabel">Confirm Delete</h4>
            </div>
            <div class="modal-body">
                <p class="text-danger lead">Are you sure you want to delete this ZIP file?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btnDeleteZip">Delete</button>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="modalDebug" tabindex="-1" role="dialog" aria-labelledby="modalDebugLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalDebugLabel">Debug Mode</h4>
            </div>
            <div class="modal-body" id="pae-debug-text">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/jquery/2.1.3/jquery.min.js"></script>
<script type="text/javascript" src="http://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/3.3.4/js/bootstrap.min.js"></script>
<script type="text/javascript">

    var bDebugMode = true;

    var iDeleteAlbum_ID;
    var iDeleteZip_ID;

    $(function() {
        $(".pae-btn").click(function() {
            // OnClick
            var sAction = $(this).data("action");
            var iAlbum = $(this).data("album");

            switch (sAction) {
                case "create-zip":
                    CreateZip(iAlbum);
                    break;
                case "download-zip":
                    DownloadZip(iAlbum);
                    break;
                case "delete-album":
                    DeleteAlbum(iAlbum);
                    break;
                case "delete-zip":
                    DeleteZip(iAlbum);
                    break;
            }
        });

        <!-- Button onClick Events -->
        $("#btnDeleteZip").click(function() {
            // OnClick
            ShowHideModal(false, "#modalDeleteZip");
            AJAXRequest("delete-zip", iDeleteZip_ID);
        });
        $("#btnDeleteAlbum").click(function() {
            // OnClick
            ShowHideModal(false, "#modalDeleteAlbum");
            AJAXRequest("delete-album", iDeleteAlbum_ID);
        });

        <!-- Modal Setup -->
        $("#myModal").modal({
            backdrop: "static",
            keyboard: false,
            show: false
        });
        $("#modalDeleteAlbum").modal({
            backdrop: "static",
            keyboard: false,
            show: false
        });
        $("#modalDeleteZip").modal({
            backdrop: "static",
            keyboard: false,
            show: false
        });
        $("#modalDebug").modal({
            backdrop: "static",
            keyboard: false,
            show: false
        }).on('hidden.bs.modal', function (eVent){
            top.location.reload();
        });
    });

    function DeleteAlbum(iAlbum) {
        iDeleteAlbum_ID = iAlbum;
        ShowHideModal(true, "#modalDeleteAlbum");
    }

    function DeleteZip(iAlbum) {
        iDeleteZip_ID = iAlbum;
        ShowHideModal(true, "#modalDeleteZip");
    }

    function DownloadZip(iAlbum) {
        top.location.href = "zips/" + iAlbum + ".zip";
    }

    function ShowHideModal(bShow, sModal = "#myModal") {
        if (bShow) {
            // Show
            $(sModal).modal('show');
        } else {
            // Hide
            $(sModal).modal('hide');
        }
    }

    function SetWaitReason(sAction) {
        switch (sAction) {
            case "create-zip":
                $("#pae-reason").html("Generating ZIP file...");
                break;
            case "download-zip":
                // Nothing
                break;
            case "delete-album":
                $("#pae-reason").html("Deleting Piwigo album...");
                break;
            case "delete-zip":
                $("#pae-reason").html("Deleting ZIP file...");
                break;
        }
    }

    function Debug(oData) {
        ShowHideModal(false);
        $("#pae-debug-text").html(oData);
        ShowHideModal(true, "#modalDebug");
    }

    function AJAXRequest(sAction, iAlbum) {
        SetWaitReason(sAction);
        ShowHideModal(true);
        $.post("action.php", {
            "fldAction" : sAction,
            "album" : iAlbum
        }).done(function (oData) {
            if (bDebugMode) {
                Debug(oData);
            } else {
                top.location.reload();
            }
        }).fail(function (oData) {
            if (bDebugMode) {
                Debug(oData);
            } else {
                top.location.reload();
            }
        });
    }

    function CreateZip(iAlbum) {
        AJAXRequest("create-zip", iAlbum);
    }

</script>

</body>
</html>
