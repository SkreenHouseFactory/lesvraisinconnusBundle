<?php

namespace SkreenHouseFactory\lesVraisInconnusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;

use SkreenHouseFactory\v3Bundle\Api\ApiManager;

class DefaultController extends Controller
{

// PROD  
//    const DAILYMOTION_API_KEY    = 'aa81289b98515cba1e93';
//    const DAILYMOTION_API_SECRET = '2a68d63f959846fd17dc4ae689b685d6804b41e3';

// DEV
    const UPLOAD_PATH     = "/home/myskreen/dev1/v3/web/uploads/";
    const UPLOAD_URL     = "http://v3.dev1.myskreen.typhon.net/uploads/";

    const DAILYMOTION_API_KEY = '6e6a0bed18211400adf7';
    const DAILYMOTION_API_SECRET = '4973b96c068de80195a2cb644437217f4959a529';

    const ERR_FORM        = 1;
    const ERR_CGV         = 2;
    const ERR_UPLOAD_DM   = 3;
    const ERR_FILE_FORMAT = 4;
    const ERR_NETWORK     = 5;
  
    public function indexAction(Request $request)
    {
      $session_uid = $request->cookies->get('myskreen_session_uid');

      if ($session_uid) {
        $api = $this->get('api');
        $userDatas = $api->fetch('session/settings/'.$session_uid);
//        echo $api->url;exit;
//        print_r($userDatas);exit;
      }
      return $this->render('SkreenHouseFactorylesVraisInconnusBundle:Default:index.html.twig');
    }

    public function doneAction(Request $request) {
      // Modifications configuration PHP pour accepter fichiers larges
      set_time_limit(6*60*60);
      $fileName = null; // Initialisation de la variable.
      // On récupère le myskreener_id
      $session_uid = $request->cookies->get('myskreen_session_uid');
      if ($session_uid) {
        $api = $this->get('api');
        $userDatas = $api->fetch('session/settings/'.$session_uid);
//        echo $api->url;exit;
      } else {
        return $this->redirect('http://www.myskreen.com');
      }
      $userId = $userDatas->sk_id;
      if ($request->getMethod() === "POST") {
        $err = false;
        // On vérifie qu'on a bien un form complet
        if (array_key_exists('lvi_title',$_POST) && 
            array_key_exists('lvi_desc',$_POST) && 
            array_key_exists('lvi_cgv',$_POST) && 
            array_key_exists('lvi_file',$_FILES)) {
          $title = $_POST['lvi_title'];
          $desc  = $_POST['lvi_desc'];
          $cgv   = $_POST['lvi_cgv'];
          // On gère immédiatement le pseudo
          if (array_key_exists('lvi_pseudo',$_POST)) {
            $api = $this->get('api');
            $api->fetch('updateUsername', array('sk_id' => $userId, 'username' => $_POST['lvi_pseudo']));
          }
          if ($cgv == 1) {
            $dmApi = new Dailymotion();
            $dmApi->setGrantType(Dailymotion::GRANT_TYPE_PASSWORD, self::DAILYMOTION_API_KEY, self::DAILYMOTION_API_SECRET, array("manage_videos","write","delete"),
                                   array('username' => 'lesvraisinconnus', 'password' => 'skfactory'));
            $session = array();
            // On PUT la vidéo sur Dailymotion
            try
            {
              // On déplace le fichier pour qu'il soit public sur une URL (attention, on vire les espaces, DM n'aime pas du tout !!)
              $fName = explode(".",$_FILES["lvi_file"]["name"]);
              $fName = date('U') . "." . $fName[count($fName) - 1];
              $fileName = self::UPLOAD_PATH . str_replace(" ","_",$fName);
              $fileUrl = self::UPLOAD_URL . str_replace(" ","_",$fName);
error_log("FILE_NAME : " . $fileName,3,"/home/myskreen/dev1/v3/app/logs/dev.log");
error_log("FILE_URL : " . $fileUrl,3,"/home/myskreen/dev1/v3/app/logs/dev.log");
              move_uploaded_file($_FILES["lvi_file"]["tmp_name"],$fileName);
              $result = $dmApi->post('/me/videos', array('url' => $fileUrl, 'title' => $title, 'description' => $desc, 'published'=>false, 'tags'=>array("author_" . $userId)));

error_log("RESULT : " . $result,3,"/home/myskreen/dev1/v3/app/logs/dev.log");
              // On récupère le message réponse de DM
              if (is_array($result) && array_key_exists('id',$result)) {
                // La vidéo a bien été uploadée
                $vidId = $result['id'];
                $params = array(
                  'sk_id'         => $userId,
                  'video_id'      => $vidId,
                  'title'         => $title,
                  'description'   => $desc);
                $api->fetch('vraisInconnus',$params);
                // Si tout va bien, on crée la fiche programme et on lie à l'utilisateur
                // On ajoute aussi une notification à l'utilisateur
                // On envoie un mail à l'utilisateur avec :
                // Si tout va bien, le lien de la vidéo
                // Si problème, on explique le problème
                // Bien sûr, on supprime le fichier vidéo maintenant qu'on est bon.
              } else {
                $err = self::ERR_UPLOAD_DM;
              }
            }
            catch (DailymotionAuthRequiredException $e)
            {
                // Redirect the user to the Dailymotion authorization page
                $err = self::ERR_UPLOAD_DM;
            }
            catch (DailymotionAuthRefusedException $e)
            {
                // Handle case when user refused to authorize
                $err = self::ERR_UPLOAD_DM;
            }
          } else {
            $err = self::ERR_CGV;
          }
        } else {
          $err = self::ERR_FORM;
        }
        if ($err) {
          // Appel API de gestion de l'erreur
          $params = array('error'=> $err,'sk_id'=>$userId);
          $api->fetch('vraisInconnus',$params);
          @unlink($fileName);
        }
        exit;
      } else {
        // Si on n'est pas dans un POST, on redirige vers la page d'accueil
        throw $this->createNotFoundException('Cette URL ne mène visiblement nulle part...');
        @unlink($fileName);
        exit;
      }
      @unlink($fileName);
      exit;
    }
  
