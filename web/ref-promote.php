<?

/* ref-promote.php:
 * Ways of promoting a particular pledge
 *
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: ref-promote.php,v 1.3 2007-06-27 17:21:58 matthew Exp $
 */

include_once '../phplib/pb.php';

page_check_ref(get_http_var('ref'));
$p = new Pledge(get_http_var('ref'));
microsites_redirect($p);

$pin_box = deal_with_pin($p->url_flyers(), $p->ref(), $p->pin());
if ($pin_box) {
    page_header(_("Enter PIN"));
    print $pin_box;
    page_footer();
    exit;
}

$params = array(
    'ref' => $p->ref(),
    'pref' => $p->url_typein()
);
page_header('Promoting your pledge', $params);

?>
<h2>Promoting your pledge</h2>

<style type="text/css">
li {
    margin: 1em 0;
}
</style>

<ul>
<!--
<li>As a simple first step, why not <a href="<?=$p->url_email()?>">email your friends</a>?

<li>More physically, you could <a href="<?=$p->url_flyers()?>">print out some flyers</a> and put them places.
-->

<li>
<p><strong>MySpace, LiveJournal</strong>
<small>(and other sites that don't allow JavaScript)</small>:
Copy the text from the box below to have this
automatically updating image:</p>
<a style="float:right" href="<?=$p->url_typein()?>"><img border="0" src="<?=$p->url_flyer('A7')?>_flyers1_live.png" alt="Sign my pledge at PledgeBank"></a>
<textarea rows="8" cols="45" onclick="this.select()">
<a href="<?=$p->url_typein()?>"><img border="0" src="<?=$p->url_flyer('A7')?>_flyers1_live.png" alt="Sign my pledge at PledgeBank"></a>
</textarea>

<li style="clear:both; padding-top:1em">

<p><strong>Other blog sites</strong>
<small>(that allow JavaScript)</small>:
Embed the status of your pledge in your blog sidebar by copying the text from the box below:</p>

<div style="width:300px;float:right" id="progress">
<script type="text/javascript" src="<?=$p->url_typein()?>/progress.js"></script>
</div>

<textarea rows="8" cols="45" onclick="this.select()"><script type="text/javascript" src="<?=$p->url_typein()?>/progress.js"></script></textarea>

<form method="post" action="http://www.blogger.com/add-widget">
<input type="hidden" name="widget.title" value="PledgeBank - <?=$p->ref()?>">
<input type="hidden" name="widget.content" value="&lt;script type='text/javascript' src='<?=$p->url_typein()?>/progress.js'&gt;&lt;/script&gt;">
<input type="hidden" name="widget.template" value="&lt;data:content/&gt;">
<input type="hidden" name="infoUrl" value="<?=$p->url_typein()?>">
<input type="image" src="/add2blogger_lg.gif" align="middle" alt="Install widget on Blogger">
</form>

<form action="https://www.typepad.com/t/app/weblog/design/widgets" method="post">
<input type="hidden" name="service_key" value="52616e646f6d4956087744e2a065070944433b7a2248955538dbd9188560ee676724733e853276ac933ecb18dd94f09d">
<input type="hidden" name="service_name" value="PledgeBank">
<input type="hidden" name="service_url" value="http://www.pledgebank.com/">
<input type="hidden" name="long_name" value="PledgeBank <?=$p->ref()?> progress">
<input type="hidden" name="short_name" value="<?=$p->ref()?>">
<input type="hidden" name="content" value="&lt;script type='text/javascript' src='<?=$p->url_typein()?>/progress.js'&gt;&lt;/script&gt;">
<input type="hidden" name="return_url" value="<?=$p->url_typein()?>">
<input type="image" src="/typepad.gif" align="middle" alt="Install widget on TypePad">
</form>

<li><strong>Wordpress</strong>:
you can install <a href="/WPwidget.zip">this WordPress widget</a> in the widget folder, and have a configuration page to enable you to pick which pledge you want to follow. (Users of Wordpress before version 2.2. will need to <a href="http://automattic.com/code/widgets/">install the widget plugin</a>.)

<!--

<li>If/when we have pledge signer "public"ness, widget of "Pledges I've signed" is an obvious one, or "pledges like ones I've signed". Any others we can think of that I haven't?

<li>Or this, to show related pledges to a particular pledge:
<br><textarea rows="4" cols="50"><script type="text/javascript" src="<?=$p->url_typein()?>/related.js"></script></textarea>

<div style="width:300px;margin-top:1em">
<script type="text/javascript" src="<?=$p->url_typein()?>/related.js"></script>
</div>

-->

</ul>
<?

page_footer();
