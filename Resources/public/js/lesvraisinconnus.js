/*jslint unparam: true */
/*global window, $ */
$(document).ready(function(){
  $('a.popin_vrais_inconnus').on('click', function(){
     $(".modal-footer").css("display","block"); 
    //L'UTILISATEUR EST PAS CONNECTE
    $('#skModal.modal .modal-title').html(' ');
    $('#skModal.modal .modal-message').html('Pour nous envoyer votre parodie, créez d\’abord un compte ou connectez-vous sur votre compte myskreen en 1 clic. C\’est rapide et gratuit.'+
    '    '
);

    UI.auth(function() {
      if (Skhf.session.datas.email &&
          !$('#skModal.modal .modal-body').html()) {
        console.log("DATA SESSION :::::::: ",Skhf.session.datas);
        var usernameMsg = "";
        var hasFieldForUsername=false;
        var checkMsg = '';
            
        if (Skhf.session.datas.username != undefined) {
          usernameMsg = ' <p>'
                      + ' <label for="inputTitle">Votre Pseudo : </label> ' + Skhf.session.datas.username
                      + ' </p>';
        } else {

          usernameMsg = ' <p>'
                      + ' <label for="inputTitle">Votre Pseudo : </label> <input class="form-control" id="lePseudo" type="text" onBlur="javascript: checkAvailable(this.value);" name="lvi_pseudo" placeholder="Pseudo"/>'
                      + ' <span id="lePseudoMsg"></span>'
                      + ' </p>';
          hasFieldForUsername=true;

          checkMsg = 'function checkAvailable(val) {'
                   + ' API.query("GET", "availableUsername.json", {username: val, sk_id: "' + Skhf.session.datas.sk_id + '"}, function(resp){'
                   + ' console.log(resp);'
                   + ' if (!resp.available) {'
                   + ' $("#lePseudo").val("");'
                   + ' $("#lePseudoMsg").css("color","#c00");'
                   + ' $("#lePseudoMsg").html("<strong>" + val + "</strong> n\'est pas disponible !");'
                   + ' $("#lePseudo").focus();'
                   + ' } else {'
                   + ' $("#lePseudoMsg").css("color","#0c0");'
                   + ' $("#lePseudoMsg").html("<strong>" + val + "</strong> est disponible.");'
                   + ' }'
                   + ' });'
                   + '}';
        }
        //L'UTILISATEUR EST CONNECTE
        $('#skModal.modal .modal-title').html('PUBLIER UNE PARODIE');
        $('#skModal.modal .modal-message').html('Renseignez votre pseudo, donnez un titre à votre vidéo et présentez-la en quelques mots. <br> Conseil de dernière minute : soignez le son et l\image, c\'est important.');
        $('#skModal.modal .modal-body').html(''
            + '<div class="scroll" style="overflow-y: auto;max-height: 350px;">'
            + '<form id="vraisinconnus_form" role="form" class="modal-catchform-disable" method="post" action="'+API.config.v3_root +'/_lesvraisinconnusbundle/done" enctype="multipart/form-data">'
            + ' <div id="leForm">'
            + ' <div id="alert_msg" class="alert alert-danger alert-block" style="display: none;">'
            + ' <strong>Aïe !</strong>'
            + ' <span id="alert_msg_body" style="font-size: 12px"></span>'
            + ' </div>'
            + usernameMsg
            + ' <p class="form-group">'
            + ' <label for="inputTitle">Titre de votre vid&eacute;o</label> <input class="form-control" type="text" id="inputTitle" name="lvi_title" placeholder="Le titre"/>'
            + ' </p>'
            + ' <p class="form-group">'
            + ' <label for="inputDesc">Description de votre vid&eacute;o</label><br />'
            + ' <textarea class="form-control" id="inputDesc" name="lvi_desc" placeholder="La description" style="height:60px;"/>'
            + ' </p>'
            + ' <p class="form-group">'
            + ' <input class="form-control" id="fileupload" type="file" name="lvi_file" onChange="displayFileInfo();">'
            + ' <span class="btn btn-large btn-info fileinput-button">'
            + ' '
            + ' <span>(Taille maximum 2 Go)</span>'
            + ' </span>'
            + ' <div id="fileInfo" class="alert alert-info" style="display:none"></div>'
            + ' </p><input type="hidden" id="inputCgv" name="lvi_cgv" value="1"/>'
            + ' <p class="form-group accept">'
            + ' </p>'
            + ' </div>'
            + ' <div id="leProgress" style="text-align: center; display: none">'
            + ' <h3 id="progressStatus">envoi du fichier en cours...</h3>'
            + ' <div id="progress" class="progress progress-success progress-striped active">'
            + ' <div class="progress-bar"></div>'
            + ' </div>'
            + ' </div>'
            + ' <div id="leSuccess" style="display: none">'
            + ' <div class="alert alert-success">F&eacute;licitations ! Votre parodie a bien &eacute;t&eacute; envoy&eacute;e. </div>'
            + ' <p>Apr&egrave;s validation par nos &eacute;quipes, votre vid&eacute;o sera publi&eacute;e sur myskreen.com <br><br>Vous recevrez un lien par mail dès qu\’elle sera validée.Vous pourrez alors partager votre vidéo : plus elle sera vue, plus elle aura de chance d\’être sélectionnée par Les Inconnus et passer sur France 2 !'
            + ' </p><br>Merci de votre participation.<br><br> <input  class="close" data-dismiss="modal" type="button"value="Revenir au site" style="display: inline-block;width: auto;"/>'
            + ' </div>'
            + ' <div id="leError" style="display: none">'
            + ' <div class="alert alert-error" style="background-color: #fcc"><strong>OULA !</strong> Il y a visiblement eu un problème pendant le transfert.</div>'
            + ' <p>Je n\'accuse personne, mais je pense qu\'il serait de bon ton de recommencer la procédure, parce que, là, je'
            + ' suis désolé mais ça n\'est pas passé...</p>'
            + ' <button class="btn btn-info" onClick="resetForm();">Revenir au formulaire</button>'
            + ' </div>'
            + '</form>'
            + '</div>'
            + '<script>'
            + ' function validateForm(arr, theForm, options) {'
            + (hasFieldForUsername ? 'var pseudo = arr[0];' : '')
            + '    var title = arr[' + (hasFieldForUsername ? '1' : '0') + '];'
            + '    var desc = arr[' + (hasFieldForUsername ? '2' : '1') + '];'
            + '    var cgv = false; var file = false;'
            //+ '    if (arr.length == ' + (hasFieldForUsername ? '5' : '4') + ') {'
            + '      console.log(\'arr.length:\', arr.length);'
            + '      cgv  = true;'
            + '      file = arr[' + (hasFieldForUsername ? '3' : '2') + '];'
            //+ '    } else {'
            //+ '      cgv  = false;'
            //+ '      file = arr[' + (hasFieldForUsername ? '3' : '2') + '];'
            //+ '    }'
            + '    console.log(\'file.value:\', file, arr, \'hasFieldForUsername:\', '+hasFieldForUsername+');'
            + '    var errMsg = "";'
            + '    var errCount = 0;'
            + (hasFieldForUsername ? 'if (pseudo.value == "") { errCount++; errMsg += "<br />- Il faut renseigner votre pseudo;"; }' : '')
            + ' if (title.value == "") { errCount++; errMsg += "<br />- il faut donner un titre à la vidéo;";}'
            + ' if (desc.value == "") { errCount++; errMsg += "<br />- il faut décrire un peu la vidéo;";}'
            + ' if (!cgv) { errCount++; errMsg += "<br />- je sais, c\'est lourd, il faut cocher la case \\\"j\'accepte...\\\"...mais bon, c\'est la loi;";}'
            + ' if (file.value == "") { errCount++; errMsg += "<br />- il faut aussi mettre un fichier vidéo;";}'
            + ' else if (file.value.size > 2*1024*1024*1024) { errCount++; errMsg += "<br />- je suis sûr que la vidéo est top, mais le fichier est trop gros (max. 2 Go);";}'
            + ' if (errCount == 1) { errMsg = "<br />Il y a un problème (sans gravité, heureusement) : " + errMsg; }'
            + ' else if (errCount > 1) { errMsg = "<br />Il y a plusieurs problèmes (sans gravité, heureusement) : " + errMsg; }'
            + ' console.log("ERR COUNT : " + errCount);'
            + ' if (errCount > 0) {'
            + ' console.log("ERR COUNT : " + errCount);'
            + ' $("#alert_msg_body").html(errMsg);'
            + ' $("#alert_msg").slideDown(600);'
            + ' return false;'
            + ' } else {'
            + ' $("#alert_msg").hide(); '
            + ' }'
            + ' $("#leForm").hide();'
            + ' $("#leProgress").show();'
            + ' }'
            + ''
            + ' function showProgress(e, position, total, percentComplete) {'
            + ' console.log("ON UPDATE LA PROGRESS BAR : " + percentComplete);'
            + ' $(".modal-footer").css("display","none");'
            + ' $("#progress .progress-bar").css("width",percentComplete + "%");'
            + ' if (percentComplete > 97) { $("#progressStatus").html(" et on y est...")}'
            + ' else if (percentComplete > 84) { $("#progressStatus").html("plus que "+(100 - percentComplete)+"% et on y est...")}'
            + ' else if (percentComplete > 75) { $("#progressStatus").html("(" + percentComplete + "%) on arrive bientôt au bout...")}'
            + ' else if (percentComplete > 70) { $("#progressStatus").html("(" + percentComplete + "%) on espère que c\'est drôle...")}'
            + ' else if (percentComplete > 65) { $("#progressStatus").html("(" + percentComplete + "%) on espère que c\'est drôle...")}'
            + ' else if (percentComplete > 60) { $("#progressStatus").html("(" + percentComplete + "%) on espère que c\'est drôle...")}'
            + ' else if (percentComplete > 55) { $("#progressStatus").html("(" + percentComplete + "%) wow ! sacré fichier...")}'
            + ' else if (percentComplete > 50) { $("#progressStatus").html("(" + percentComplete + "%) wow ! sacré fichier...")}'
            + ' else if (total > 17*1024*1024 && percentComplete > 46) { $("#progressStatus").html("(" + percentComplete + "%) wow ! sacré fichier...")}'
            + ' else if (total > 17*1024*1024 && percentComplete > 40) { $("#progressStatus").html("(" + percentComplete + "%) plus le fichier est lourd, plus c\'est long...")}'
            + ' else if (total > 17*1024*1024 && percentComplete > 34) { $("#progressStatus").html("(" + percentComplete + "%) plus le fichier est lourd, plus c\'est long...")}'
            + ' else if (total > 17*1024*1024 && percentComplete > 28) { $("#progressStatus").html("(" + percentComplete + "%) plus le fichier est lourd, plus c\'est long...")}'
            + ' else if (percentComplete > 23) { $("#progressStatus").html("(" + percentComplete + "%) plus le fichier est lourd, plus c\'est long...")}'
            + ' else if (percentComplete > 15) { $("#progressStatus").html("(" + percentComplete + "%) plus le fichier est lourd, plus c\'est long...")}'
            + ' else if (percentComplete > 0) { $("#progressStatus").html("(" + percentComplete + "%) envoi du fichier en cours...")}'
            + ' }'
            + ''
            + ' function showSuccess(responseText, statusText, xhr, theForm) {'
            + ' console.log("FORMULAIRE TRAITE AVEC SUCCES - YOUPI !!");'
            + ' $("#leForm, #leProgress, #leError").hide();'
            + ' $("#leSuccess").show();'
            + ' }'
            + ''
            + ' function showError() {'
            + ' console.log("FORMULAIRE TRAITE AVEC ERREUR - ZUT-TEUH !!");'
            + ' $("#leForm, #leProgress, #leSuccess").hide();'
            + ' $("#leError").show();'
            + ' }'
            + ''
            + ' function resetForm() {'
            + ' $("#submit_btn").css("opacity","1").removeAttr("disabled");'
            + ' $("#fileupload").val("");'
            + ' $("#leForm").show();'
            + ' $("#leProgress, #leSuccess, #leError").hide();'
            + ' return false;'
            + ' }'
            + ''
            + ' function displayFileInfo() { '
            + ' $("#fileupload").css("background-color", "#9ce48d"); '
            + ' }'
            + ''
            + ' var formOptions = {'
            + ' beforeSubmit: validateForm,'
            + ' uploadProgress: showProgress,'
            + ' error: showError,'
            + ' success: showSuccess'
            + ' };'
            + ''
            + (hasFieldForUsername ? checkMsg : '')
            + ' $("#vraisinconnus_form").ajaxForm(formOptions);'
            + '</script>');

        $('#skModal.modal .modal-footer').html('<button id="submit_btn" class="btn btn-success valid-btn-inc" onClick="if (!$(this).attr(\'disabled\')) $(\'#vraisinconnus_form\').submit() ">Valide, c\'est ton destain !</button>'
        + '<br><label id="labelfilou"for="inputCgv" style="font-weight: normal; font-size: 12px;">'
        + '     En cliquant sur valider, vous acceptez <a href="http://mskstatic.com/medias/pdf/CGU-vrais-inconnus.pdf" target="_blank">les conditions g&eacute;n&eacute;rales et les r&egrave;gles de diffusion</a></label>');
        $('#skModal.modal').modal('show');
      }
    });
  });
});