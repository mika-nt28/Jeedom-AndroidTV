<?php
if (!isConnect('admin')) {
    throw new Exception('{{401 - Accès non autorisé}}');
}
?>
<div class="container-modal">
    <div class="stepwizard col-md-offset-3">
        <div class="stepwizard-row setup-panel">
            <div class="stepwizard-step">
                <a href="#step-1" type="button" class="btn btn-primary stepwizard-btn-circle">1</a>
                <p>{{Étape 1}}</p>
            </div>
            <div class="stepwizard-step">
                <a href="#step-2" type="button" class="btn btn-default stepwizard-btn-circle" disabled="disabled">2</a>
                <p>{{Étape 2}}</p>
            </div>
            <div class="stepwizard-step">
                <a href="#step-3" type="button" class="btn btn-default stepwizard-btn-circle" disabled="disabled">3</a>
                <p>{{Étape 3}}</p>
            </div>
            <div class="stepwizard-step">
                <a href="#step-4" type="button" class="btn btn-default stepwizard-btn-circle" disabled="disabled">4</a>
                <p>{{Étape 4}}</p>
            </div>
            <div class="stepwizard-step">
                <a href="#step-5" type="button" class="btn btn-default stepwizard-btn-circle" disabled="disabled">5</a>
                <p>{{Étape 5}}</p>
            </div>
        </div>
    </div>

    <form role="form" action="" method="post">
        <div class="row setup-content" id="step-1">
            <div class="stepwizard col-md-offset-3">
                    <center><h3>{{Identification de l'adresse IP}}</h3></center></br>
                    <div class="form-group">
                        <center><label class="control-label">{{Pour identifier l'adresse IP de votre périphérique Android,</br>Aller dans "Menu", cliquer sur "Status"}}</label></center>
                        <input id="ip_address_found" type="text" required="required" class="form-control stepwizard-input" placeholder="192.168.1.XXX" />
                        <center><img src="plugins/AndroidTV/desktop/images/Config/IP_status_setp1.png" /></center>
                    </div>
                    <button id="toStep2" class="btn btn-primary nextBtn btn-lg pull-right" type="button" ><i class="fa fa-fast-forward"></i> {{Suivant}}</button>
            </div>
        </div>
        <div class="row setup-content" id="step-2">
            <div class="stepwizard col-md-offset-3">
                    <center><h3>{{Activer le mode Développeur}}</h3></center></br>
                    <div class="form-group">
                        <center><label class="control-label">{{Aller dans le menu "About" - Cliquer plusieurs fois sur "Build"}}</label></center>
                        <center><img src="plugins/AndroidTV/desktop/images/Config/About_Build_step2.png"/></center>
                    </div>
                    <button id="toStep3" class="btn btn-primary nextBtn btn-lg pull-right" type="button" ><i class="fa fa-fast-forward"></i> {{Suivant}}</button>
            </div>
        </div>
        <div class="row setup-content" id="step-3">
            <div class="stepwizard col-md-offset-3">
                    <center><h3>{{Activer le mode Debug}}</h3></center></br>
                    <div class="form-group">
                        <center><label class="control-label">{{Aller dans le menu "System - Cliquer sur "Developer options"}}</label></center>
                        <center><img src="plugins/AndroidTV/desktop/images/Config/Developer_Options_step3.png"/></center>
                    </div>
                    <button id="toStep4" class="btn btn-primary nextBtn btn-lg pull-right" type="button" ><i class="fa fa-fast-forward"></i> {{Suivant}}</button>
            </div>
        </div>
        <div class="row setup-content" id="step-4">
            <div class="stepwizard col-md-offset-3">
                    <center><h3>{{Activer le mode Debug}}</h3></center></br>
                    <div class="form-group">
                        <center><label class="control-label">{{Cliquer sur "USB debugging"}}</label></center>
                        <center><img src="plugins/AndroidTV/desktop/images/Config/USB_Debugging_step4.png"/></center>
                    </div>
                    <button id="toStep5" class="btn btn-primary nextBtn btn-lg pull-right" type="button" ><i class="fa fa-fast-forward"></i> {{Suivant}}</button>
            </div>
        </div>
        <div class="row setup-content" id="step-5">
            <div class="stepwizard col-md-offset-3">
                    <center><h3>{{Enregistrer la connexion}}</h3></center></br>
                    <div class="form-group">
                        <center><label class="control-label">{{Cliquer sur "Always allow from this computer" puis sur "OK"}}</label></center>
                        <center><img src="plugins/AndroidTV/desktop/images/Config/Allow_stepFinal.png"/></center>
                    </div>
                    <button id="closeConfigureAdb" class="btn btn-primary nextBtn btn-lg pull-right ui-icon-closethick" type="button" >{{Terminer}}</button>
            </div>
        </div>
    </form>
</div>

<?php include_file('desktop', 'configureadb.AndroidTV', 'js', 'AndroidTV');?>
<?php include_file('desktop', 'configureadb.AndroidTV', 'css', 'AndroidTV');?>