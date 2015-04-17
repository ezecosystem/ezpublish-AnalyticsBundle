# ezpublish-AnalyticsBundle

A Bundle for eZPublish 5.x which integrates Google Analytics to your site.

It uses `analytics.js` from Google's Universal Analytics.

This bundle is intended as a boilerplate. It will enable the very basics of google analytics tracking, and gives you a template for extending it to use with your own eZ Platform.

## Installation

* Download or clone the Bundle to your ```src/``` directory

* If you are using the eZDemoBundle you can add this file to override the necessary template: 

```twig
# ezpublish/Resources/eZDemoBundle/views/page_footer_script.html.twig
{% block google_meta %}
    {{ render_esi( controller( 'GazAnalyticsBundle:GoogleAnalytics:googleAnalytics' ) ) }}
{% endblock %}
```
* If you are using your own pagelayout.html.twig then place the call to the controller somewhere near the closing `<body>` tag.

## Custom Dimensions
* Sometimes you want to track more than just the page title or path offered natively with Google Analytics. You might want to track the remoteIds for certain object types, or the  Class Type, or a particular field value.

* For that you can use the `setTrackingDataForCurrentPage()` method in the `TrackingInformationHelper` class. By default it is set to track the remoteId's for Content and Location on the page which it is called.

* To call this method add the following line to a custom controller:

```
$this->get( 'gaz_analytics.tracking_information' )
     ->setTrackingDataForCurrentPage( $location, $content );
 ```

* For example, in the DemoBundle, add it to ```DemoController->listBlogPostsAction()``` and visit ```<demo_site>/Blog```