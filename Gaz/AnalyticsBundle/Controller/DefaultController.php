<?php

namespace Gaz\AnalyticsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('GazAnalyticsBundle:Default:index.html.twig', array('name' => $name));
    }
}
