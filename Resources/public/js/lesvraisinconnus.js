/*jslint unparam: true */
/*global window, $ */
$(document).ready(function(){
  $('a.popin_vrais_inconnus').on('click', function(){
    UI.auth(function() {
      if (Skhf.session.datas.email) {
        console.log("DATA SESSION :::::::: ",Skhf.session.datas);
        var usernameMsg = "";
        var hasFieldForUsername=false;
        var checkMsg = '';
        if (Skhf.session.datas.username != undefined) {
          usernameMsg = '    <p>'
                      + '      <label for="inputTitle">Pseudo : </label> ' + Skhf.session.datas.username
                      + '    </p>';
        } else {
          usernameMsg = '    <p>'
                      + '      <label for="inputTitle">Pseudo : </label> <input class="form-control" id="lePseudo" type="text" onBlur="javascript: checkAvailable(this.value);" name="lvi_pseudo" placeholder="Choisis ton pseudo"/>'
                      + '      <span id="lePseudoMsg"></span>'
                      + '    </p>';
          hasFieldForUsername=true;
          checkMsg = 'function checkAvailable(val) {'
                   + '  API.query("GET", "availableUsername.json", {username: val, sk_id: "' + Skhf.session.datas.sk_id + '"}, function(resp){'
                   + '    console.log(resp);'
                   + '    if (!resp.available) {'
                   + '      $("#lePseudo").val("");'
                   + '      $("#lePseudoMsg").css("color","#c00");'
                   + '      $("#lePseudoMsg").html("<strong>" + val + "</strong> n\'est pas disponible !");'
                   + '      $("#lePseudo").focus();'
                   + '    } else {'
                   + '      $("#lePseudoMsg").css("color","#0c0");'
                   + '      $("#lePseudoMsg").html("<strong>" + val + "</strong> est disponible.");'
                   + '    }'
                   + '  });'
                   + '}';
        }

        //L'UTILISATEUR EST CONNECTE
        $('.modal .modal-title').html('Toi aussi, envoie ta parodie des Inconnus');
        $('.modal .modal-message').html('Si ta parodie plaît aux Inconnus, elle sera peut-être diffusée sur France 2.');
        $('.modal .modal-body').html(''
            + '<form id="vraisinconnus_form" role="form" method="post" action="'+API.config.v3_root +'/lesvraisinconnus/done" enctype="multipart/form-data">'
            + '  <div id="leForm">'
            + '    <div id="alert_msg" class="alert alert-danger alert-block" style="display: none;">'
            + '      <strong>Aïe !</strong>'
            + '      <span id="alert_msg_body" style="font-size: 12px"></span>'
            + '    </div>'
            + usernameMsg
            + '    <p>'
            + '      <label for="inputTitle">Donne un titre à ta vidéo</label> <input class="form-control" type="text" id="inputTitle" name="lvi_title" placeholder="Le titre"/>'
            + '    </p>'
            + '    <p>'
            + '      <label for="inputDesc">Décris ta vidéo en quelques mots</label><br />'
            + '      <textarea class="form-control" id="inputDesc" name="lvi_desc" placeholder="La description"/>'
            + '    </p>'
            + '    <p>'
            + '        <label for="inputCgv" style="font-weight: normal; font-size: 12px;"><input type="checkbox" id="inputCgv" name="lvi_cgv" value="1"/>'
            + '        J\'accepte les conditions générales de ce superbe événement, disponibles <a href="#" target="_blank">ICI</a></label>'
            + '    </p>'
            + '    <p>'
            + '      <span class="btn btn-large btn-info fileinput-button">'
            + '        <i class="glyphicon glyphicon-plus"></i>'
            + '        <span>Balance ton fichier (max. 1 Go)</span>'
            + '        <input class="form-control" id="fileupload" type="file" name="lvi_file" onChange="displayFileInfo();">'
            + '      </span>&nbsp;<span id="fileInfo" style="color: #0c0; font-size: 11px;"></span><br />'
            + '      <span style="font-weight: normal; font-size: 12px">Conseil de dernière minute : soigne le son et l\'image, c\'est important</span>'
            + '    </p><br />'
            + '    <p class="pull-right">'
            + '      <input id="submit_btn" class="btn btn-success" type="submit" value="Envoie ! C\'est ton destain !"/>'
            + '    </p>'
            + '  </div>'
            + '  <div id="leProgress" style="text-align: center; display: none">'
            + '    <h3 id="progressStatus">envoi du fichier en cours...</h3>'
            + '    <div id="progress" class="progress progress-success progress-striped active">'
            + '      <div class="progress-bar"></div>'
            + '    </div>'
            + '  </div>'
            + '  <div id="leSuccess" style="display: none">'
            + '    <div class="alert alert-success"><strong>Félicitations !</strong> La vidéo a été correctement uploadée.</div>'
            + '    <p>Un modérateur de mySkreen va vérifier que cette vidéo n\'a rien d\'infâmant avant de la publier.'
            + '    Tu vas par ailleurs recevoir prochainement un e-mail avec le lien vers la vidéo.<br /><br />'
            + '    Petits conseils :<br />'
            + '    - partage cette vidéo avec tes amis car<br />'
            + '    - plus elle sera vue, plus les chances augmenteront qu\'elle soit diffusée sur France 2<br />'
            + '    - de plus, c\'est la classe et tes amis méritent bien de la voir, non ?</p>'
            + '  </div>'
            + '  <div id="leError" style="display: none">'
            + '    <div class="alert alert-error" style="background-color: #fcc"><strong>OULA !</strong> Il y a visiblement eu un problème pendant le transfert.</div>'
            + '    <p>Je n\'accuse personne, mais je pense qu\'il serait de bon ton de recommencer la procédure, parce que, là, je'
            + '    suis désolé mais ça n\'est pas passé...</p>'
            + '    <button class="btn btn-info" onClick="resetForm();">Revenir au formulaire</button>'
            + '  </div>'
            + '</form>'
            + '<script>'
            + '  function validateForm(arr, theForm, options) {'
            + (hasFieldForUsername ? 'var pseudo = arr[0];' : '')
            + '    var title = arr[' + (hasFieldForUsername ? '1' : '0') + '];'
            + '    var desc = arr[' + (hasFieldForUsername ? '2' : '1') + '];'
            + '    var cgv = false; var file = false;'
            + '    if (arr.length == ' + (hasFieldForUsername ? '5' : '4') + ') {'
            + '      cgv  = true;'
            + '      file = arr[' + (hasFieldForUsername ? '4' : '3') + '];'
            + '    } else {'
            + '      cgv  = false;'
            + '      file = arr[' + (hasFieldForUsername ? '3' : '2') + '];'
            + '    }'
            + '    var errMsg = "";'
            + '    var errCount = 0;'
            + (hasFieldForUsername ? 'if (pseudo.value == "") { errCount++; errMsg += "<br />- il faut renseigner un pseudo valide, sinon comment rendre à César;"; }' : '')
            + '    if (title.value == "") { errCount++; errMsg += "<br />- il faut renseigner le titre de la vidéo, sinon on ne sait pas de quoi il s\'agit;";}'
            + '    if (desc.value == "") { errCount++; errMsg += "<br />- il faut décrire un peu sa vidéo, parce que sinon on n\'a que le titre;";}'
            + '    if (!cgv) { errCount++; errMsg += "<br />- je sais, c\'est lourd, il faut cocher la case \\\"j\'accepte...\\\"...mais bon, c\'est la loi;";}'
            + '    if (file.value == "") { errCount++; errMsg += "<br />- ouais...il faut aussi mettre un fichier vidéo, c\'est un peu la base du projet;";}'
            + '    else if (file.value.size > 1024*1024*1024) { errCount++; errMsg += "<br />- je suis sûr que la vidéo est top, mais le fichier est trop gros (1Go Max);";}'
            + '    if (errCount == 1) { errMsg = "<br />Il y a un problème (sans gravité, heureusement) : " + errMsg; }'
            + '    else if (errCount > 1) { errMsg = "<br />Il y a plusieurs problèmes (sans gravité, heureusement) : " + errMsg; }'
            + '    console.log("ERR COUNT : " + errCount);'
            + '    if (errCount > 0) {'
            + '      console.log("ERR COUNT : " + errCount);'
            + '      $("#alert_msg_body").html(errMsg);'
            + '      $("#alert_msg").slideDown(600);'
            + '      return false;'
            + '    } else {'
            + '      $("#alert_msg").hide(); '
            + '    }'
            + '    $("#leForm").hide();'
            + '    $("#leProgress").show();'
            + '  }'
            + ''
            + '  function showProgress(e, position, total, percentComplete) {'
            + '    console.log("ON UPDATE LA PROGRESS BAR : " + percentComplete);'
            + '    $("#progress .progress-bar").css("width",percentComplete + "%");'
            + '    if (percentComplete > 97) { $("#progressStatus").html("eh ben, c\'est pas dommage...")}'
            + '    else if (percentComplete > 84) { $("#progressStatus").html("plus que "+(100 - percentComplete)+"% et on y est...")}'
            + '    else if (percentComplete > 75) { $("#progressStatus").html("on arrive bientôt au bout...")}'
            + '    else if (percentComplete > 70) { $("#progressStatus").html("j\'espère que c\'est drôle...")}'
            + '    else if (percentComplete > 65) { $("#progressStatus").html("wow ! sacré fichier...")}'
            + '    else if (percentComplete > 60) { $("#progressStatus").html("...de pourir de la peur du plaisir...")}'
            + '    else if (percentComplete > 55) { $("#progressStatus").html("...de la peur du désir de la mort...")}'
            + '    else if (percentComplete > 50) { $("#progressStatus").html("cette attente, c\'est le plaisir...")}'
            + '    else if (total > 17*1024*1024 && percentComplete > 46) { $("#progressStatus").html("...et tout deviendra clair...")}'
            + '    else if (total > 17*1024*1024 && percentComplete > 40) { $("#progressStatus").html("hypochondriaque...")}'
            + '    else if (total > 17*1024*1024 && percentComplete > 34) { $("#progressStatus").html("sinusoïdale de l\'anachorète...")}'
            + '    else if (total > 17*1024*1024 && percentComplete > 28) { $("#progressStatus").html("être ou ne pas être, telle est la question...")}'
            + '    else if (percentComplete > 23) { $("#progressStatus").html("envoi du fichier en cours...")}'
            + '    else if (percentComplete > 15) { $("#progressStatus").html("plus le fichier est lourd, plus c\'est long...")}'
            + '  }'
            + ''
            + '  function showSuccess(responseText, statusText, xhr, theForm) {'
            + '    console.log("FORMULAIRE TRAITE AVEC SUCCES - YOUPI !!");'
            + '    $("#leForm").hide();'
            + '    $("#leProgress").hide();'
            + '    $("#leSuccess").show();'
            + '    $("#leError").hide();'
            + '  }'
            + ''
            + '  function showError() {'
            + '    console.log("FORMULAIRE TRAITE AVEC ERREUR - ZUT-TEUH !!");'
            + '    $("#leForm").hide();'
            + '    $("#leProgress").hide();'
            + '    $("#leSuccess").hide();'
            + '    $("#leError").show();'
            + '  }'
            + ''
            + '  function resetForm() {'
            + '    $("#leForm").show();'
            + '    $("#leProgress").hide();'
            + '    $("#leSuccess").hide();'
            + '    $("#leError").hide();'
            + '  }'
            + ''
            + '  function displayFileInfo() {'
            + '    var path = $("#fileupload").val();'
            + '    var idx = Math.max(path.lastIndexOf("/"),path.lastIndexOf("\\\\")) + 1;'
            + '    var name = path.substr(idx);'
            + '    if (name != "") {'
            + '      $("#fileInfo").html(name);'
            + '    } else {'
            + '      $("#fileInfo").html("");'
            + '    }'
            + '  }'
            + ''
            + '  var formOptions = {'
            + '   beforeSubmit: validateForm,'
            + '   uploadProgress: showProgress,'
            + '   error: showError,'
            + '   success: showSuccess'
            + '  };'
            + ''
            + (hasFieldForUsername ? checkMsg : '')
            + '  $("#vraisinconnus_form").ajaxForm(formOptions);'
            + '</script>');
        $('.modal').show();
      }
    });
  });
});