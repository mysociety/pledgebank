<?
// header.php
// Header of main pledgebank.com
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

/* XXX @import url('...') uses single-quotes to hide the style-sheet
 * from Mac IE. Ugly, but it works. */

// $announcement is displayed below the header if it's not empty; use HTML tags, etc., if you want.
  $announcement = "";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?=$lang ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?=$params['robots']?>
<title><?=$title?></title>

<link rel="stylesheet" href="//fonts.googleapis.com/css?family=Source+Sans+Pro:400,600">
<link rel="stylesheet" type="text/css" media="all" href="/assets/css/banner.css">

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
</head>
<body<?=$params['body_id']?><?=$params['body_class']?>>

<div class="retirement-banner retirement-banner--pledgebank">
  <div class="retirement-banner__inner">
    <a class="retirement-banner__logo" href="https://www.mysociety.org/">mySociety</a>
    <p class="retirement-banner__description">With regret, we’ve made the
    difficult decision to close this site down from the end of June.</p>
    <p class="retirement-banner__description">You can still browse and sign
    existing pledges, but you can no longer create new ones. <a
    class="retirement-banner__more" href="https://www.mysociety.org/2015/01/28/goodbye-to-some-old-friends/">Find out more&hellip;</a></p>
  </div>
</div>
<div class="retirement-replacement-body">

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

<span id="countrytitle"><a href="/where"><?=pb_site_country_name()?></a></span>
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

