<?php
/**
 * Created by PhpStorm.
 * User: GazofNaz
 */

namespace Gaz\AnalyticsBundle\Helper;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\Repository\Values\User\User;
use eZ\Publish\Core\MVC\ConfigResolverInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class TrackingInformationHelper {

    /** @var \eZ\Publish\Core\MVC\ConfigResolverInterface  */
    protected $configResolver;

    /** @var \eZ\Publish\API\Repository\LocationService  */
    protected $locationService;

    /** @var \eZ\Publish\API\Repository\ContentService  */
    protected $contentService;

    /** @var \eZ\Publish\API\Repository\UserService  */
    protected $userService;

    /** @var \eZ\Publish\API\Repository\ContentTypeService  */
    protected $contentTypeService;

    /** @var ContainerInterface */
    protected $container;

    /** @var  \Psr\Log\LoggerInterface $logger */
    protected $logger;

    function __construct( ConfigResolverInterface $configResolver,
                          LocationService $locationService,
                          ContentService $contentService,
                          UserService $userService,
                          ContentTypeService $contentTypeService,
                          ContainerInterface $container,
                          LoggerInterface $logger){
        $this->configResolver = $configResolver;
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->userService = $userService;
        $this->contentTypeService = $contentTypeService;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Populates an array with user data. This data is used to track
     * data in both Google and Webtrends
     *
     * @todo better to use session data here?
     * @todo try catches?
     *
     * @param User $user
     * @return array
     */
    public function getCurrentUserInformation( User $user ){

        $userDetails = array();

        $userDetails['userAccountId'] = $user->getVersionInfo()->getContentInfo()->id;
        $userContentTypeId = $user->getVersionInfo()->getContentInfo()->contentTypeId;
        $userDetails['userClassIdentifier'] = $this->contentTypeService->loadContentType( $userContentTypeId )->getName( 'eng-GB' );

        return $userDetails;
    }

    /**
     * Sets some custom tracking data for the page currently being viewed.
     * Use this as a base to set any custom data you want to send to analytics.
     * For example, LocationId, ContentId or a content field.
     *
     * You should call this method in your controller where you have complete control
     * over the caching mechanisms.
     *
     * Does not return any data - sets it to global twig variables. Implemented
     * this way because you can't pass objects around twig templates when http cache
     * is enabled.
     *
     * @param Location $location
     * @param Content $content
     *
     * @return void
     */
    public function setTrackingDataForCurrentPage( Location $location, Content $content ){

        // Google dimension1
        $this->container->get( 'twig' )->addGlobal( 'google_dimension_3', $content->contentInfo->remoteId );

        // Google dimension2
        $this->container->get( 'twig' )->addGlobal( 'google_dimension_4', $location->remoteId );

    }

}