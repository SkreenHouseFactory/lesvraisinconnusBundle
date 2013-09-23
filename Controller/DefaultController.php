<?php

namespace SkreenHouseFactory\lesVraisInconnusBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;

use SkreenHouseFactory\v3Bundle\Api\ApiManager;

class DefaultController extends Controller
{
  
    const DAILYMOTION_API_KEY    = '50167efef362edb0f608';
    const DAILYMOTION_API_SECRET = 'c596a2db8214ca33742dfb5fda40b385c6daf7ea';
    
    public function indexAction(Request $request)
    {
      $session_uid = $request->cookies->get('myskreen_session_uid');
      $api = $this->get('api');

      $userDatas = $api->fetch('session/settings/'.$session_uid);
      //echo $api->url;
      if (isset($userDatas->error)) {
        return $this->redirect('http://www.myskreen.com');
      }

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
      return $this->render('SkreenHouseFactorylesVraisInconnusBundle:Default:index.html.twig', array('session'=>$session));
    }
}
