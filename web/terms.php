<?
// terms.php:
// T&Cs page for LiveSimply
//
// Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: terms.php,v 1.1 2007-01-03 20:13:01 matthew Exp $

require_once "../phplib/pb.php";

page_header(_("Terms and Conditions"), array('cache-max-age' => 600));

if ($microsite && $microsite == 'livesimply') {
    livesimply_terms();
} else {
    print '<p>This page is only for <em>live</em>simply at present.</p>';
}

page_footer();

# ---

function livesimply_terms() { ?>
<h2>Terms and Conditions</h2>

<p>By starting a <em>live</em>simply promise you are agreeing to respecting some common online community rules:</p>

<p>When setting up promises:</p>

<ol>
<li>Make your promise unique. We'd suggest that you check first if any
of the promises on this website appeal to you. By signing up to a
promise you are building the <em>live</em>simply:promise community, making that
promise more successful.  If none of the existing promises resonate with
you, then start your own promise.</li>
<li>Do not post promises that are unlawful, harassing, defamatory,
abusive, threatening, harmful, obscene, profane, sexually oriented,
racially, religiously, ethnically or in any other way offensive.</li>
<li>Don't use inappropriate usernames. Those which can be seen as vulgar or offensive.</li>
<li>Don't pretend you are someone else.</li>
<li>Do not incite people to commit any crime.</li>
<li>If you are under 18 you need to ask your parent/guardian or teacher for permission to start a promise</li>
</ol>

<p>When commenting to other promises or other people's comments to your promise:</p>

<ol>
<li>Don't pretend you are someone else.</li>
<li>Don't swear.</li>
<li>No defamatory comments.</li>
<li>Avoid contempt of court.</li>
<li>Don't abuse the complaints system.</li>
<li>Do not incite people to commit any crime.</li>
<li>Do not post comments that are unlawful, harassing, defamatory, abusive, threatening, harmful, obscene, profane, sexually oriented or racially offensive.</li>
<li>Don't publish personal information.</li>
<li>If you are under 18 you need to ask your parent/guardian or teacher for permission to post comments.</li>
</ol>

<p>Violation of any of the rules above may lead to your membership account/promise being temporarily suspended or terminated.</p>

<h3>Privacy questions</h3>
<p>(from the <a href="/faq">FAQ</a>)</p>

<dl>
<dt>Does my name have to be visible to everyone when I sign a promise?</dt>
<dd>You can add yourself secretly to a promise by unchecking the 'Show my name on this promise' box when you sign up. Alternatively, you can sign up by SMS (in the UK only). If you are about to make a sensitive promise, you may want to make it private, which means only people with a PIN you give them can view the promise page. </dd>

<dt>Who gets to see my email address?</dt>
<dd>
<p><em>live</em>simply network will never disclose your email address to anyone,
including the creator of your promise, unless we are obliged to by law. We do
let the promise creators send emails to promise subscribers to explain what's
going on, to motivate them etc. However, we don't show the promise creator the
addresses of the subscribers getting their email. If you reply to an email from
the promise creator yourself, you will give them your email address &mdash;
please be aware!</p>

<p><em>live</em>simply network is also reserving the right to use your email
addresses to get in touch with you very occasionally during the life of the
<em>live</em>simply challenge (due to end May 2008).</p>

</dd>

<dt>Will you send spam to my email address? </dt>
<dd>Nope. After you sign up to a promise we will only send you emails in relation
to your promise or to the <em>live</em>simply challenge. You may receive a message
from the promise creator, asking how you're getting on, and status emails from
<em>live</em>simply promise stating whether the target has been reached. The
members of the <a href="http://www.progressio.org.uk/livesimply/AssociatesInternal/92992/members/"><em>live</em>simply network</a>
will never give or sell your email addresses to anyone else, unless we are obliged to by law. </dd>

</dl>

<h2>For under 18s</h2>

<p>To start or sign up to a promise or to comment to someone else's promise you need to ask your parent/guardian or teacher for permission. </p>

<p>When you start or sign up for a <em>live</em>simply promise, we ask you for an email address. We need this so you can be kept in touch with how the promise is going but we won't be showing your address to anyone else (unless for legal reasons we have to). Your email address is safe and sound with us - that's a promise!</p>

<p>Mind you, if you send an email to someone else who signed up to a promise they will see your email address &mdash; so be careful and make sure your parent/guardian/teacher is aware of it. </p>

<p>Your email address is something personal to you that you should be careful about sharing. When you are asked for your email address by a web site, you need to be clear about what that site is going to do with your address. For all the info you need on staying safe online, go to <a href="http://www.childnet-int.org/projects">http://www.childnet-int.org/projects</a>.</p>
 
<p>If you have any more questions about our terms and conditions, please email <a href="mailto:promise&#64;livesimply.org.uk">promise&#64;livesimply.org.uk</a>.</p>

<?
}

############################################################################

