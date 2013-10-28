<?php

namespace SkreenHouseFactory\lesVraisInconnusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;

use SkreenHouseFactory\v3Bundle\Api\ApiManager;

ini_set('post_max_size', '2048M');
ini_set('upload_max_filesize', '2048M');

class DefaultController extends Controller
{

// PROD  
    // const DAILYMOTION_API_KEY    = 'aa81289b98515cba1e93';
    // const DAILYMOTION_API_SECRET = '2a68d63f959846fd17dc4ae689b685d6804b41e3';

// DEV
   const DAILYMOTION_API_KEY = '6e6a0bed18211400adf7';
   const DAILYMOTION_API_SECRET = '4973b96c068de80195a2cb644437217f4959a529';

    const UPLOAD_PATH     = '/uploads/DM/';

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

    public function getHost(Request $request) {
      $host= gethostname();
      $ip = gethostbyname($host);
      switch($ip) {
        case '78.109.88.183':
          return 'uploads1.myskreen.com/DM/';
        break;
        case '78.109.88.184':
          return 'uploads2.myskreen.com/DM/';
        break;
        default:
          return $request->getHttpHost().self::UPLOAD_PATH;
        break;
      }
    }

    public function doneAction(Request $request) {

      // Modifications configuration PHP pour accepter fichiers larges
      set_time_limit(6*60*60);
      $fileName = null; // Initialisation de la variable.
      $filePath = null;
      // On récupère le myskreener_id
      $session_uid = $request->cookies->get('myskreen_session_uid');
            // mail("yann@myskreen.com", "DailyMotion", "Message :done".$session_uid);
            // echo "1";
      if ($session_uid) {
        $api = $this->get('api');
        $userDatas = $api->fetch('session/settings/'.$session_uid);
//        echo $api->url;exit;
      } else {
        return $this->redirect('http://www.myskreen.com');
      }
      $userId = $userDatas->sk_id;
      // echo "2";
      if ($request->getMethod() === "POST") {
        $err = false;
// echo "3";

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
   // echo "4";
            $dmApi = new Dailymotion();
            $dmApi->setGrantType(
              Dailymotion::GRANT_TYPE_PASSWORD, 
              self::DAILYMOTION_API_KEY, 
              self::DAILYMOTION_API_SECRET, 
              array("manage_videos","write","delete"),
              array('username' => 'lesvraisinconnus', 'password' => 'bangme24')
            );

           // echo $dmApi;
            $session = array();
            // On PUT la vidéo sur Dailymotion
            try
            {
              // On déplace le fichier pour qu'il soit public sur une URL (attention, on vire les espaces, DM n'aime pas du tout !!)
              $fileName = date('YmdHis').'----'.skRewriter::make($_FILES["lvi_file"]["name"], '-', array('.'));
              $filePath = $this->get('kernel')->getRootDir() . '/../web'.self::UPLOAD_PATH . $fileName;
              $fileUrl = 'http://'.$this->getHost($request) . $fileName;
              
              //echo '$fileName:'.$fileName;
              //echo '$filePath:'.$filePath;
              //echo '$fileUrl:'.$fileUrl;
              
              move_uploaded_file($_FILES["lvi_file"]["tmp_name"], $filePath);
              $result = $dmApi->post('/me/videos', array(
                'url' => $fileUrl, 
                'title' => $title, 
                'description' => $desc, 
                'published' => false, 
                'tags' => array("author_" . $userId
              )));

              // On récupère le message réponse de DM
              if (is_array($result) && array_key_exists('id', $result)) {
                // La vidéo a bien été uploadée
                $vidId = $result['id'];
                $params = array(
                  'sk_id'         => $userId,
                  'video_id'      => $vidId,
                  'title'         => $title,
                  'description'   => $desc
                );
                try {
                  $api->fetch('vraisInconnus', $params);
                } catch(Exception $e) {
                  echo 'DefaultController Exception call API';
                  echo 'url:'.$api->url. ' params:';
                  print_r($params);
                }
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
          echo 'ERR:'.$err;
          // Appel API de gestion de l'erreur
          $params = array(
            'error'=> $err,
            'sk_id'=>$userId
          );
          $api->fetch('vraisInconnus',$params);
          @unlink($filePath);
        }
        exit;
      } else {
        // Si on n'est pas dans un POST, on redirige vers la page d'accueil
        throw $this->createNotFoundException('Cette URL ne mène visiblement nulle part...');
        @unlink($filePath);
        exit;
      }
      @unlink($filePath);
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




/**
* Réécriture d'URL
*
* @author   Benoît Bergstörm    <benoit@skreenhouse.com>
*
* @param    string $replace     Liste des caractère à remplacer
* @param    string $erase       Liste des caractère à effacer
*/

class skRewriter
{
    protected static $cache   = array();
    protected static $replace = array( '\'', '“', '”', '%', '/', '\\', '_', '-', '&', '+', '.', '=', '–', '…', '|' );

    protected static $erase   = array( '&ndash', '§', '(', ')', '[', ']', '¡', '', '!', '?',
                                       '¿', ':', ';', '¢', 'þ', '¤', '#', ',', '*', '<', '>',
                                       '«', '»', '°', 'º', '"', '–', '¹', '²', '³', '’',
                                       '›', '…', '–' );

    public static function slug($str)
    {
      return self::make($str, '-', array('+'));
    }

    /**
    * bad encoding for 20minutes DM
    *
    */
    public static function hack($str)
    {
      $str = urldecode(str_replace(array('a%CC%80', 'e%CC%81', '%E2%80%A6', '%C5%93o', '%E2%80%99', '%E2%80%93', '%C5%99', '%C5%92', '%C2%85'), 
                                   array('à', 'é', ' ', 'o', '\'', ' ', ' ', 'oe', ''), 
                                   urlencode($str)));

      return $str;
    }

    /**
    * Réécrit une chaine pour une URL
    *
    * @author   Benoît Bergstörm    <benoit@skreenhouse.com>
    *
    * @param    string  $str        Chaine à réécrire
    * @param    string  $separator  Séparateur de mots
    * @param    array   $keep       Caractères non-modifiables
    *
    * @return   string  $url        La chaine réécrite
    */

    /*
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////

    ATTENTION !!!! WARNING !!!! ATTENTION !!!! WARNING !!!! ATTENTION !!!! WARNING !!!! ATTENTION !!!! WARNING !!!!

    InfodocPeer::getCachePlayerExporte et UrlRewriter::make sont copiées dans plugins/skPlayerExportePlugin/json.php

    ATTENTION !!!! WARNING !!!! ATTENTION !!!! WARNING !!!! ATTENTION !!!! WARNING !!!! ATTENTION !!!! WARNING !!!!

    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
    */

    public static function make( $str, $separator = '-', $keep = array() )
    {
        if (is_array($str)) { //prevent
          $str = implode($separator, $str);
        }

        //pseudo-cache
        if (isset(self::$cache[md5($str.$separator)])) {
          return self::$cache[md5($str.$separator)];
        }

        //$current_encoding = mb_detect_encoding($str, 'auto');
        //echo "\ns:".mb_convert_encoding($str,'UTF-8',mb_detect_encoding($str));
        //$str=Encoding::fixUTF8($str);
        //echo "\nstr:$str utf8_decode:". Encoding::toUTF8($str);//iconv($current_encoding, 'UTF-8', $str);

        // On reviendra à str si la réécriture aboutit à une chaîne vide
        $url = self::hack($str);

        // Modification de la casse

        if ( !in_array( 'uppercase', $keep ) )
        {
            $url = strtolower( $url );
        }

        // Efface et remplace

        $url = str_replace( array_diff( self::$erase, $keep ), '', $url );
        $url = str_replace( array_diff( self::$replace, $keep), $separator, $url );

        // Remplacements divers

        if ( !in_array( '@', $keep ) )
        {
            $url = str_replace( '@', 'a', $url );
        }

        if ( !in_array( '+', $keep ) )
        {
            $url = str_replace( '+', 'plus', $url );
        }

        // Remplacements au cas par cas

        $url = str_replace( '$', 'dollar'.$separator, $url );
        $url = str_replace( '€', 'euro'.$separator, $url );

        $url = str_replace( 'Å', 'A', $url );
        $url = str_replace( 'Ä', 'A', $url );
        $url = str_replace( 'Â', 'A', $url );
        $url = str_replace( 'Á', 'A', $url );
        $url = str_replace( 'Ã', 'A', $url );
        $url = str_replace( 'À', 'A', $url );
        $url = str_replace( 'ã', 'a', $url );
        $url = str_replace( 'á', 'a', $url );
        $url = str_replace( 'à', 'a', $url );
        $url = str_replace( 'â', 'a', $url );
        $url = str_replace( 'ä', 'a', $url );
        $url = str_replace( 'å', 'a', $url );
        $url = str_replace( 'â', 'a', $url );
        $url = str_replace( 'ÿ', 'y', $url );
        $url = str_replace( 'Ÿ', 'y', $url );
        $url = str_replace( 'Ì', 'I', $url );
        $url = str_replace( 'Ï', 'I', $url );
        $url = str_replace( 'Í', 'I', $url );
        $url = str_replace( 'Î', 'I', $url );
        $url = str_replace( 'í', 'i', $url );
        $url = str_replace( 'î', 'i', $url );
        $url = str_replace( 'ì', 'i', $url );
        $url = str_replace( 'í', 'i', $url );
        $url = str_replace( 'ï', 'i', $url );
        $url = str_replace( 'Ê', 'E', $url );
        $url = str_replace( 'È', 'E', $url );
        $url = str_replace( 'É', 'E', $url );
        $url = str_replace( 'Ë', 'E', $url );
        $url = str_replace( 'E', 'e', $url );
        $url = str_replace( 'ë', 'e', $url );
        $url = str_replace( 'è', 'e', $url );
        $url = str_replace( 'é', 'e', $url );
        $url = str_replace( 'ê', 'e', $url );
        $url = str_replace( 'è', 'e', $url );
        $url = str_replace( 'Ò', 'O', $url );
        $url = str_replace( 'Ó', 'O', $url );
        $url = str_replace( 'Ô', 'O', $url );
        $url = str_replace( 'Õ', 'O', $url );
        $url = str_replace( 'Ö', 'O', $url );
        $url = str_replace( 'ó', 'o', $url );
        $url = str_replace( 'õ', 'o', $url );
        $url = str_replace( 'ð', 'o', $url );
        $url = str_replace( 'ô', 'o', $url );
        $url = str_replace( 'ö', 'o', $url );
        $url = str_replace( 'ò', 'o', $url );
        $url = str_replace( 'Ú', 'U', $url );
        $url = str_replace( 'Û', 'U', $url );
        $url = str_replace( 'Ù', 'U', $url );
        $url = str_replace( 'ù', 'u', $url );
        $url = str_replace( 'û', 'u', $url );
        $url = str_replace( 'ú', 'u', $url );
        $url = str_replace( 'ü', 'u', $url );
        $url = str_replace( 'Ü', 'U', $url );
        $url = str_replace( 'Ò', 'O', $url );
        $url = str_replace( 'Ó', 'O', $url );
        $url = str_replace( 'Ô', 'O', $url );
        $url = str_replace( 'Õ', 'O', $url );
        $url = str_replace( 'Ö', 'O', $url );
        $url = str_replace( 'ó', 'o', $url );
        $url = str_replace( 'õ', 'o', $url );
        $url = str_replace( 'ð', 'o', $url );
        $url = str_replace( 'ô', 'o', $url );
        $url = str_replace( 'ö', 'o', $url );
        $url = str_replace( 'ò', 'o', $url );
        $url = str_replace( 'Ú', 'U', $url );
        $url = str_replace( 'Û', 'U', $url );
        $url = str_replace( 'Ù', 'U', $url );
        $url = str_replace( 'ù', 'u', $url );
        $url = str_replace( 'û', 'u', $url );
        $url = str_replace( 'ú', 'u', $url );
        $url = str_replace( 'ü', 'u', $url );
        $url = str_replace( 'Ü', 'U', $url );
        $url = str_replace( 'ç', 'c', $url );
        $url = str_replace( 'Ç', 'C', $url );
        $url = str_replace( 'ñ', 'n', $url );
        $url = str_replace( 'Ñ', 'N', $url );
        $url = str_replace( 'ý', 'y', $url );
        $url = str_replace( 'ø', 'o', $url );
        $url = str_replace( 'ß', 'ss', $url );
        $url = str_replace( 'æ', 'ae', $url );
        $url = str_replace( 'Æ', 'ae', $url );
        $url = str_replace( 'œ', 'oe', $url );
        $url = str_replace( 'Œ', 'oe', $url );
        $url = str_replace( '½', 'et-demi', $url );

        // Traitement des espaces
        $url = trim( $url );
        $url = str_replace( ' ', $separator, $url );
 
 			 	// __ => start protect spaces
				$url = str_replace(' ', '__', $url);

        // Espace insécable
        $url = urldecode( str_replace( '%C2%A0', $separator, urlencode( $url ) ) );

        // unicode, après les replaces : __ => protect spaces
        if (count($keep) == 0) { //ne marche pas avec keep ! => + >>
          $url = preg_replace('/\%[A-Z0-9]{2}/i', '', urlencode($url));
        }
 
 			 	// __ => end protect spaces
				$url = str_replace('__', ' ', $url);

        // Gestion des incohérences du traitement
        $url = str_replace( $separator.$separator.$separator, $separator, $url );
        $url = str_replace( $separator.$separator, $separator, $url );

        // Caractère '10' (= New Line en ASCII)
        // Bug -> caractère affiché comme un espace mais pas remplacé, posait un problème dans le routing pour le sitemap
        $url = str_replace( chr( 10 ), $separator, $url );
        
        // hack bug utf8
        //echo utf8_decode($url);
        //$url = str_replace('\u', '_UNICODE_', $url);
        //$url = preg_replace('/_UNICODE_(\d+)/i', '', $url);
        //$url = urldecode(str_replace( array('%C2%92','%C2%8C','%C2%9C','%E2%80%9E','%C2%96'), array($separator,'oe',''), urlencode($url) ));
        
        
        //un petit dernier strtolower pour la route si on a remplacé des caracteres avant
        if ( !in_array( 'uppercase', $keep ) ) {
            $url = strtolower( $url );
        }

        // Retour à la chaine originale si échec réécriture
        if ( !$url ) {
            $url = $str;
        }

        if ( substr( $url, 0, 1 ) == $separator ) {
            $url = substr( $url, 1, strlen( $url ) );
        }

        if ( substr( $url, ( strlen( $url ) - 1 ), 1 ) == $separator ) {
            $url = substr( $url, 0, ( strlen( $url ) - 1 ) );
        }

        //mise en cache
        self::$cache[md5($str.$separator)] = $url;

        return $url;
    }
}
