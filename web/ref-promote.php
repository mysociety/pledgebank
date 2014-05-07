<?

/* ref-promote.php:
 * Ways of promoting a particular pledge
 *
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: ref-promote.php,v 1.6 2008-09-15 10:00:20 timsk Exp $
 */

include_once '../phplib/pb.php';

page_check_ref(get_http_var('ref'));
$p = new Pledge(get_http_var('ref'));
microsites_redirect($p);
deal_with_pin($p->url_flyers(), $p->ref(), $p->pin());

$params = array(
    'ref' => $p->ref(),
    'pref' => $p->url_typein()
);
page_header(_('Promoting your pledge'), $params);

print h2(_('Promoting your pledge'));

?>
<ul id="promote">
<!--
<li>As a simple first step, why not <a href="<?=$p->url_email()?>">email your friends</a>?

<li>More physically, you could <a href="<?=$p->url_flyers()?>">print out some flyers</a> and put them places.
-->

<li>
<p><strong><?=_('Facebook, Twitter, Pinterest') ?></strong>
<small><?=_("(and other sites that don't allow JavaScript)") ?></small>:
<?=_('Copy the text from the box below to have this automatically updating image:') ?></p>
<a href="<?=$p->url_typein()?>"><img align="right" border="0" src="<?=$p->url_flyer('A7')?>_flyers1_live.png" alt="<?=_('Sign my pledge at PledgeBank') ?>"></a>
<textarea rows="8" cols="45" onclick="this.select()">
<a href="<?=$p->url_typein()?>"><img border="0" src="<?=$p->url_flyer('A7')?>_flyers1_live.png" alt="<?=_('Sign my pledge at PledgeBank') ?>"></a>
</textarea>

<li style="clear:both; padding-top:1em">

<p><strong><?=_('Other blog sites') ?></strong>
<small><?=_('(that allow JavaScript)') ?></small>:
<?=_('Embed the status of your pledge in your blog sidebar by copying the text from the box below:') ?></p>

<div style="width:300px;float:right" id="progress">
<script type="text/javascript" src="<?=$p->url_typein()?>/progress.js"></script>
</div>

<textarea rows="8" cols="45" onclick="this.select()"><script type="text/javascript" src="<?=$p->url_typein()?>/progress.js"></script></textarea>

<form method="post" action="http://www.blogger.com/add-widget">
<input type="hidden" name="widget.title" value="PledgeBank - <?=$p->ref()?>">
<input type="hidden" name="widget.content" value="&lt;script type='text/javascript' src='<?=$p->url_typein()?>/progress.js'&gt;&lt;/script&gt;">
<input type="hidden" name="widget.template" value="&lt;data:content/&gt;">
<input type="hidden" name="infoUrl" value="<?=$p->url_typein()?>">
<input type="image" src="/add2blogger_lg.gif" align="middle" alt="<?=_('Install widget on Blogger') ?>">
</form>

<form action="https://www.typepad.com/t/app/weblog/design/widgets" method="post">
<input type="hidden" name="service_key" value="52616e646f6d4956087744e2a065070944433b7a2248955538dbd9188560ee676724733e853276ac933ecb18dd94f09d">
<input type="hidden" name="service_name" value="PledgeBank">
<input type="hidden" name="service_url" value="http://www.pledgebank.com/">
<input type="hidden" name="long_name" value="PledgeBank <?=$p->ref()?> progress">
<input type="hidden" name="short_name" value="<?=$p->ref()?>">
<input type="hidden" name="content" value="&lt;script type='text/javascript' src='<?=$p->url_typein()?>/progress.js'&gt;&lt;/script&gt;">
<input type="hidden" name="return_url" value="<?=$p->url_typein()?>">
<input type="image" src="/typepad.gif" align="middle" alt="<?=_('Install widget on TypePad') ?>">
</form>

<li><strong>Wordpress</strong>:
<? print _('you can install <a href="/WPwidget.zip">this WordPress widget</a> in the widget folder, and have a configuration page to enable you to pick which pledge you want to follow.');
print _('(Users of Wordpress before version 2.2. will need to <a href="http://automattic.com/code/widgets/">install the widget plugin</a>.)'); ?>

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
