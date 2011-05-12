<?
// index.php:
// Front page template for Barnet PledgeBank.
//
// Copyright (c) 2010 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org

?>

<h2>Barnet PledgeBank is a site to get things done.</h2>

<div id="currentpledges">
<?
    global $pb_today;
    $pledges = pledge_get_list("date >= '$pb_today'",
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
  <div style="border:1px solid white;">
    <div style="border-top: 4px solid #00ada1;margin-top:1em;">
      <a href="http://www.barnet.gov.uk/biglunch" title="about The Big Lunch"><img 
        alt="The Big Lunch logo" src="/microsites/barnet/the_big_lunch_logo_212_x_90.png" 
        style="float:right;width:212px;height:90px;border:none;margin:1em;"/></a>
      <h3><a href="/new/thebiglunch" title="The Big Lunch" >The Big&nbsp;Lunch Street Parties</a></h3>
      <p>
        
        We're supporting street parties in Barnet for the Big Lunch on the <strong>5&nbsp;June</strong> with the following pledges:
        sign up or <a href="/new/thebiglunch">start a pledge</a> for a party in your street!
      </p>
      <ul style="margin-left:2em;">
          <li><a href="/corringham-rd-bl">Corringham&nbsp;Road,&nbsp;NW11</a></li>
          <li><a href="/heath-view-bl">Heath&nbsp;View,&nbsp;N2</a></li>
          <li><a href="/jackson-rd-bl">Jackson&nbsp;Road,&nbsp;EN4</a></li>
          <li><a href="/park-rd-bl">Park&nbsp;Road,&nbsp;EN5</a></li>
          <li><a href="/salcombe-gdns-bl">Salcombe&nbsp;Gardens,&nbsp;NW7</a></li>
          
      </ul>
    </div>
  </div>
</div>  
