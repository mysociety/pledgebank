Pledge states
=============

1. Can new people sign up?
Tested for by pledge_is_valid_to_sign plpgsql function.

2. Has pledge been successful?
Which we test for with the success flag.

3. Has pledge expired?
We test for by comparing pledges.date with current_timestamp


Earlier notes:
--------------

Organiser:	* Name, Email, Nature of problem, Minimum threshold, Deadline by which pledges must be in, Optional: email addresses to alert
User:		* Name, Email, Pledges, Project
TIME'S UP! - What happens?

Home page
~~~~~~~~~
A list of the top 5 current highest signup pledges, plus the five newest pledges. Pledges should be identified by a simple, non encrypted number, so if you've seen one on a bus stop, say, you can type in a really short number (ie 1234) to find a live pledge. Perhaps this should be a free text search box too (gulp). Lastly, it should obviously have a 'Start your own pledge here' button fairly prominent.

How will people sign up to a pledge? 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Most people should have either a URL, or a unique number for a pledge. This should dump them at the simplest possible page. At the top it will state the basic wording of the pledge, phrased in the first person. At the end there should be a 'signature' box, where you can add your email address (is it worth asking for names?). Optionally, there will be a counter reflecting how many pledges have been recieved, how many are left, and how much time remains. Whether these motivate or discourage participation we'll have to see through experimentation. 

How will people publicise their pledges? 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
* We should keep URLs super simple - i.e pledgebank.com/123 
* We should offer some pro-forma PDF/word docs that are dynamically generated to contain the text of their pledge and the URL for fulfilling it. This will help reiterate the offline options. 
* We should auto link to other prospective locations (search ican?) 
* If there is a postcode section, provide contact details for local media? 
* Provide a service to email friends. I never use these, but prehaps Tom L or Stef have stats on their use. 
* Experiment with SMS? 

Is there any role for geographic technologies here? 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Often, when I explain PledgeBank to people, I find myself using geographic identities. In particular, I say "On my street", or "In my town". Arguably, it is enough for these to be in the text of the pledge. But if we had proper postcode systems working, we could do things like allow people to find pledges near them, or limit participants in a pledge to people within a certain radius. I suppose with the MaPit component, we could probably also let people pick electoral areas. But would they want to? 

Should we allow pledgees to communicate/organise? 
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
Lots of possibilities here, from 'No', to complicated opt-in or out email lists and forums. Bottom line - is anything worth doing?
