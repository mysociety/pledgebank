<?
// footer.ph
// Footer for main PledgeBank.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

    debug_timestamp(true, "begin footer");
?>
</div>

<div id="pballfooter">
<hr class="v"><h2 class="v"><?=_('Navigation') ?></h2>
<div id="navforms">
<a href="http://www.mysociety.org/"><img id="ms_logo" align="top" alt="Visit mySociety.org" src="/i/mysociety-dark+50.png"><span id="ms_logo_ie"></span></a>
<form action="/lang" method="get" name="language">
<input type="hidden" name="r" value="<?=htmlspecialchars($_SERVER['REQUEST_URI'])?>">
<select name="lang" id="language">
<?
    foreach ($langs as $l => $pretty) {
        $o = '<option value="' . $l . '"';
        if ($l == $lang) $o .= ' selected';
        $o .= ">$pretty</option>";
        print $o;
    }
?>
<option value="translate"><?=_('Translate into your language...')?></option>
</select> <input type="submit" value="<?=_('Change')?>"></form>
</div>
<?
    $menu = microsites_navigation_menu($contact_ref);
    # remove all extraneous whitespace to avoid IE bug
    print '<ul id="nav">';
    foreach ($menu as $text => $link) {
        print "<li><a href='$link'>$text</a></li>";
    }
    print '</ul>';
    if (!array_key_exists('nonav', $params) or !$params['nonav']) {
?>
<div class="noprint">
<?      if (microsites_local_alerts() && (!array_key_exists('nolocalsignup', $params) || !$params['nolocalsignup']))
            pb_view_local_alert_quick_signup("localsignupeverypage");
        debug_timestamp(true, "local alert quick timestamp");
?>
<hr class="v">
<div id="pbfooter">
<a href="/translate/"><?=_('Translate PledgeBank into your language') ?></a>.
<br><a href="http://www.mysociety.org/"><?=_('Built by mySociety') ?></a>.
<a href="/privacy"><?=_('Privacy and cookies') ?></a>.</div>
</div>
<?
        debug_timestamp(true, "change language links");
    }
?>

</div>
<script type="text/javascript">
greyOutInputs();
</script>
</body></html>

