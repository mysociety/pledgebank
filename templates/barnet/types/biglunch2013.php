<!-- =========================== the big lunch 2013 ============================== -->
<h1>The Big Lunch: a one day get-together for neighbours</h1>
<div class='ms-biglunch2013-banner' style="height:200px;"></div>
<div id="ms-explicit-party-list">
  <h3>Sign up to a Big Lunch street party pledge:</h3>          
  <?  print pledge_get_ul_list_by_type('biglunch2013', 3, 3, "pledge_type_auto_width_column"); ?>
  <div style="width:100%;clear:both;height:1px"></div>
</div>
<div style="ms-general-box">
   <a href="http://www.thebiglunch.com/" title="about The Big Lunch"><img 
      alt="The Big Lunch logo" src="/microsites/barnet/the_big_lunch_logo_212_x_90.png" 
      style="float:right;width:212px;height:90px;border:none;margin: 0 1em 1em 1em;"/></a>
  <p>
      <a href="http://www.thebiglunch.com/">The Big Lunch</a> is a very simple idea from the Eden Project. 
      The aim is to get as many people as possible across the whole of the UK to have lunch with their
      neighbours in a simple act of community, friendship and fun. This year it&rsquo;s happening on 
      <strong>Sunday&nbsp;2&nbsp;June</strong>.
  </p>
  <p>
      In fact, you don&rsquo;t have to pick <em>exactly</em> that date &mdash; maybe 
      <strong>Friday&nbsp;31&nbsp;May</strong>,
      <strong>Saturday&nbsp;1&nbsp;June</strong>, or even any other weekend near that time!
  </p>
  <p>
      A Big Lunch can be anything from a few neighbours getting together in the garden or on the street, 
      to a full blown party with food, music and decoration that quite literally stops the traffic.              
      If it is on the street, you will need to get Barnet Council&rsquo;s permission to make sure it is OK 
      for you to close the road and sort out your insurance.
      So sign up now: the council pledges to support <strong>the first 30 streets to reach their target of
      <?= microsites_get_pledge_type_details('biglunch2013', 'preloaded_data', 'target') ?> households</strong>
      signed up for a party in the street.
  </p>
</div>
<div style="width:100%;clear:both;height:1px"></div>
<div style="float:right; width:45%;margin-left:1em;position:relative;" class="pb-barnet-breakout">
  <h3>
     What you need to do: Sign up to a pledge.
  </h3>
  <p>
    Check to see if your street is shown at the top of this page &mdash; click on it and sign up!
  <h3>
    Can’t see your street? Start a new pledge.
  </h3>
  <p>
    If there’s not already a pledge for your street, simply start one. 
    You will need to get a minimum of
    <?= microsites_get_pledge_type_details('biglunch2013', 'preloaded_data', 'target') ?>
    households involved for the pledge to be successful.
  </p>
</form><!-- close existing global form :-( -->
<form class="basic_form xform" method="get"  action="https://www.barnet.gov.uk/forms/form/380/en/XXXXXX" >
  <p class="center">
    <input type="submit" class="button next" title="Begin application" name="next" value="Begin application »">
  </p>
</form>
<form action="#"> <!-- open global form, no action -->
</div>

<p>The PledgeBank team will . . .</p>
<ul>
   <li>
       start a pledge page to help encourage people in your street to get involved and invite you to be the first to sign it
   </li>
   <li>
     or, if we&rsquo;ve created one already, we&rsquo;ll let you know so you can sign up to it
   </li>
</ul>
<p>
   After that, it&rsquo;s up to you to spread the word to your neighbours to get them to sign your pledge and get involved!
</p>
