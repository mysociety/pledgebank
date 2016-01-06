<?
// header.php
// Header of RBWM pledgebank site
// Uses elements copyright Royal Borough of Windsor and Maidenhead,
// and others copyright MySociety from main Pledgebank header
//
// $announcement is displayed below the header if it's not empty; use HTML tags, etc., if you want.
  $announcement = "";
?>
<!DOCTYPE html>
<!--[if lt IE 7]>       <html class="no-js lt-ie10 lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>          <html class="no-js lt-ie10 lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>          <html class="no-js lt-ie10 lt-ie9" lang="en"> <![endif]-->
<!--[if IE 9]>          <html class="no-js lt-ie10" lang="en"> <![endif]-->
<!--[if gt IE 9]><!-->
<html class="no-js" lang="en"><!--<![endif]--><head>
<meta http-equiv="content-type" content="text/html; charset=UTF-8">
        <script>
            (function (html) {
                html.className = html.className.replace(/\bno-js\b/, '');
            })(document.getElementsByTagName('html')[0]);
        </script>
        <link rel="alternate" type="application/rss+xml" title="RSS" href="http://www3.rbwm.gov.uk/rss/news">
        <link rel="shortcut icon" type="image/x-icon" href="//www3.rbwm.gov.uk/site/favicon.ico">
        <link rel="apple-touch-icon" href="//www3.rbwm.gov.uk/site/apple-touch-icon.png">
        <link rel="stylesheet" href="//fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,600italic,700italic,300,400,600,700">
        <link rel="stylesheet" href="/microsites/rbwm/css/font-awesome.css">
        <link rel="stylesheet" href="/microsites/rbwm/css/base.css">
<!--[if lte IE 8]>
        <link rel="stylesheet" href="/microsites/rbwm/css/rbwm-oldie.css">
        <script src="/microsites/rbwm/js/html5shiv.min.js"></script>
<![endif]-->
<!--[if gt IE 8]><!-->
        <link rel="stylesheet" href="/microsites/rbwm/css/rbwm.css">
<!--<![endif]-->
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <meta name="description" content="">
        <meta name="keywords" content="">

        <script src="/microsites/rbwm/js/modernizr.js"></script>
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
        <link href="/microsites/rbwm/styles.css" type="text/css" rel="stylesheet">

      <title>Pledges | The Royal Borough of Windsor and Maidenhead</title>
    </head>
    <body<?=$params['body_id']?><?=$params['body_class']?>>

        <header role="banner" class="site-header" id="top">
            <ul class="visually-hidden skip-links">
                <li>
                    <a href="#content" rel="nofollow">Skip to content</a>
                </li>
                <li>
                    <a href="#navigation" rel="nofollow">Skip to main navigation</a>
                </li>
            </ul>
            
            <div class="logo">
                <a href="http://www3.rbwm.gov.uk/">
                    <img src="/microsites/rbwm/img/logo.png" alt="">
                    <span class="visually-hidden">The Royal Borough of Windsor and Maidenhead</span>
                </a>
            </div>
			<div class="search">
				<div id="sb-search" class="sb-search sb-search-open">
                    <form action="http://www3.rbwm.gov.uk/search/search.asp" method="get">
                        <label for="zoom_query" class="visually-hidden">Search this site</label>
                        <input class="sb-search-input" placeholder="Search website (Planning)" size="18" maxlength="40" name="zoom_query" id="zoom_query" type="search">
                        <button class="sb-search-submit">
                            <span class="sb-icon-search icon-search"></span>
                            <span class="visually-hidden">Search</span>
                        </button>
                    </form>
				</div>
			</div>
        </header>
	
<!-- TOP NAVIGATION -->        
        <div class="navigation" id="navigation">
			<a aria-hidden="true" class="nav-toggle" href="#">Menu</a><nav aria-hidden="false" class="nav-collapse nav-collapse-0 closed">
				<div class="inner">
<ul>
                        <li>
                            <a href="http://www3.rbwm.gov.uk/">Home</a>
                        </li>
                        <li>
                            <a href="http://www3.rbwm.gov.uk/info/200127/contact_the_council/60/contact_us">Contact Us</a>
                        </li>
                        <li>
                            <a href="http://www3.rbwm.gov.uk/info/200409/data_protection/59/privacy_and_cookies">Privacy &amp; Cookies</a>
                        </li>
					</ul>				</div>
			</nav>
		</div>
<!-- TOP NAVIGATION END -->
        <main class="main" id="content" role="main">
            <div class="title-banner">
                <div class="container">
<!-- PAGE TITLE -->   
                    <a href="/"><h1>PledgeBank</h1></a>
<!-- PAGE TITLE END -->
                </div>
            </div>

            <div class="container">
                <nav class="breadcrumb">
<!-- BREADCRUMB -->
                    <ol>
                        <li>
                            <a href="http://www3.rbwm.gov.uk/" rel="home">Home</a>
                        </li>
                        <li>
                            <a href="/">PledgeBank</a>
                        </li>
                    </ol>
<!-- BREADCRUMB END -->
                </nav>
                <span id="tagline"><?=_('I&rsquo;ll do it, but <strong>only</strong> if you&rsquo;ll help')?></span>
            </div>

            <article class="main-content">
                <div class="container">  
<!-- MAIN CONTENT -->


<hr class="v">

<div id="pballheader"><?

    // Start flyers-printing again
    if ($params['noprint']) print '</div> <!-- noprint -->';

    // Display who is logged in
    print '<p id="signedon" class="noprint">';
    if ($P) {
        if ($P->has_name())
            printf(_('Hello, %s'), htmlspecialchars($P->name));
        else
            printf(_('Hello, %s'), htmlspecialchars($P->email));
        print ' <small>(<a href="/logout">';
        print _('this isn\'t you?  click here');
        print '</a>)</small>';
        print '<br /><a href="/my">View your pledges</a>';
    }
    else {
        print '<a class="login" href="/my">Log In</a>';
    }
    print '</p>';
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
