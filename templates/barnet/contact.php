<?
// contact.php:
// Barnet contact us template for PledgeBank.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

$topic = get_http_var('topic');

     
    if ($comment_id) {
        print '<div id="tips">';
        print p(_('You are reporting the following comment to us:'));
        print '<blockquote>';
        $row = db_getRow('select *,extract(epoch from ms_current_timestamp()-whenposted) as whenposted from comment where id = ? and not ishidden', $comment_id);
        if ($row)
            print comments_show_one($row, true);
        else
            print '<em>Comment no longer exists</em>';
        print '</blockquote>';
        print "</div>";
    }

    if (sizeof($errors)) {
        print '<div id="errors"><ul><li>';
        print join ('</li><li>', $errors);
        print '</li></ul></div>';
    } ?>
<form name="contact" accept-charset="utf-8" action="/contact" method="post"><input type="hidden" name="contactpost" value="1"><input type="hidden" name="ref" value="<?=htmlspecialchars($ref)?>"><input type="hidden" name="referrer" value="<?=htmlspecialchars($referrer)?>"><input type="hidden" name="pledge_id" value="<?=htmlspecialchars($pledge_id)?>"><input type="hidden" name="comment_id" value="<?=htmlspecialchars($comment_id)?>">
<?  if ($comment_id) {
        print h2(_('Report abusive, suspicious or wrong comment'));
        print p(_("Please let us know exactly what is wrong with the comment, and why you think it should be removed."));
    } elseif ($topic == 'royalwedding') { ?>
        
        <h2>Royal Wedding: Street Party in your street</h2>
        <div class='ms-royalwedding-banner' style="height:200px;"></div>
        <div style="font-size:1.126em;padding-bottom:0.25em;">
            <p>
                 The Royal Wedding of HRH Prince William and Kate Middleton will take place on 29 April this year. 
                 It&rsquo;s a wonderful excuse for a street party &mdash; not only is a street party a fun way for residents 
                 to celebrate the event, it's also a great way to get to know your neighbours better.               
            </p>
        </div>
        <div style="float:right; width:45%;margin-left:1em;" class="pb-barnet-breakout">
          <h3>
              What you will need to do:
          </h3>
          <ul>
            <li>
                Read our guide to <a href="http://www.barnet.gov.uk/royal-wedding.htm">holding a Royal Wedding street party</a>.
            </li>
            <li>
              Fill in a <a href="http://www.barnet.gov.uk/royal-wedding-form.pdf">Royal Wedding street party application form</a> telling us about the road or section of road you would like to close on the day.
            </li>
            <li>
              Read the <a href="http://www.barnet.gov.uk/royal-wedding-insurance.pdf">policy wording of the public liability insurance cover</a> arranged by the Council.
            </li>
          </ul>
          <h3>
            What we're pledging: 
          </h3>
          <ul>
            <li>
              Barnet Council will support street parties on the day of the Royal Wedding, including arranging insurance free of charge, if at least three households in a street sign up to holding an event.
            </li>
          </ul>
        </div>
        <h3>
            Get involved!
        </h3>            
        <p>
            Start or join a pledge to organise a street party in your road. Ideally, you'll need to get a minimum of 3&nbsp;households 
            involved. If you don't meet your target you don't have to act, but if you do then .&nbsp;.&nbsp;. party&nbsp;on!
        </p>
        
    <?
    } else {
        print h2("Suggest a pledge");
        $contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);
        
        print "<p>Do you have an idea for a pledge that could appear on this site?</p>";
        print "<p>What would you like to get done? </p>";

        printf(_('If you prefer, you can email %s instead of using the form.'), '<a href="mailto:' . $contact_email . '">' . $contact_email . '</a>');
        print "</p>";
    }
?>

<p><label for="name"><?=_('Your name:') ?></label> <input type="text" id="name" name="name" value="<?=htmlspecialchars($name) ?>" size="30">

<p><label for="e"><?=_('Your email:') ?></label> <input type="text" id="e" name="e" value="<?=htmlspecialchars($email) ?>" size="30"></p>

<input type="hidden" id="subject" name="subject" value="">

<?
    if ($topic == 'royalwedding'){
?>
        <input name="topic" type="hidden" value="<?= $topic ?>" />
        <p>
            <label for="street">Your street:</label> <input id="message" name="message" type="text" value="<?=htmlspecialchars(get_http_var('message', true)) ?>" size="30"/>
        </p>
<?      
    } else {
?>
    <p><label for="message">Your suggestion:</label>
        <br><textarea rows="7" cols="40" name="message" id="message"><?=htmlspecialchars(get_http_var('message', true)) ?></textarea></p>
<? } ?>

<p>
<input type="submit" name="submit" value="Send to PledgeBank team"></p>
<? if ($topic=='royalwedding') { ?>

  <p>The PledgeBank team will...</p>
  <ul>
    <li>
        email you the <a href="http://www.barnet.gov.uk/royal-wedding-form.pdf">Royal Wedding street party application form</a> you will need to close off your street
    </li>
    <li>
        start a pledge page to help encourage people in your street to get involved and invite you to be the first to sign it
    </li>
    <li>
      or, if we've created one already, we'll let you know so you can sign up to it
    </li>
  </ul>
  <p>
    After that, it's up to you to spread the word to your neighbours to get them to sign your pledge and get involved!
  </p>

<? } elseif (! $comment_id ) { ?>
  <p>The PledgeBank team will...</p>
  <ul>
    <li>review suggestions and add them to the website</li>
    <li>Share your pledge through tweets, social networks and flyers</li>
    <li>Recruit people to help and get things done</li>
  </ul>
<? } ?>
</form>


