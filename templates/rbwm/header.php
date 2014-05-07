<?
// header.php
// Header of RBWM pledgebank site
// Uses elements copyright Royal Borough of Windsor and Maidenhead,
// and others copyright MySociety from main Pledgebank header
//
// $announcement is displayed below the header if it's not empty; use HTML tags, etc., if you want.
  $announcement = "";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<!--
// // // // //
//
//  The Royal Borough of Windsor and Maidenhead
//  The Townhall, St Ives Road, Maidenhead, SL6 1RF United Kingdom
//
//  Tel: +44 (0)1628 683800
//  Fax: +44 (0)1628 685757
//
//  DX 6422 Maidenhead 1
//
//  Email: mailto:customer.service@rbwm.gov.uk
//
// // // // //
//
//    author: Andrew Scott / Andrew Scott
// published: 2010-10-25
//   updated: 2010-11-10
//  location: http://www.rbwm.gov.uk/web/meetings_council_epetitions.htm
//  template: v5
//
// // // // //
-->


<head>
 <!-- PageID 29542 - published by RedDot 7.5 - 7.5.2.16 - 21542 -->
 <!-- START - Dublin Core DC Meta-Tags -->
 <meta name="DC.title" lang="en" content="e-Petitions" xml:lang="en" />
 <meta name="DC.description" lang="en" content="PARAM_DESCRIPTION." xml:lang="en" />
 <meta name="DC.identifier" scheme="URI" content="PARAM_DC_IDENTIFIER" />
 <meta name="DC.format" scheme="IMT" content="text/html" />
 <meta name="DC.language" scheme="RFC3066" content="en-GB" />
 <meta name="DC.language" scheme="ISO639-2/T" content="eng" />
 <meta name="DC.subject" lang="en" content="Local Government" xml:lang="en" />
 <meta name="eGMS.subject.category" scheme="IPSV" lang="en" content="Online petitions" xml:lang="en" />
 <meta name="eGMS.subject.service" scheme="LGSL" content=" 354" />
 <meta name="DC.coverage" content="UK" />
 <meta name="DC.coverage.spacial" lang="en" content="The Royal Borough of Windsor and Maidenhead, Berkshire, United Kingdom" xml:lang="en" />
 <meta name="DC.date.created" scheme="W3CDTF" content="2010-10-25" />
 <meta name="DC.date.issued" content="PARAM_DATE" />
 <meta name="DC.date.modified"  content="PARAM_DATE" />
 <meta name="DC.rights.copyright" lang="en" content="The Royal Borough of Windsor and Maidenhead" xml:lang="en" />
 <meta name="DC.rights" scheme="URI" content="http://www.rbwm.gov.uk/copyrite.htm" />
 <meta name="DC.creator" content="The Royal Borough of Windsor and Maidenhead, Web-Manager, email mailto:customer.service@rbwm.gov.uk" />
 <meta name="DC.date" content="PARAM_DATE" />
 <meta name="DC.publisher" lang="en" content=" The Royal Borough of Windsor and Maidenhead, The Townhall, St Ives Road, Maidenhead, SL6 1RF United Kingdom - Tel: +44 (0)1628 683800 - Fax: +44 (0)1628 685757 - Email: mailto:customer.service@rbwm.gov.uk" xml:lang="en" /><!-- END - Dublin Core DC Meta-Tags -->
 <!-- START - Standard Meta-Tags -->
 <meta name="identifier" content="PARAM_DC_IDENTIFIER" />
 <meta name="format" content="text/html" />
 <meta name="description" content="PARAM_DESCRIPTION." />
 <meta name="country" content="uk" />
 <meta name="language" content="en" />
 <meta name="rights" content="The Royal Borough of Windsor and Maidenhead. All rights reserved" />
 <meta name="author" content="Andrew Scott" />
 <meta name="creator" content="Andrew Scott" />
 <meta name="publisher" content="The Royal Borough of Windsor and Maidenhead, The Townhall, St Ives Road, Maidenhead, SL6 1RF United Kingdom - Tel: +44 (0)1628 683800 - Fax: +44 (0)1628 685757 - Email: mailto:customer.service@rbwm.gov.uk" />
 <meta name="subject" content="Unitary Authority, Local Government, Council Services" />
 <meta name="keywords" content="petitions, electronic" />
 <meta name="robots" content="all" />
 <meta name="revisit" content="7 days" />
 <meta name="created" content="2010-10-25" />
 <meta name="modified" content="PARAM_DATE" /><!-- END - Standard Meta-Tags -->
 <link rel="shortcut icon" href="http://www.rbwm.gov.uk/graphics/favicon.ico" />
 <meta http-equiv="imagetoolbar" content="no" /><!-- START - CSS Stylesheet Selection -->

