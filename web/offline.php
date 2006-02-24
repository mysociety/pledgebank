<?
// offline.php:
// Description of PledgeBank's offline functions.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: offline.php,v 1.15 2006-02-24 19:21:55 matthew Exp $

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

page_header(_("You never bump into your neighbours online"));

?>
<div class="offline">
<img src="monitor-neighbours-250px.jpg" class="l" style="margin-right: 20px;" title="<?=_('You never bump into your neighbours online') ?>" width="245" height="250" alt="">
<h2><?=_('You never bump into your neighbours online') ?></h2>
<?
print p(_("Have you ever noticed that you never bump into your neighbours online?
This means that most websites can't offer much help to people trying
to do things on their street or in their local community."));
print p(_("PledgeBank is different. We've designed PledgeBank to make it easy to
get word of your pledges to your neighbours, and easy for them to get
involved even if they never use the internet."));
?>
</div>

<div class="offline">
<img src="flyer-example.png" class="r" style="margin-left: 20px;" title="<?=_('Example PledgeBank flyer') ?>" width="298" height="211" alt="">
<h2><?=_('Flyers') ?></h2>
<?
print p(_('Every pledge created on PledgeBank comes with a set of automatically
generated flyers.'));
print p(_("These flyers tell people what your pledge is and how to sign up.
They're ideal for posting through doors, pinning on notice-boards,
handing out at school gates - whatever you want. If you've created a
pledge yourself, you can ask your signers to print and distribute
leaflets themselves, spreading the word further."));
print p(_('Each flyer tells people how to sign up either via the web, or via a
simple two word text message.'));
?>
</div>

<div class="offline">
<img src="phone-200px.jpg" class="l" style="margin-right: 20px;" width="168" height="200" alt="">
<h2><?=_('Mobile Phones') ?></h2>
<?
print p(_("The ability to sign up via text message is powerful because it means
that people who never use the Internet can sign up, and you can get
someone to sign up even if they're nowhere near a computer."));
# TRANS: Does "pledge" in the SMS format have to stay in English? I suspect the answer is yes, but please confirm. (Tim Morley, 2005-11-23)
# Yes, we only have use of the word "pledge" currently. (Matthew Somerville, http://www.mysociety.org/pipermail/mysociety-i18n/2005-November/000104.html)
printf(p(_("Anyone with a mobile can sign up to a pledge by texting the word
<strong>pledge</strong> followed by the pledge reference to
<strong>%s</strong>. For the moment, this works only in %s.
Contact us if you can help with SMS for other countries.")), 
OPTION_PB_SMS_DISPLAY_NUMBER, sms_countries_description());
print p(_("Text messages cost whatever you normally pay for a normal SMS, and as
a pledge creator you can write to your mobile signers when your pledge
succeeds, letting them know what to do next."));
print p(_("There are all types of other things you can do with text messages. You
can include the details in your own campaign materials, include them
in letters to newspapers, pin them in your window, tattoo them on your
arms."));

print '</div>';

print p(_("So if you want to create a pledge, or if you have seen one on this
site that you really endorse, fire up the printer, memorize the text
message details, and take your message out into the big wide world."));

print '<p><a href="/">' . _('To PledgeBank front page') . '</a></p>';

page_footer();

?>
