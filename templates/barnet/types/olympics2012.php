<!-- =========================== olympics 2012 =================================== -->

<h2>2012 Olympics: Street Party in your street</h2> 
<div class='ms-olympics2012-banner' style="height:200px;"></div>
<div id="ms-explicit-party-list">
  <h3>Our 2012 Olympic street party pledges:</h3>
  <?  print pledge_get_ul_list_by_type('olympics2012', 3, 3, "pledge_type_auto_width_column"); ?>
  <div style="width:100%;clear:both;height:1px"></div>
</div>
<div style="font-size:1.126em;padding-bottom:0.25em;position:relative;">
  <p>
    The 2012 Games represent a unique opportunity for London and the UK. It is a wonderful excuse for a street party
    &mdash; not only is a street party a fun way for residents to celebrate such an event, it&rsquo;s also a great
    way to get to know your neighbours better.
  </p>
  <ul>
    <li>
      Your party can happen on any day between the games' start and end, that is, 
      <strong>between 27&nbsp;July and 12&nbsp;August&nbsp;2012</strong>.
    </li>
    <li>
      We have special LOCOG Olympic bunting packs available for the first 10 fulfilled street parties!
    </li>
  </ul>
  <div class="pb-barnet-breakout" style="position:relative;padding-top: 1em;">
    <p class="corner-label">
      Please note:
    </p>
    <h4>
      Sharing your contact details with other volunteers
    </h4>
    <p>
      If you agree to become a volunteer, we will automatically share your contact details with other participants in your street.
    </p>
  </div>
  <p>
    The council will provide advice and insurance cover.
  </p>
</div>
<div style="width:100%;clear:both;height:1px"></div>
<div style="float:right; width:45%;margin-left:1em;position:relative;" class="pb-barnet-breakout">
  <h3>
    What you need to do: Sign up to a pledge!
  </h3>
  <p>
    Check to see if your street is shown at the top of this page &mdash; click on it and sign up!
  </p>
  <h3>
    Can&rsquo;t see your street? Start a new pledge! 
  </h3>
  <p>
    If there&rsquo;s not already a pledge for your street, simply start one by sending us your details
    (don&rsquo;t forget to tell us the street name too!).
    You will need to get a minimum of 
    <?= microsites_get_pledge_type_details('olympics2012', 'preloaded_data', 'target') ?>
    households involved for the pledge to be successful.
  </p>
</div>
<? print_contact_form($name, $email, $topic, true) ?>
<p>The PledgeBank team will . . .</p>
<ul>
  <li>
    start a pledge page to help encourage people in your street to get involved and invite you to be the first to sign it
  </li>
  <li>
    or, if we&rsquo;ve created one already, we'll let you know so you can sign up to it
  </li>
</ul>
<p>
  After that, it&rsquo;s up to you to spread the word to your neighbours to get them to sign your pledge and get involved!
</p>

