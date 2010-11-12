<?
// header.php:
// Header for catcomm PledgeBank
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html lang="<?=$lang ?>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?=$params['robots']?>
<title>
<?
    if ($title) 
        print strip_title($title) . " - ";
    echo strip_title(_('PledgeBank'));
    if (!$title) print ' - ' . _("Tell the world \"I'll do it, but only if you'll help\"");
?>
</title>
<style type="text/css" media="all">@import url('/microsites/autogen/catcomm.css');</style>
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
?>

<a href="http://www.catcomm.org"><img src="/microsites/catcomm-logo.png" alt="Catalytic Communities" align="left"
    style="margin-top: -10px; margin-left: -0.4em; background-color: #ffffff; float: left; border: solid 2px #21004a; padding: 0px; margin: 10px;"></a>
<h1>
<a id="logo" href="/"><span id="logo_pledge">Pledge</span><span id="logo_bank">Bank</span></a><span id="beta">Beta</span>
<span id="countrytitle"><a href="/where"><?=_('(other PledgeBanks)')?></a></span>
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

