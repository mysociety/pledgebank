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
    <img alt="portrait of William and Kate" src="/microsites/barnet/royalwedding_small_portrait.jpg" style="float:right;width:170px;height:208px;border:none;"/>
    <div style="border-top: 4px solid #00ada1;margin-top:32px;">
      <h3><a href="/new/royalwedding" title="Street Party" >Royal Wedding Street Parties</a></h3>
      <p>
        We're pledging to support street parties for the Royal Wedding on 29 April.
        If you can't see a pledge for <i>your</i> street here, why not get involved by <a href="/new/royalwedding">setting one&nbsp;up</a>? 
      </p>
      <ul style="margin-left:2em;">
          <li><a href="/brownlow-road">Brownlow&nbsp;Road,&nbsp;N3</a></li>
          <li><a href="/chislehurst-ave">Chislehurst&nbsp;Avenue,&nbsp;N12</a></li>
          <li><a href="/corringham-road">Corringham&nbsp;Road,&nbsp;NW11</a></li>
          <li><a href="/CrewysRoad">Crewys&nbsp;Road,&nbsp;NW2</a></li>
          <li><a href="/dalegreenparty">Dale&nbsp;Green&nbsp;Road,&nbsp;N11</a></li>
          <li><a href="/falklandroad">Falkland&nbsp;Road,&nbsp;EN5</a></li>
          <li><a href="/harmandriveparty">Harman&nbsp;Drive,&nbsp;NW2</a></li>
          <li><a href="/hartlandroad">Hartland&nbsp;Road,&nbsp;N11</a></li>
          <li><a href="/hertfordclose">Hertford&nbsp;Close,&nbsp;EN4</a></li>
          <li><a href="/hertford-road">Hertford&nbsp;Road,&nbsp;N2</a></li>
          <li><a href="/lambertroad">Lambert&nbsp;Road,&nbsp;N12</a></li>
          <li><a href="/mandevilleroad">Mandeville&nbsp;Road,&nbsp;N14</a></li>
          <li><a href="/PrincesAvenue">Princes&nbsp;Avenue,&nbsp;N3</a></li>
          <li><a href="/ravenscroftpark">Ravenscroft&nbsp;Park,&nbsp;EN5</a></li>
          <li><a href="/rowlandsclose">Rowlands&nbsp;Close,&nbsp;NW7</a></li>
          <li><a href="/southwayparty">Southway,&nbsp;N20</a></li>
          <li><a href="/westburyroad">Westbury&nbsp;Road,&nbsp;N12</a></li>
          <li><a href="/westchester">Westchester&nbsp;Drive,&nbsp;NW4</a></li>
          <li><a href="/woodlands-avenue">Woodlands&nbsp;Ave,&nbsp;N3</a></li>
          <li><a href="/woodvilleroad">Woodville&nbsp;Road,&nbsp;EN5</a></li>
      </ul>
    </div>
  </div>
</div>  
