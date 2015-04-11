<?php
/**
 * @class GoogleAnalyticsController
 *
 * @package Gaz\AnalyticsBundle\Controller
 * @author Gareth Arnott
 *
 * Class renders the content for Google analytics to function on the site
 */

namespace Gaz\AnalyticsBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\Core\Repository\Values\User\User;
use Symfony\Component\HttpFoundation\Response;

class GoogleAnalyticsController extends Controller{


    /**
     * Renders the google analytics script to the page
     *
     * Also sends required parameters for tracking of custom variables.
     *
     * @return Response
     */
    public function googleAnalyticsAction( ) {

        $response = new Response();

        // should be default
        $response->setPrivate();

        $user = $this->getRepository()->getCurrentUser();

        if( empty( $user ) ) {
            return $response;
        }

        $analyticsService = $this->get( 'gaz_analytics.tracking_information' );
        $userDetails = $analyticsService->getCurrentUserInformation( $user );

        $googleOptions['siteSpeedSampleRate'] = $this->_getSiteSpeedSampleRate();
        $googleOptions['trackingId']          = $this->_getTrackingId();


        return $this->render(
            'GazAnalyticsBundle::google_script.html.twig',
            array( 'google_options'     => $googleOptions,
                   'user_details'       => $userDetails
            ),
            $response
        );
    }

    /**
     * Gets the value of the SiteSpeedSampleRate
     *
     * This determines what percentage of users we monitor for site performance
     *
     */
    private function _getSiteSpeedSampleRate(){

        $siteSpeedSampleRate = 1;// Default sample rate

        if( $this->container->hasParameter( 'google_analytics.site_speed_sample_rate' ) ){
            $siteSpeedSampleRate = $this->container->getParameter( 'google_analytics.site_speed_sample_rate' );
        }

        return $siteSpeedSampleRate;
    }

    /**
     * Gets the value of the tracking ID from the yml
     *
     * Warns us that if no value is set, tracking is failing
     *
     */
    private function _getTrackingId(){

        $trackingId = 'no_code'; //prevent exceptions

        if( $this->container->hasParameter( 'google_analytics.tracking_id' ) ){
            $trackingId = $this->container->getParameter( 'google_analytics.tracking_id' );
        }
        else{
            $logger = $this->get( 'logger' );
            $logger->error('Parameter: "google_analytics.tracking_id" is not set. ANALYTICS TRACKING IS FAILING!');
        }

        return $trackingId;
    }


}
