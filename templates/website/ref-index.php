<?
// ref-index.php:
// Main pledge page, for URLs http://www.pledgebank.com/REF/
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: francis@mysociety.org. WWW: http://www.mysociety.org
//

pledge_draw_status_plaque($p);
debug_comment_timestamp("after draw_status_plaque()");
$p->render_box(array('showdetails' => true, 'reportlink' => true, 'showcontact' => true, 'id' => 'pledge_main'));
debug_comment_timestamp("after \$p->render_box()");

print '<div id="col2">';
if (!$p->finished())
    $p->sign_box();
else
    draw_connections_for_finished($p);

echo '<div id="spreadword">';
if (!$p->finished()) {
    print h2(_('Spread the word on and offline'));
} else {
    print h2(_('Things to do with this pledge'));
}
// Now we have "share this" button, only show digg on pledges that asked for it
if (!$p->finished() && $p->ref() == 'us-patriot-drive') {
    print '<div id="digg">';
    print '<script src="http://digg.com/tools/diggthis.js" type="text/javascript"></script>';
    print '</div>';
}
print '<ul>';
if (!$p->pin()) {
?>
<li>
<div id="fb-root"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = "//connect.facebook.net/en_GB/all.js#xfbml=1";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>
<a href="http://twitter.com/share" class="twitter-share-button" data-url="<?=$p->url_typein()?>" data-count="none">Tweet</a><script type="text/javascript" src="http://platform.twitter.com/widgets.js"></script>
<div class="fb-like" data-href="<?=$p->url_typein()?>" data-send="false" data-layout="button_count" data-width="90" data-show-faces="false" style="vertical-align:top"></div>
<?
} else {
    print '<li>';
    print_link_with_pin($p->url_email(), "", _("Email your friends"));
    print '</li>';
}
if (!$p->finished()) {
    if (microsites_has_flyers()) {
        print '<li>';
        print_link_with_pin($p->url_flyers(), _("Stick them places!"), _("Print out customised flyers"));
        print '</li>';
    }
    print '<li>';
    print_link_with_pin('/' . $p->ref() . '/promote', '', _('Promote on your site or blog'));
    print '</li>';
} 
?>
    <li><a rel="nofollow" href="/new/local/<?=$p->ref() ?>"><?=_('Create a local version of this pledge') ?></a></li>
    <li><small><?=_('Creator only:') ?> <a rel="nofollow" href="<?=$p->url_announce()?>" title="<?=_('Only if you made this pledge') ?>"><?=_('Send message to signers') ?></a>
<?
if (!$p->finished()) {
    print ' | <a rel="nofollow" href="' . $p->url_picture() . '" title="' . _('Only if you made this pledge') . '">';
    print $p->has_picture() ? _('Change picture') : _('Add a picture');
    print '</a>';
}
?>
</small></li>
</ul>
</div>
<?
if (microsites_comments_allowed())
    draw_comments($p);
?>
</div>

<div id="col1">
<div id="signatories">
<?
$title = '<a name="signers">' . _('Current signatories') . '</a>';
if (microsites_has_survey()) {
    $title .= ' <span style="font-size:50%; font-weight:normal">(<span style="color:#006600"><img alt="Green text " src="http://upload.wikimedia.org/wikipedia/commons/thumb/f/fb/Yes_check.svg/16px-Yes_check.svg.png">= ' ._("they've done it").'</span>)</span>';
}
print h2($title);

$nsigners = db_getOne('select count(id) from signers where pledge_id = ?', $p->id());
if ($nsigners == 0) {
    print p(sprintf(_('So far, only %s, the Pledge Creator, has signed this pledge.'),
        htmlspecialchars($p->creator_name())));
} else {
    draw_signatories_list($p, $nsigners);
    print '<p>';
    print_link_with_pin($p->url_info(), "", _("View signup rate graph"));
}
print '</div>';

draw_connections($p);
print '</div>';

