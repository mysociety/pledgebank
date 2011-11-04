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
        <div id="ms-explicit-party-list">
          <h3>Our street party pledges:</h3>          
          <ul>
              <li><a href="/Alders-Close">Alders&nbsp;Close,&nbsp;HA8</a></li>
              <li><a href="/asmuns-hill">Asmuns&nbsp;Hill,&nbsp;NW11</a></li>
              <li><a href="/Athenaeum-Road">Athenaeum&nbsp;Road,&nbsp;N20</a></li>
              <li><a href="/bevan-road">Bevan&nbsp;Road,&nbsp;EN4</a></li>
              <li><a href="/Bosworth-Road">Bosworth&nbsp;Road,&nbsp;EN5</a></li>
              <li><a href="/brownlow-road">Brownlow&nbsp;Road,&nbsp;N3</a></li>
              <li><a href="/Brookland-Close">Brookland&nbsp;Close,&nbsp;NW11</a></li>
              <li><a href="/Bryant-Close">Bryant&nbsp;Close,&nbsp;EN5</a></li>
              <li><a href="/cissbury-road">Cissbury&nbsp;Road</a></li>
              <li><a href="/corringham-road">Corringham&nbsp;Road,&nbsp;NW11</a></li>
              <li><a href="/CrewysRoad">Crewys&nbsp;Road,&nbsp;NW2</a></li>
              <li><a href="/dalegreenparty">Dale&nbsp;Green&nbsp;Road,&nbsp;N11</a></li>
              <li><a href="/dukesavenue">Dukes&nbsp;Avenue</a></li>
              <li><a href="/Eastholm">Eastholm,&nbsp;NW11</a></li>
              <li><a href="/elm-gardens">Elm&nbsp;Gardens,&nbsp;N2</a></li>
              <li><a href="/exeter-road">Exeter&nbsp;Road,&nbsp;N14</a></li>
          </ul>
          <ul>
              <li><a href="/fairview-way">Fairview&nbsp;Way,&nbsp;HA8</a></li>
              <li><a href="/falklandroad">Falkland&nbsp;Road,&nbsp;EN5</a></li>
              <li><a href="/fallowsclose">Fallows&nbsp;Close,&nbsp;N2</a></li>
              <li><a href="/grove-road">Grove&nbsp;Road,&nbsp;N12</a></li>
              <li><a href="/harmandriveparty">Harman&nbsp;Drive,&nbsp;NW2</a></li>
              <li><a href="/harrowes-meade">Harrowes&nbsp;Meade,&nbsp;HA8</a></li>
              <li><a href="/hartlandroad">Hartland&nbsp;Road,&nbsp;N11</a></li>
              <li><a href="/hatley-close">Hatley&nbsp;Close,&nbsp;N11</a></li>
              <li><a href="/hertfordclose">Hertford&nbsp;Close,&nbsp;EN4</a></li>
              <li><a href="/hertford-road">Hertford&nbsp;Road,&nbsp;N2</a></li>
              <li><a href="/holly-park">Holly&nbsp;Park</a></li>
              <li><a href="/lambertroad">Lambert&nbsp;Road,&nbsp;N12</a></li>
              <li><a href="/Leicester-Road">Leicester&nbsp;Road,&nbsp;N2</a></li>
              <li><a href="/Limes-Ave">Limes&nbsp;Avenue</a></li>
              <li><a href="/marion-road">Marion&nbsp;Road,&nbsp;NW7</a></li>
              <li><a href="/myddleton-park">Myddleton&nbsp;Park,&nbsp;N20</a></li>
          </ul>
          <ul>
              <li><a href="/Norwich-Walk">Norwich&nbsp;Walk&nbsp;HA8</a></li>
              <li><a href="/Ossulton-Way">Ossulton&nbsp;Way,&nbsp;N2</a></li>
              <li><a href="/Park-Avenue">Park&nbsp;Avenue,&nbsp;N3</a></li>
              <li><a href="/prospect-road">Prospect&nbsp;Road&nbsp;&amp;&nbsp;Place,&nbsp;NW2</a></li>
              <li><a href="/quinta-green">Quinta&nbsp;Green,&nbsp;Mays&nbsp;Lane</a></li>
              <li><a href="/ravenscroftpark">Ravenscroft&nbsp;Park,&nbsp;EN5</a></li>
              <li><a href="/rowlandsclose">Rowlands&nbsp;Close,&nbsp;NW7</a></li>
              <li><a href="/sandwick-close">Sandwick&nbsp;Close,&nbsp;NW7</a></li>
              <li><a href="/Sellers-Hall">Sellers&nbsp;Hall&nbsp;Close,&nbsp;N3</a></li>
              <li><a href="/southwayparty">Southway,&nbsp;N20</a></li>
              <li><a href="/Stanaley-Road">Stanaley&nbsp;Road,&nbsp;N2</a></li>
              <li><a href="/Victoria-Rd">Victoria&nbsp;Road</a></li>
              <li><a href="/westburyroad">Westbury&nbsp;Road,&nbsp;N12</a></li>
              <li><a href="/woodlands-avenue">Woodlands&nbsp;Ave,&nbsp;N3</a></li>
              <li><a href="/woodvilleroad">Woodville&nbsp;Road,&nbsp;EN5</a></li>              
          </ul>
          <div style="width:100%;clear:both;height:1px"></div>
        </div>
        <div style="font-size:1.126em;padding-bottom:0.25em;">
            <p>
                 The Royal Wedding of HRH Prince William and Kate Middleton took place on 29 April 2011. 
                 It was a wonderful excuse for a street party &mdash; not only is a street party a fun way for residents 
                 to celebrate such an event, it&rsquo;s also a great way to get to know your neighbours better.               
            </p>
        </div>
        <div style="float:right; width:45%;margin-left:1em;" class="pb-barnet-breakout">
          <h3>
              What you will need to do:
          </h3>
          <p>
              Sorry &mdash; it&rsquo;s too late to notify us of a <i>new</i> Royal Wedding street party now.
          </p>
          <h3>
            What we pledged: 
          </h3>
          <ul>
            <li>
              Barnet Council pledged support street parties on the day of the Royal Wedding, including arranging insurance free of charge, <strong>if at least three households in a street</strong> signed up to holding an event.
            </li>
          </ul>
        </div>
        <h3>
            Note: Deadline for applications is now closed
        </h3> 
        
    <? } else { ?>
       
       <? if ($topic == 'thebiglunch') { ?>
      
          <!-- =========================== the big lunch =================================== -->
        
          <h2>The Big Lunch: a one day get-together for neighbours</h2>
          <div class='ms-thebiglunch-banner' style="height:200px;"></div>
          <div id="ms-explicit-party-list">
            <h3>Sign up to a Big Lunch street party pledge:</h3>          
            <ul>
                <li><a href="/corringham-rd-bl">Corringham&nbsp;Road,&nbsp;NW11</a></li>
                <li><a href="/heath-view-bl">Heath&nbsp;View,&nbsp;N2</a></li>
                <li><a href="/jackson-rd-bl">Jackson&nbsp;Road,&nbsp;EN4</a></li>
                <li><a href="/park-rd-bl">Park&nbsp;Road,&nbsp;EN5</a></li>
                <li><a href="/salcombe-gdns-bl">Salcombe&nbsp;Gardens,&nbsp;NW7</a></li>
            </ul>
            <div style="width:100%;clear:both;height:1px"></div>
          </div>
          <div style="font-size:1.126em;padding-bottom:0.25em;">
             <a href="http://www.barnet.gov.uk/biglunch" title="about The Big Lunch"><img 
                alt="The Big Lunch logo" src="/microsites/barnet/the_big_lunch_logo_212_x_90.png" 
                style="float:right;width:212px;height:90px;border:none;margin: 0 1em 1em 1em;"/></a>
            <p>
                <a href="http://www.barnet.gov.uk/biglunch">The Big Lunch</a> is a very simple idea from the Eden Project. 
                The aim is to get as many people as possible across the whole of the UK to have lunch with their
                neighbours in a simple act of community, friendship and fun. This year it&rsquo;s happening on 
                <strong>Sunday&nbsp;5&nbsp;June</strong>.
            </p>
            <p>
                A Big Lunch can be anything from a few neighbours getting together in the garden or on the street, 
                to a full blown party with food, music and decoration that quite literally stops the traffic.              
                If it is on the street, you will need to get Barnet Council&rsquo;s permission to make sure it is OK 
                for you to close the road and sort out your insurance. To do this, we would like 3 households on each 
                street to sign up to a pledge.
            </p>
          </div>
          <div style="width:100%;clear:both;height:1px"></div>
          <div style="float:right; width:45%;margin-left:1em;" class="pb-barnet-breakout">
            <h3>
                What you will need to do:
            </h3>
            <ul>
              <li>
                  Read our guide to <a href="http://www.barnet.gov.uk/biglunch">holding a Big Lunch street party</a>.
              </li>
              <!-- <li>
                Read the <a href="http://www.barnet.gov.uk/royal-wedding-insurance.pdf">policy wording of the public liability insurance cover</a> arranged by the Council.
              </li>-->
              <li>
                Fill in a <a href="http://www.barnet.gov.uk/Big-Lunch-A4-Form-web.pdf">The Big Lunch street party application form</a> telling us about the road or section of road you would like to close on the day.
              </li>            
            </ul>
            <h3>
              What we're pledging: 
            </h3>
            <ul>
              <li>
                Barnet Council will support street parties on the day of The Big Lunch, including arranging insurance free of charge, if at least three households in a street sign up to get involved. 
              </li>
            </ul>
          </div>
          <h3>
              Can&rsquo;t see your road?  Start a new pledge!
          </h3>            
          <p>
              If there&rsquo;s not already a pledge for your road, simply start one. 
              Ideally, you&rsquo;ll need to get a minimum of 3&nbsp;households involved. 
          </p>
          
          
          <? } elseif ($topic == 'adoptastreet') { ?>

             <!-- =========================== adopt-a-street =================================== -->

             <h2>Adopt a Street</h2>
             <div class='ms-adoptastreet-banner' style="height:200px;"></div>
             <div id="ms-explicit-party-list">
               <h3>Sign up to an Adopt-a-Street pledge:</h3>          
               <ul>
                   <li><a href="/example-rd-bl">Example&nbsp;Road,&nbsp;NW11</a></li>
               </ul>
               <div style="width:100%;clear:both;height:1px"></div>
             </div>
             <div style="font-size:1.126em;padding-bottom:0.25em;">
               <p>
                 When residents sign up to adopt their street they will receive training from council officers 
                 and be provided with appropriate equipment such as litter pickers, shovels and rubbish sacks.
                 Prior to the winter months, 'adopted' streets will also receive a delivery of grit and 
                 equipment to remove snow and spread grit on their pavements.
               </p>
               <p>
                 After a highly successful trial, Barnet Council is offering similar support to an initial 
                 10 streets who sign up to the scheme.
                 To do this, we would like 6 households on each street to sign up to a pledge.
               </p>
               <p>
                 <a href="http://www.barnet.gov.uk/press_releases.htm?id=2401">Read more about the scheme.</a>
                </p>
             </div>
             <div style="width:100%;clear:both;height:1px"></div>
             <div style="float:right; width:45%;margin-left:1em;" class="pb-barnet-breakout">
               <h3>
                   What you will need to do:
               </h3>
               <ul>
                 <li>
                   Lorem ipsum dolor sit amet, consectetur adipisicing elit
                 </li>
                 <li>
                   velit esse cillum dolore eu fugiat nulla pariatur
                </li>
               </ul>
               <h3>
                 What we're pledging: 
               </h3>
               <ul>
                 <li>
                   Barnet Council will support street adoption for 10 streets
                   if at least six households in the street sign up to get involved. 
                 </li>
               </ul>
             </div>
             <h3>
                 Can&rsquo;t see your road?  Start a new pledge!
             </h3>            
             <p>
                 If there&rsquo;s not already a pledge for your road, simply start one. 
                 Ideally, you&rsquo;ll need to get a minimum of 6&nbsp;households involved. 
             </p>
                 
       <? } else { 

          print h2("Suggest a pledge");
        
          print "<p>Do you have an idea for a pledge that could appear on this site?</p>";
          print "<p>What would you like to get done? </p>";

        }
        print "<p>";
        $contact_email = str_replace('@', '&#64;', OPTION_CONTACT_EMAIL);
        printf(_('If you prefer, you can email %s instead of using the form.'), '<a href="mailto:' . $contact_email . '">' . $contact_email . '</a>');
        print "</p>";
        ?>
        <p><label for="name"><?=_('Your name:') ?></label> <input type="text" id="name" name="name" value="<?=htmlspecialchars($name) ?>" size="30">

        <p><label for="e"><?=_('Your email:') ?></label> <input type="text" id="e" name="e" value="<?=htmlspecialchars($email) ?>" size="30"></p>

        <input type="hidden" id="subject" name="subject" value="">

        <?
            if ($topic == 'thebiglunch' || $topic == 'adoptastreet'){
        ?>
                <input name="topic" type="hidden" value="<?= $topic ?>" />
                <p>
                    <label for="message">Your street:</label> <input id="message" name="message" type="text" value="<?=htmlspecialchars(get_http_var('message', true)) ?>" size="30"/>
                    <br/><span style="padding-left:5em; font-size:90%;">(it helps us if you include your&nbsp;postcode)</span>
                </p>
                <p>
                    <label for="custom">Your phone number:</label> <input id="custom" name="custom" type="text" value="<?=htmlspecialchars(get_http_var('custom', true)) ?>" size="20"/>
                    <br/><span style="padding-left:5em; font-size:90%;">(optional, but it&rsquo;s really handy if we can call you&nbsp;too)</span>
                </p>
        <?      
            } else {
        ?>
            <p><label for="message">Your suggestion:</label>
            <br><textarea rows="7" cols="40" name="message" id="message"><?=htmlspecialchars(get_http_var('message', true)) ?></textarea></p>
        <? } ?>

        <p>
        <input type="submit" name="submit" value="Send to PledgeBank team"></p>
        <?
    }
?>

<? if ($topic=='royalwedding') { ?>

  <!-- now closed: removed  -->

<? } elseif ($topic=='thebiglunch') { ?>

  <p>The PledgeBank team will . . .</p>
   <ul>
     <li>
         email you the <a href="http://www.barnet.gov.uk/Big-Lunch-A4-Form-web.pdf">Big Lunch street party application form</a> you will need to close off your street
     </li>
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

<? } elseif ($topic=='adoptastreet') { ?>

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


<? } elseif (! $comment_id ) { ?>
  <p>The PledgeBank team will...</p>
  <ul>
    <li>review suggestions and add them to the website</li>
    <li>Share your pledge through tweets, social networks and flyers</li>
    <li>Recruit people to help and get things done</li>
  </ul>
<? } ?>
</form>


