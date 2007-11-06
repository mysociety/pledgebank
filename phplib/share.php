<?php
/*
 * share.php:
 * Code for Share this functionality.
 * Starting point of Alex King's code
 * 
 * Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
 *
 * $Id: share.php,v 1.3 2007-11-06 14:30:03 matthew Exp $
 * 
 */

$share_sites = array(
    'delicious' => array(
        'name' => 'del.icio.us',
        'url' => 'http://del.icio.us/post?url={url}&title={title}'
    ),
    'digg' => array(
        'name' => 'Digg',
        'url' => 'http://digg.com/submit?phase=2&url={url}&title={title}'
    ),
    'facebook' => array(
        'name' => 'Facebook',
        'url' => 'http://facebook.com/share.php?u={url}&t={title}'
    ),
    'furl' => array(
        'name' => 'Furl',
        'url' => 'http://furl.net/storeIt.jsp?u={url}&t={title}'
    ),
    'netscape' => array(
        'name' => 'Netscape',
        'url' => ' http://www.netscape.com/submit/?U={url}&T={title}'
    ),
    'yahoo_myweb' => array(
        'name' => 'Yahoo! My Web',
        'url' => 'http://myweb2.search.yahoo.com/myresults/bookmarklet?u={url}&t={title}'
    ),
    'stumbleupon' => array(
        'name' => 'StumbleUpon',
        'url' => 'http://www.stumbleupon.com/submit?url={url}&title={title}'
    ),
    'google_bmarks' => array(
        'name' => 'Google Bookmarks',
        'url' => '  http://www.google.com/bookmarks/mark?op=edit&bkmk={url}&title={title}'
    ),
    'technorati' => array(
        'name' => 'Technorati',
        'url' => 'http://www.technorati.com/faves?add={url}'
    ),
    'blinklist' => array(
        'name' => 'BlinkList',
        'url' => 'http://blinklist.com/index.php?Action=Blink/addblink.php&Url={url}&Title={title}'
    ),
    'newsvine' => array(
        'name' => 'Newsvine',
        'url' => 'http://www.newsvine.com/_wine/save?u={url}&h={title}'
    ),
    'magnolia' => array(
        'name' => 'ma.gnolia',
        'url' => 'http://ma.gnolia.com/bookmarklet/add?url={url}&title={title}'
    ),
    'reddit' => array(
        'name' => 'reddit',
        'url' => 'http://reddit.com/submit?url={url}&title={title}'
    ),
    'windows_live' => array(
        'name' => 'Windows Live',
        'url' => 'https://favorites.live.com/quickadd.aspx?marklet=1&mkt=en-us&url={url}&title={title}&top=1'
    ),
    'tailrank' => array(
        'name' => 'Tailrank',
        'url' => 'http://tailrank.com/share/?link_href={url}&title={title}'
    ),
);

function share_form($p) {
?>
<div id="share_form">
<a href="#" onclick="$('#share_form').hide(); return false" class="share_close"><?=_('Close') ?></a>
<ul class="share_tabs">
<li id="share_tab1" class="selected" onclick="share_tab('1');"><?=_('Social Web') ?></li>
<li id="share_tab2" onclick="share_tab('2');"><?=_('Email') ?></li>
</ul>
<div class="clear"></div>
<?
    share_form_social($p);
    share_form_email($p->ref());
}

function share_form_social($p) {
    global $share_sites;
    echo '<div id="share_social"><ul>';
    foreach ($share_sites as $key => $data) {
        $url = str_replace(array('{url}', '{title}'),
            array(urlencode($p->url_typein()), urlencode('PledgeBank pledge ' . $p->ref())),
            $data['url']
        );
        print '<li><a href="' . htmlspecialchars($url) . '" id="share_' . $key . '">'
            . str_replace(' ', '&nbsp;', $data['name']) . '</a></li>' . "\n";
    }
    echo '</ul> <div class="clear"></div> </div>';
}

function share_form_email($ref) {
    $name = ''; $email = '';
    $P = pb_person_if_signed_on();
    if (!is_null($P)) {
        $name = $P->name_or_blank();
        $email = $P->email();
    }
?>
<div id="share_email">
<form action="/<?=$ref ?>/email" method="post">
<fieldset>
<legend><?=_('Email') ?></legend>
<ul>
<li>
<label><?=_('To: (up to 5 emails)') ?></label>
<input type="text" name="e1" value="" class="share_text">
</li>
<li>
<label><?=_('Your name:') ?></label>
<input type="text" name="fromname" value="<?php print(htmlspecialchars($name)); ?>" class="share_text">
</li>
<li>
<label><?=_('Your email:') ?></label>
<input type="text" name="fromat" value="<?php print(htmlspecialchars($email)); ?>" class="share_text">
</li>
<li><?=_('Add a message, if you want:') ?>

<textarea name="frommessage" rows="8" cols="40"></textarea>
<li>
<input type="submit" name="submit" value="<?=_('Send message') ?>">
</li>
</ul>
</fieldset>
</form>
</div>
</div>
<?php
}

