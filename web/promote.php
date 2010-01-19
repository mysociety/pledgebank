<?

include_once '../phplib/pb.php';

page_header(_('Promoting PledgeBank'));

print h2(_('Promoting PledgeBank'));
print p(_('Here are a few ways you can publicise PledgeBank on your own website:'));

?>

<div style="float: right; padding: 0 1em; border: solid 1px #999999; text-align:center">
<?

print h3(_('A4 poster'));
print p(_('For a library or similar'));

?>

<p><a href="/promo/pbposter.pdf"><img src="/promo/pbposter.png" alt="poster thumbnail"></a></p>
</div>

<?

print h3(_('Buttons for your blog'));
display('<a href="http://www.pledgebank.com/"><img src="http://www.pledgebank.com/promo/pb1.png"/></a>');
display('<a href="http://www.pledgebank.com/"><img src="http://www.pledgebank.com/promo/pb2.png"/></a>');
print h3(_('Bigger banner'));
display('<a href="http://www.pledgebank.com/"><img src="http://www.pledgebank.com/promo/pb_large.png"/></a>');
print h3(_('Pledgebank plugin for Wordpress'));

?>

<p><img src="/promo/pbwordpress.png" alt="pledgebank plugin for wordpress screenshot"></p>
<small><a href="/promo/wordpresspledgebank.com.zip"><?=_('Get the code') ?></a></small> 

<?

function display($t) {
    print "<p>$t</p>" . '<p><textarea rows="4" cols="70" onclick="this.select();">' . $t . '</textarea></p>';
}

page_footer();
