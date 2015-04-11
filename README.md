# ezpublish-AnalyticsBundle

A Bundle for eZPublish 5.x which integrates Google Analytics to your site.

It uses `analytics.js` from Google's Universal Analytics.

## Installation

If you are using the eZDemoBundle you can add this file to override the necessary template: 

```twig
# ezpublish/Resources/eZDemoBundle/views/page_footer_script.html.twig
{% block google_meta %}
    {{ render_esi( controller( 'GazAnalyticsBundle:GoogleAnalytics:googleAnalytics' ) ) }}
{% endblock %}
```
If you are using your own pagelayout.html.twig then place the call to the controller somewhere near the closing `<body>` tag.
