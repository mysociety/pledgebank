<?
// index.php:
// Front page template for Barnet PledgeBank.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

// note: calls to pledge_get_ul_list_by_type on the *front* page should probably only
//       list open pledges, so set $open_pledges_only=true in the args
?>

<h1>Barnet PledgeBank is a site to get things done.</h1>

<div id="currentpledges">

  <!-- ============================= London 2012 Olympics ================================ -->
  <ul class="search_results">
      <li><a href="/type/olympics2012" title="London 2012 Olympics street parties" class="ms-pledge-list-a"><div class="ms-pledge-list-icon" style="background-image:url(/microsites/barnet/preloaded/olympics2012.jpg);"></div></a><a href="/type/olympics2012">London 2012 Olympic street party pledges</a><br>
      </li>
  </ul>
  <div class="barnet-type-pledges">
      <p>
          Barnet Council will help you celebrate the London 2012 Olympics with a street party in your street 
          between 27&nbsp;July and 12&nbsp;August 2012 provided at least 
          <?= microsites_get_pledge_type_details('olympics2012', 'preloaded_data', 'target') ?>
          households agree to help organise it.            
          Sign up or <a href="/type/olympics2012">start a pledge</a> to arrange your party!
      </p>
      <?  print pledge_get_ul_list_by_type('olympics2012', 2); ?>
      <p style="clear:both;"> 
	      We can also help arrange  <a href="/type/olympicparkparty">parties in the park</a>.
	  </p>
	   <?  print pledge_get_ul_list_by_type('olympicparkparty', 2, 3, "", true); ?>
      <div style='clear:both;height:0.3em'></div>
  </div>

    <!-- ============================= adopt a street ================================ -->
    
    <ul class="search_results">
        <li><a href="/type/adoptastreet" title="Adopt a street" class="ms-pledge-list-a"><div class="ms-pledge-list-icon" style="background-image:url(/microsites/barnet/preloaded/adopt_a_street.jpg);"></div></a><a href="/type/adoptastreet">Adopt-a-Street pledges</a><br>
        </li>
    </ul>
    <div class="barnet-type-pledges">
        <p>
            Barnet Council will support street adoption for 10 streets if at least three households in the street sign up to get involved:
            sign up or <a href="/type/adoptastreet">start a pledge</a> to adopt your street!
        </p>
        <?  print pledge_get_ul_list_by_type('adoptastreet', 2, 3, "", true); ?>
        <div style='clear:both;height:0.3em'></div>
    </div>

    
    <!-- ============================= standalone pledges ================================ -->

<?
    global $pb_today;
    $pledges = pledge_get_list("date >= '$pb_today' AND pledge_type is null",
        array('global'=>false, 'main'=>true, 'foreign'=>false)
    );
    print format_pledge_list($pledges, array('firstperson' => 'includename', 'iconpath' => '/microsites/barnet/'));
?>
</div>

<div id="barnet_expl">
  <p>
    Barnet Pledgebank can be used to gather together people to get
    projects done. These can be tasks such as clearing snow and ice from
    pavements in the street, painting over graffiti or setting up computer
    classes in your area.
  </p>
  <p>
    The website is based on the simple principle that the person making
    the online pledge will work to make it happen &ldquo;but only if&rdquo; a number
    of other people commit too.
  </p>
  <p>
    Pledges don't have to be started by the council &mdash; organisations,
    schools, community and volunteer groups can all get involved.  
    <a href="/contact">Submit your pledge ideas</a>.
  </p>
  <p>
    PledgeBank is designed to help residents passionate about doing their
    bit for the community. By working together, we can offer services that
    are popular and worthwhile.
  </p>
</div>  