<style type="text/css" media="all">@import url('/pb.2.css');</style>
<link rel="stylesheet" type="text/css" media="print" href="/pbprint.css">
<!--[if LT IE 7]><style type="text/css">@import url("/css/ie6.css");</style><![endif]-->
<?
    echo $params['css'];
    if ($lang == 'zh' || $lang == 'eo' || $lang == 'fr' || $lang == 'sk') {
        echo '<style type="text/css" media="all">@import url(\'/css/pb.' . $lang . '.css\');</style>';
    }
    foreach ($params['rss'] as $rss_title => $rss_url) {
        print '<link rel="alternate" type="application/rss+xml" title="' . $rss_title . '" href="'.$rss_url.'">' . "\n";
    }
?>
<?=$params['js_file']?>
<script type="text/javascript" src="/pb.js"></script>
<script type="text/javascript" src="/jslib/utils.js"></script>
<script type="text/javascript" src="/jquery.js"></script>
<script type="text/javascript" src="/gaze.js"></script>
<script type="text/javascript">
  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-660910-11']);
  _gaq.push(['_setDomainName', 'pledgebank.com']);
  _gaq.push(['_setAllowLinker', true]);
  _gaq.push(['_trackPageview']);
  (function() {
    var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
    ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();
</script>

 <script type="text/javascript">
//<![CDATA[
    <!-- // Non javascript aware browser will skip this bit and display plain HTML -->
        document.write('<link href="http://www.rbwm.gov.uk/public/v5_default.css" rel="Stylesheet" title="style1" type="text/css" media="screen" />');
        document.write('<link href="http://www.rbwm.gov.uk/public/v5_largetext.css" rel="Alternate Stylesheet" title="style2" type="text/css" media="screen" />');
        document.write('<link href="http://www.rbwm.gov.uk/public/v5_print.css" rel="Alternate Stylesheet" title="style3" type="text/css" media="screen" />');
        document.write('<link href="http://www.rbwm.gov.uk/public/v5_print.css" rel="Stylesheet" type="text/css" media="print" />');
 //]]>
 </script>
 <script type="text/javascript" src="http://www.rbwm.gov.uk/public/css-switcher.js">
</script><!-- END - CSS Stylesheet Selection -->
 <!-- START - BreakOut of Frames -->

 <script type="text/javascript">
//<![CDATA[
    <!--
        if (top.location!= self.location) {
            top.location = self.location.href
        }
    //-->
 //]]>
 </script><!-- END - BreakOut of Frames -->
 <!-- START: RSS Homepage Only -->

 <script type="text/javascript">
//<![CDATA[
    if (location.href == "http://www.rbwm.gov.uk/index.htm" || location.href == "http://www.rbwm.gov.uk/" )
    {
        document.write('<link rel="alternate" type="application/rss+xml" title="RSS News Feed" href="http://www.rbwm.gov.uk/news_rss.xml" />');
    }
 //]]>
 </script><!-- END: RSS Homepage Only -->


 <title>Pledges | The Royal Borough of Windsor and Maidenhead</title>


 <link href="/microsites/rbwm/styles.css" type="text/css" rel="stylesheet" />
</head>

<body>
 <div id="rbwm-wrapper">
  <div id="rbwm-container">
   <a id="top" name="top"></a>

   <div id="rbwm-header">
    <!--ZOOMSTOP-->
    <noscript>
    <div>
     This browser is not JavaScript aware. For accessibility the default styling is plain html.
    </div></noscript> <!-- START - Hidden 'Skip' Links -->


    <div id="skiplinks">
     <strong>Pledges | The Royal Borough of Windsor and Maidenhead</strong>

     <ul>
      <li><a class="skiplink" href="#content" accesskey="s">Jump to Main Page Content</a>
      </li>


      <li><a class="skiplink" href="#sitemenu">Jump to Site Menu</a>
      </li>
     </ul>
    </div>
    <!-- END - Hidden 'Skip' Links -->
    <a href="http://www.rbwm.gov.uk/"><img src="http://www.rbwm.gov.uk/graphics/rbwm_crest-col_196x75.jpg" alt="The Royal Borough Of Windsor And Maidenhead" class="banner" /></a> <!-- START - Top Menu -->


    <div class="topmenu smalltext">
     | <a href="http://www.rbwm.gov.uk/index.htm" accesskey="1" title="Access key: 1">Home</a> | <a href="http://www.rbwm.gov.uk/user_accessibility.htm" accesskey="0" title="Access key: 0">Accessibility</a> | <a href="http://www.rbwm.gov.uk/help.htm">Help</a> | <a href="http://www.rbwm.gov.uk/sitemap.htm" accesskey="3" title="Access key: 3">Site Map</a> | <a href="http://www.rbwm.gov.uk/contacts.htm" accesskey="9" title="Access key: 9">Contact Us</a> |
    </div>
    <!-- END - Top Menu -->
    <!-- START - Search Box -->


    <div class="sitesearch">
     <form method="get" action="http://www.rbwm.gov.uk/search/search.asp">
      <p><label for="searchbox" title="Access key: 4"><b>Search</b></label> <input type="text" size="20" value="Search" onfocus="form.zoom_query.value=&#39;&#39;" name="zoom_query" id="searchbox" accesskey="4" /> <input class="button" type="submit" value="Go" /></p>
     </form>
    </div>
    <!-- END - Search Box -->
   </div>


   <div class="clearer">
   </div>
   <!-- START - Online 'TABS' -->


   <div id="onlineTabsContainer" class="noprint">
    <div id="onlineDiv">
     <div id="onlineTabs">
      <ul>
       <li><a href="http://www.rbwm.gov.uk/apply_online.htm"><span>Apply Online</span></a>
       </li>
       <!--            <li><a href="http://www.rbwm.gov.uk/csc_online_booking_service.htm"><span>Booking Online</span></a></li> -->


       <li><a href="http://www.rbwm.gov.uk/web/maps_online.htm"><span>Maps Online</span></a>
       </li>


       <li><a href="http://www.rbwm.gov.uk/online_payments_service.htm"><span>Pay Online</span></a>
       </li>


       <li><a href="http://www.rbwm.gov.uk/report_online.htm"><span>Report Online</span></a>
       </li>


       <li><a href="http://www.rbwm.gov.uk/my_account.htm"><span>My Account</span></a>
       </li>
      </ul>
     </div>
    </div>
   </div>
   <!-- END - Online 'TABS' -->


   <div class="clearerSpace">
   </div>


   <div id="rbwm-contentContainer">
    <a name="sitemenu" id="sitemenu"></a> <!-- START: 8693 - LGNL -->
    <!--ZOOMSTOP-->



    <!--ZOOMRESTART-->
    <!-- END: 8693 - LGNL -->

</head>
<body<?=$params['body_id']?><?=$params['body_class']?>>

    <div class="rbwm-content">
     <a name="content" id="content"></a>

     <div class="breadcrumbs">
        Location: <a href="/index.htm">Home</a> » <a href="#">GrandParent</a> » <a href="#">Parent</a> » Pledgebank
    </div>
     <!--ZOOMRESTART-->


     <div class="clearer">
     </div>

<?
    // On the "print flyers from in-page image" page, these top parts are hidden from printing
    if ($params['noprint']) print '<div class="noprint">';

    if ($lang == 'zh') {
?>
<h1 style="padding-bottom:0.25em;"><a id="logo" href="/"><span id="logo_zh"><?=_('Pledge') . _('Bank')?></span>
<small><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></small></a>
<?
    } else {
?>
<h1><a id="logo" href="/"><span id="logo_pledge"><?=_('Pledge')?></span><span id="logo_bank"><?=_('Bank')?></span></a>
<?
    }
?>

<span id="tagline"><small><br><?=_('I&rsquo;ll do it, but <strong>only</strong> if you&rsquo;ll help')?></small></span></h1>

<hr class="v">

<div id="pballheader"><?

    // Start flyers-printing again
    if ($params['noprint']) print '</div> <!-- noprint -->';

    // Display who is logged in
    if ($P) {
        print '<p id="signedon" class="noprint">';
        if ($P->has_name())
            printf(_('Hello, %s'), htmlspecialchars($P->name));
        else
            printf(_('Hello, %s'), htmlspecialchars($P->email));
        print ' <small>(<a href="/logout">';
        print _('this isn\'t you?  click here');
        print '</a>)</small></p>';
    }
?>
</div>

<form id="nav_search" accept-charset="utf-8" action="/search" method="get">
<?=_('Search for pledges:')?> <input type="text" id="q" name="q" size="25" value="<?=htmlspecialchars(get_http_var('q', true))?>"><input type="submit" value="<?=_('Search')?>">
</form>

<?   if ($announcement && ! $params['noprint']) { ?>
  <div id="banner-announcement">
    <?= $announcement ?>
  </div>
<? }?>

<?=$params['banner']?>

<div id="pbcontent">

