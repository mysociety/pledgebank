<?
// header.php
// Header of main pledgebank.com
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

/* XXX @import url('...') uses single-quotes to hide the style-sheet
 * from Mac IE. Ugly, but it works. */
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?=$lang ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?=$params['robots']?>
<title><?=$title?></title>

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
</head>
<body<?=$params['id']?>>
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

<?=$params['banner']?>

<div id="pbcontent">

