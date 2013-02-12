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
    <!-- ============================= grit my school 2013 (note! not 2012) ================================ -->
    
    <ul class="search_results">
        <li><a href="/type/gritmyschool2013" title="Grit my school" class="ms-pledge-list-a"><div class="ms-pledge-list-icon" style="background-image:url(/microsites/barnet/preloaded/frosty_mimosa.jpg);"></div></a><a href="/type/gritmyschool2013">Grit My School 2013 pledges</a><br>
        </li>
    </ul>
    <div class="barnet-type-pledges">
		<p>
			The <a href="/type/gritmyschool2013">Grit My School 2013</a> pledges have just closed.        
        </p>
        <?  print pledge_get_ul_list_by_type('gritmyschool2013', 2, 3, "", true); ?>
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