    public function indexWithUploaderAction(Request $request)
    {
      $session_uid = $request->cookies->get('myskreen_session_uid');
      $api = $this->get('api');

      $userDatas = $api->fetch('session/settings/'.$session_uid);
      //echo $api->url;
      if (isset($userDatas->error)) {
        return $this->redirect('http://www.myskreen.com');
      }
/*
      $dmApi = new Dailymotion();
      $dmApi->setGrantType(Dailymotion::GRANT_TYPE_PASSWORD, self::DAILYMOTION_API_KEY, self::DAILYMOTION_API_SECRET, null,
                             array('username' => 'lesvraisinconnus', 'password' => 'skfactory'));      
      $session = array();
      try
      {
          $result = $dmApi->get('/me/videos', array('fields' => 'id,title,description'));
          $session = $dmApi->getSession();
      }
      catch (DailymotionAuthRequiredException $e)
      {
          // Redirect the user to the Dailymotion authorization page
      }
      catch (DailymotionAuthRefusedException $e)
      {
          // Handle case when user refused to authorize
          // <YOUR CODE>
      }
*/
      // IFrame uploader partner DM
      $DM_IDENTITY_LOGIN = 'lesVraisInconnus';
      $DM_IDENTITY_EMAIL = 'dev@myskreen.com';
      $DM_UPLOAD_WEB = true;
      $DM_UPLOAD_WEBCAM = true;
      $DM_WIDGET_HEIGHT = 300;
      $DM_WIDGET_WIDTH = 600;

      $DM_WP_UID = '50167efef362edb0f608';
      $DM_WP_SECRET = 'c596a2db8214ca33742dfb5fda40b385c6daf7ea';
      $DM_WP_TIMEOUT = time() + 7200;
      $DM_WP_SIGN = md5(sprintf('%s:%s:%s:%s:%d', $DM_IDENTITY_LOGIN, $DM_IDENTITY_EMAIL, $DM_WP_UID, $DM_WP_SECRET, $DM_WP_TIMEOUT));
      $DM_WP_IDENTITY = sprintf('%s:%s:%s:%d:%s', $DM_IDENTITY_LOGIN, $DM_IDENTITY_EMAIL, $DM_WP_UID, $DM_WP_TIMEOUT, $DM_WP_SIGN);
      $DM_URLBASE = 'http://www.dailymotion.com';
      $DM_UPLOAD_DESTINATION = '';

      $dmIframeCode = '<iframe width="' . $DM_WIDGET_WIDTH . '" height="' . $DM_WIDGET_HEIGHT
                    . '" src="' . $DM_URLBASE . '/widget/upload' . $DM_UPLOAD_DESTINATION . '?identity=' . $DM_WP_IDENTITY
                    . '&web=' . ($DM_UPLOAD_WEB ? 1 : 0) . '&webcam=' . ($DM_UPLOAD_WEBCAM ? 1 : 0) . '"></iframe>';

      return $this->render('SkreenHouseFactorylesVraisInconnusBundle:Default:index.html.twig', array('uploader'=>$dmIframeCode));
    }
}
