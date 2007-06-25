<?

include_once '../phplib/pb.php';

page_header('Promote PledgeBank');
?>
<h2>Promoting PledgeBank</h2>

<p>Here are a few ways you can publicise PledgeBank on your own website:</p>

<div style="float: right; padding: 0 1em; border: solid 1px #999999; text-align:center">
<h3>A4 poster</h3>
<p>For a library or similar</p>
<p><a href="http://memedev.com/mysocietydownloads/promo/pbposter.pdf"><img src="http://memedev.com/mysocietydownloads/promo/pbposter.png" alt="poster thumbnail"></a></p>
</div>

<h3>Buttons for your blog</h3>
<?

display('<a href="http://www.pledgebank.com/"><img src="http://memedev.com/mysocietydownloads/promo/pb1.png"/></a>');
display('<a href="http://www.pledgebank.com/"><img src="http://memedev.com/mysocietydownloads/promo/pb2.png"/></a>');

print '<h3>Bigger banner</h3>';
display('<a href="http://www.pledgebank.com/"><img src="http://memedev.com/mysocietydownloads/promo/pb_large.png"/></a>');
?>

<h3>Pledgebank plugin for Wordpress</h3>
<p><img src="http://memedev.com/mysocietydownloads/promo/pbwordpress.png" alt="pledgebank plugin for wordpress screenshot"></p>
<small><a href="http://memedev.com/mysocietydownloads/promo/wordpresspledgebank.com.zip">Get the code</a></small> 

<?

function display($t) {
    print "<p>$t</p>" . '<p><textarea rows="4" cols="70" onclick="this.select();">' . $t . '</textarea></p>';
}

page_footer();
