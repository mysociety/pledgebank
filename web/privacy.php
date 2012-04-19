<?
// privacy.php:
// Privacy page for PledgeBank.
//
// Copyright (c) 2012 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

require_once "../phplib/pb.php";
require_once '../phplib/fns.php';

page_header(_("Frequently Asked Questions"), array('cache-max-age' => 600));

?>

<h2>Privacy and cookies</h2>

<p><strong>Our use of your data, cookies, and external services: what you
should know, and how to opt out if you want to.</strong></p>

<p>Summary: We care a lot about our users’ privacy. We provide details below,
and we try our hardest to look after the private data that we hold. Like many
other websites, we sometimes use cookies and Google Analytics to help us make
our websites better. These tools are very common and used by many other sites,
but they do have privacy implications, and as a charity concerned with socially
positive uses of the internet, we think it’s important to explain them in full.
If you don’t want to share your browsing activities on mySociety’s sites with
other companies, you can adjust your usage or install opt-out browser plugins.

<?

    print h3('<a name="privacy">'._('Privacy Questions').'</a>');
    print "<dl>\n";

    print dt(_("I don't want my name visible to everyone when I sign a pledge!"));

    print dd(sprintf(_("You can add yourself secretly to a pledge by unchecking the 'Show
    my name on this pledge' box when you sign up.  Alternatively, you can sign
    up by SMS (in %s only).  If you are about to make a sensitive pledge, you may want to 
    make it private, which means only people with a PIN you give them can
    view the pledge page."), sms_countries_description()));

    print dt(_('Who gets to see my email address?'));

    print dd(_("We will never disclose your email address to anyone, including the
    creator of your pledge, unless we are obliged to by law. We do let the pledge
    creators send emails to pledge subscribers to explain what's going on, to
    motivate them etc. However, we don't show the pledge creator the addresses of
    the subscribers getting their email. If you reply to an email from the pledge
    creator yourself, you will give them your email address&mdash;please be
    aware!"));

    print dt(_('Will you send nasty, brutish spam to my email address?'));

    print dd(_("Nope. After you sign up to a pledge we will send you emails in
    relation to your pledge. These will be a mixture of status emails from
    PledgeBank itself, and missives from the pledge creator, trying to encourage you
    into greater support. We will never give or sell your email addresses to anyone
    else, unless we are obliged to by law."));

    print "</dl>\n";

?>

<h3>Cookies</h3>

<p>To make our service easier or more useful, we sometimes place small data
files on your computer or mobile phone, known as cookies; many websites do
this. We use this information to, for example, remember you have logged in so
you don't need to do that on every page, or to measure how people use the
website so we can improve it and make sure it works properly. Below, we list
the cookies and services that this site can use.

<table cellpadding="5">
<tr align="left"><th scope="col">Name</th><th scope="col">Typical Content</th><th scope="col">Expires</th></tr>
<tr><td>pb_person_id</td><td>A digitally signed token of the logged-in user's numeric ID, and when they logged in</td><td>When browser is closed, or four weeks if &ldquo;Remember me&rdquo; is ticked when logging in</td></tr>
<tr><td>pb_test_cookie</td><td>"1", to test if cookies are supported</td><td>When browser is closed</td></tr>
</table>

<h4>Measuring website usage (Google Analytics)</h4>

<p>We use Google Analytics to collect information about how people use this site.
We do this to make sure it’s meeting its users’ needs and to understand how we
could do it better. Google Analytics stores information such as what pages you
visit, how long you are on the site, how you got here, what you click on, and
information about your web browser. IP addresses are masked (only a portion is
stored) and personal information is only reported in aggregate. We do not allow
Google to use or share our analytics data for any purpose besides providing us
with analytics information, and we recommend that any user of Google Analytics
does the same.

<p>If you’re unhappy with data about your visit to be used in this way, you can
install the <a href="http://tools.google.com/dlpage/gaoptout">official browser
plugin for blocking Google Analytics</a>.

<p>The cookies set by Google Analytics are as follows:

<table cellpadding="5">
<tr align="left"><th scope="col">Name</th><th scope="col">Typical Content</th><th scope="col">Expires</th></tr>
<tr><td>__utma</td><td>Unique anonymous visitor ID</td><td>2 years</td></tr>
<tr><td>__utmb</td><td>Unique anonymous session ID</td><td>30 minutes</td></tr>
<tr><td>__utmz</td><td>Information on how the site was reached (e.g. direct or via a link/search/advertisement)</td><td>6 months</td></tr>
<tr><td>__utmx</td><td>Which variation of a page you are seeing if we are testing different versions to see which is best</td><td>2 years</td></tr>
</table>

<h5>Google’s Official Statement about Analytics Data</h5>

<p>“This website uses Google Analytics, a web analytics service provided by
Google, Inc. (“Google”). Google Analytics uses “cookies”, which are text files
placed on your computer, to help the website analyze how users use the site.
The information generated by the cookie about your use of the website
(including your IP address) will be transmitted to and stored by Google on
servers in the United States . Google will use this information for the purpose
of evaluating your use of the website, compiling reports on website activity
for website operators and providing other services relating to website activity
and internet usage.  Google may also transfer this information to third parties
where required to do so by law, or where such third parties process the
information on Google’s behalf. Google will not associate your IP address with
any other data held by Google.  You may refuse the use of cookies by selecting
the appropriate settings on your browser, however please note that if you do
this you may not be able to use the full functionality of this website.  By
using this website, you consent to the processing of data about you by Google
in the manner and for the purposes set out above.”</p>

<p><a href="http://www.mysociety.org/privacy-online/">More general information on how third party services work</a></p>

<h3>Credits</h3>

<p>Bits of wording taken from the <a href="http://gov.uk/help/cookies">gov.uk
cookies page</a> (under the Open Government Licence).

<?

page_footer();

