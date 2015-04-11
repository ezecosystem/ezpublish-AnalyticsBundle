<?php
/**
 * Created by PhpStorm.
 * User: Gareth Arnott
 */

namespace Gaz\AnalyticsBundle\Helper;

use eZ\Publish\API\Repository\ContentService;
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

    /** @var ContainerInterface */
    protected $container;

    /** @var  \Psr\Log\LoggerInterface $logger */
    protected $logger;

    function __construct( ConfigResolverInterface $configResolver,
                          LocationService $locationService,
                          ContentService $contentService,
                          UserService $userService,
                          ContainerInterface $container,
                          LoggerInterface $logger){
        $this->configResolver = $configResolver;
        $this->locationService = $locationService;
        $this->contentService = $contentService;
        $this->userService = $userService;
        $this->container = $container;
        $this->logger = $logger;
    }

    /**
     * Populates an array with user data. This data is used to track
     * data in both Google and Webtrends
     *
     * @param User $user
     * @return array
     */
    public function getCurrentUserInformation( User $user ){

        $session = $this->container->get('session');

        $isMasquerade = $session->get( 'masquerade' );

        // it's vital we append -masked to all masqueraded page views.
        // This prevents false positives appearing in the data provided to clients.
        $maskedString = $isMasquerade ? '-masked' : '';

        $userDetails = array();

        $samsUserHashType = $this->configResolver->getParameter( 'password_hash.sams', 'gaz' );

        $localUserHashType = $this->configResolver->getParameter( 'password_hash.local', 'gaz' );

        //sams users
        if( $user->hashAlgorithm == $samsUserHashType ){

            $userDetails['sams_parent_company_name']  = $session->get( 'companyName') . $maskedString;//GA/WT
            $userDetails['sams_parent_account_id']    = $session->get('samsCompanyId') . $maskedString;//GA/WT
            $userDetails['acc_id']                    = $session->get('samsUserId') . $maskedString;//GA/WT
            $userDetails['full_name']                 = $session->get( 'userFullName' ) . $maskedString;//WT
            $userDetails['email']                     = $user->email . $maskedString;//WT
            $userDetails['sams_account_manager_name'] = $session->get( 'userAccountManger' ) . $maskedString;//WT
            $userDetails['job_title']                 = $session->get( 'userJobTitle' ) . $maskedString;//WT
            $userDetails['job_group']                 = $session->get( 'userJobGroup' ) . $maskedString;//WT
        }
        //allow tracking of admin users
        elseif( $user->hashAlgorithm == $localUserHashType ){

            $userDetails['sams_parent_company_name']  = 'local-user';//GA/WT
            $userDetails['sams_parent_account_id']    = 'local-user';//GA/WT
            $userDetails['acc_id']                    = 'local-user-' . $user->getVersionInfo()->getContentInfo()->id;//GA/WT
        }

        return $userDetails;
    }

    /**
     * Sets the relevant tracking data for the report currently being viewed
     *
     * Does not return any data - sets it to global twig variables. Implemented
     * this way because you can't pass objects around twig templates when http cache
     * is enabled.
     *
     * @param $reportLocation
     * @param $reportContent
     * @param $chapterLocationId
     *
     * @return void
     */
    public function setCurrentReportTrackingData( Location $reportLocation, Content $reportContent, $chapterLocationId ){

        // report id
        $this->container->get( 'twig' )->addGlobal( 'global_report_id', $reportContent->contentInfo->remoteId );

        // RS name
        $researchStreamName = $this->getResearchStreamName( $reportLocation );
        $this->container->get( 'twig' )->addGlobal( 'global_research_stream', $researchStreamName );

        // publication date
        $publicationDate = $reportLocation->contentInfo->publishedDate;
        $this->container->get( 'twig' )->addGlobal( 'global_publication_date', $publicationDate );

        // report title
        $reportTitle = $reportContent->contentInfo->name;
        $this->container->get( 'twig' )->addGlobal( 'global_report_title', $reportTitle );

        // chapter title
        $chapterTitle = $this->getChapterNameByLocationId( $chapterLocationId );
        $this->container->get( 'twig' )->addGlobal( 'global_chapter_title', $chapterTitle );
        $contentType = null;
        //$contentType = $this->productService->getContentTypeFromLocation( $reportLocation );

        if( $contentType != null ) {

            // product type
            $productType = $this->getProductType($reportContent, $contentType->identifier);
            $this->container->get('twig')->addGlobal('global_product_type', $productType['type']);
            $this->container->get('twig')->addGlobal('global_product_group', $productType['group']);

            // author, always get first object
            $authorName = $this->getAuthorName( $reportContent, $contentType->identifier );
            $this->container->get( 'twig' )->addGlobal( 'global_lead_author', $authorName );
        }

    }

    /**
     * Fetches the Research Stream name the content resides in
     *
     * @param Location $contentLocation
     *
     * @return string
     */
    protected function getResearchStreamName( Location $contentLocation ) {
        $researchStreamName = '';
        $researchStreamLocation = null;
        //$researchStreamLocation = $this->productService->getParentContainerLocation( $contentLocation );
        if( $researchStreamLocation instanceof Location ) {
            $researchStreamName = $researchStreamLocation->contentInfo->name;
        }

        return $researchStreamName;
    }

    /**
     * Returns the appropriate author name based on the content type
     *
     * @param Content $content
     * @param         $contentType - This is passed in so we don't have to keep fetching it
     *
     * @return string
     */
    protected function getAuthorName( Content $content, $contentType ) {
        $authorName = '';
        if( $contentType == $this->configResolver->getParameter( 'class_identifier.product', 'gaz' ) ) {
            $leadAuthorContentId = $content->getFieldValue('lead_author')->destinationContentIds[0];
            $authorName = $this->contentService->loadContent($leadAuthorContentId)->contentInfo->name;
        }
        elseif( $contentType == $this->configResolver->getParameter( 'class_identifier.conference_paper', 'gaz' ) ) {
            $authorName = $content->getFieldValue( 'author' );
        }

        return $authorName;
    }

    protected function getProductType( Content $content, $contentType ) {
        $productType = array(
            'type'  => '',
            'group' => '',
        );

        if( $contentType == $this->configResolver->getParameter( 'class_identifier.product', 'gaz' ) ) {
            $reportTypeId = $content->getFieldValue('product_type')->destinationContentIds[1];
            // although product type is a required field, it sometimes (once) was not set
            if( $reportTypeId ){
                $productType['type'] = $this->getContentNameById( $reportTypeId );
            }
            //product group
            $reportGroupId = $content->getFieldValue('product_type')->destinationContentIds[0];
            if( $reportGroupId ){
                $productType['group'] = $this->getContentNameById( $reportGroupId );
            }
        }
        elseif( $contentType == $this->configResolver->getParameter( 'class_identifier.conference_paper', 'gaz' ) ) {
            $productType['type'] = 'Conference Paper';
        }
        elseif( $contentType == $this->configResolver->getParameter( 'class_identifier.news', 'gaz' ) ) {
            $productType['type'] = 'News';
        }
        elseif( $contentType == $this->configResolver->getParameter( 'class_identifier.newsletter_issue', 'gaz' ) ) {
            $productType['type'] = 'Newsletter Issue';
        }
        else{
            // badly configured product.
            $productType['type'] = '';
        }

        return $productType;
    }

    /**
     * Sets the chapter title for a report
     *
     * Sets a generic message if no chapter title is found
     *
     * @param $locationId
     * @return string
     */
    protected function getChapterNameByLocationId( $locationId ){

        $chapterTitle = 'no_chapter_title';
        try{
            $chapterTitle = $this->locationService->loadLocation( $locationId )->contentInfo->name;
        }
        catch( \eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e ){
            $this->logger->info( 'User not authorized to read chapter locationID (' . $locationId . '): ' . $e->getMessage() );
        }
        catch( \eZ\Publish\API\Repository\Exceptions\NotFoundException $e ){
            $this->logger->warning( 'The specified chapter location (' . $locationId . ') could not be found: ' . $e->getMessage() );
        }
        catch( \Exception $e ) {
            $this->logger->alert( 'An unexpected exception occurred loading name from location (' .$locationId . '): ' . $e->getMessage() );
        }

        return $chapterTitle;
    }

    /**
     * Gets content name using basic call of loadContent
     *
     * Catches NotFound & Unauthorized exceptions and logs them to warning.
     *
     * @param $contentId
     * @return Content|null
     */
    private function getContentNameById( $contentId ){

        $contentName = null;

        try{
            $contentName = $this->contentService->loadContent( $contentId )->contentInfo->name;
        }
        catch(\eZ\Publish\API\Repository\Exceptions\NotFoundException $e ){
            $this->logger->warning( 'The specified content ID (' . $contentId . ') could not be found: ' .$e->getMessage() );
        }
        catch(\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e ){
            $this->logger->info( 'User not authorized to read content ID (' . $contentId . '): ' . $e->getMessage() );
        }
        catch( \Exception $e ) {
            $this->logger->alert( 'An unexpected exception occurred loading the content (' . $contentId . ') name: ' . $e->getMessage() );
        }

        return $contentName;
    }
}