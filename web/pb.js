/*
 * pb.js
 * Javascript parts of PledgeBank website.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: pb.js,v 1.39 2007-07-29 22:25:44 matthew Exp $
 * 
 */

var greyed = [ 
    ['title', _('<Enter your pledge>')],
    ['date', _('<Date>')],
    ['name', _('<Enter your name>')],
    ['q', _('<Enter town or keyword>')]
    ]

// http://www.scottandrew.com/weblog/articles/cbs-events
function addEvent(obj, evType, fn){ 
    if (obj.addEventListener){ 
        obj.addEventListener(evType, fn, true); 
        return true; 
    } else if (obj.attachEvent){ 
        var r = obj.attachEvent("on"+evType, fn); 
        return r; 
    } else { 
        return false; 
    }
}

function pluralize(t) {
    if (document && document.getElementById) {
        d = document.getElementById('type');
        v = d.value;
        w = s.value;
        if (t==1) {
            if (v==_('other local people')) d.value = _('other local person');
        } else {
            if (v==_('other local person')) d.value = _('other local people');
        }
    }
}

function fadein(thi) {
    id = thi.id
    for (var j = 0; j < greyed.length; j++) {
        if (greyed[j][0] == id && greyed[j][1] == thi.value) {
            thi.className = '';
            thi.value = '';
        }
    }
}
function fadeout(thi) {
    id = thi.id
    for (var j = 0; j < greyed.length; j++) {
        if (greyed[j][0] == id && thi.value == '') {
            thi.className = 'greyed';
            thi.value = greyed[j][1];
        }
    }
}

addEvent(window, 'load', greyOutInputs);
function greyOutInputs() {
    if (!document) return
    if (document.getElementById) {
        for (var j = 0; j < greyed.length; j++) {
            d = document.getElementById(greyed[j][0])
            if (d && d.value == '') d.value = greyed[j][1]
            if (d && d.value == greyed[j][1]) d.className = [d.className, 'greyed'].join(' ');
        }
        d = document.getElementById('ref')
        if (d && d.value.length<6) d.className = [d.className, 'greyed'].join(' ');

    }

    d = document.forms.pledge
    if (d) {
        if (d.visibility) grey_pin(d.visibility[0].checked)
        if (d.place) update_place_local(d.place,false)
    }
    d = document.forms.localalert
    if (d) {
        if (d.place) update_place_local(d.place,false)
    }
}

function checklength(thi) {
    var l = thi.value.length
    if (l<6) thi.className = 'greyed';
    else thi.className = '';
}

// optionclick is "true" if user just clicked, or "false" during page load
function update_place_local(item, optionclick) {
    var d = item.form
    var e = d.elements['country']

    // Find country/state
    countryPicked = e.options[e.selectedIndex].value
    var arr = countryPicked.split(',')
    countryPicked = arr[0]
    state = arr[1]

    // Work out our situation
    iscountry = (countryPicked != _("Global") && countryPicked != _("(separator)") && countryPicked != _("(choose one)"));
    isuk = (countryPicked == "GB");
    if (d.elements['local1'])
        islocal = d.elements['local1'].checked
    else
        islocal = true // Happens in alert.php
    hasgazetteer = (gaze_countries[countryPicked] == 1)

    // Ghost things appropriately
    grey_local(!iscountry || !hasgazetteer)
    grey_ifyes(!islocal || !iscountry || !hasgazetteer)
    grey_place(!islocal || !iscountry || !hasgazetteer, optionclick)
    grey_postcode(!islocal || !isuk || !hasgazetteer, optionclick)

    // Front page text
    var place_postcode_label = document.getElementById('place_postcode_label')
    if (place_postcode_label) {
        var current = place_postcode_label.childNodes[0].nodeValue
        if (current == _('Town:') && countryPicked == 'GB') {
            place_postcode_label.childNodes[0].nodeValue = _('Postcode or town:')
        } else if (current == _('Postcode or town:') && countryPicked != 'GB') {
            place_postcode_label.childNodes[0].nodeValue = _('Town:')
        }
    }
}

function grey_postcode(t, optionclick) {
    if (!document || !document.getElementById) return
    d = document.getElementById('postcode_line')
    if (!d) return
    d.style.display = t ? 'none' : 'block';
    grey_thing(t, 'postcode', optionclick)
}

function grey_place(t, optionclick) {
    if (!document || !document.getElementById) return
    d = document.getElementById('place_line')
    if (!d) return
    d.style.display = t ? 'none' : 'block';
    grey_thing(t, 'place', optionclick)
}

function grey_local(t) {
    if (!document || !document.getElementById) return
    d = document.getElementById('local_line')
    if (!d) return
    d.style.display = t ? 'none' : 'block';
    grey_thing(t, 'local0', false)
    grey_thing(t, 'local1', false)
}

function grey_ifyes(t) {
    if (!document || !document.getElementById) return
    d = document.getElementById('ifyes_line')
    if (!d) return
    d.style.display = t ? 'none' : 'block';
}

function grey_pin(t) {
    grey_thing(t, 'pin', true)
}

function grey_thing(t, e, focus) {
    var d = document.getElementById(e)
    if (t) {
        d.disabled = true
        d.className = 'greyed';
        d.style.borderColor = '#999999'
    } else {
        d.disabled = false
        d.className = '';
        d.style.borderColor = '#9c7bbd'
        if (focus) {
            d.focus()
        }
    }
}

// Used in ref-announce.php
function count_sms_characters() {
    n = document.getElementById("message_sms").value.length;
    /* XXX should really use the DOM for that but that requires a little
     * appendChild/removeChild dance that might not even work in old
     * browsers. So do it lazily instead: */
    if (n <= 160) {
        text = sprintf(_("You have used %d characters; %d remain."), n, 160-n);
    } else {
        text = "<b>" + sprintf(_("You have used %d characters, which is %d more than will fit in an SMS. Please make your message shorter."), n, n-160) + "</b>";
    }
    document.getElementById("smslengthcounter").innerHTML = text;
}

function _(s) {
    if (typeof(translation)!='undefined' && translation[s]) return translation[s]
    return s
}

// Noddy version that only does %d and nothing more
function sprintf() {
    if (!arguments || arguments.length < 1 || !RegExp) {
        return
    }
    var str = arguments[0]
    var re = /%d/
    var numSubstitutions = 0
    while(typeof(arguments[++numSubstitutions])!='undefined' && (str = str.replace(re, parseInt(arguments[numSubstitutions], 10))) ) {}
    return str
}

// Used in new pledge creation
function toggleNewModifyFAQ() {
    if (!document) 
        return true
    if (document.getElementById) {
        var modifyfaq = document.getElementById('modifyfaq')
        if (modifyfaq.style.display == 'block')
            modifyfaq.style.display = 'none'
        else
            modifyfaq.style.display = 'block'
        return false
    } else
        return true
}

function byarea_town_keypress(thi) {
    if (!document || !document.getElementById)
        return
    var c = document.getElementById('country')
    if (!c)
        return
    mySociety.asyncRequest("/ajax-gaze.php?" + 
                "country=" + encodeURIComponent(c.value) + 
                "&place=" + encodeURIComponent(thi.value), 
        function(xmlhttp) {
            if (xmlhttp.readyState==4) {
                var d = document.getElementById('byarea_town_ajax');
                if (d)
                    d.innerHTML = xmlhttp.responseText;
                else
                    alert('not found byarea_town_ajax');
            }
        });
}

function check_login_password_radio() {
    if (!document || !document.getElementById) return
    d = document.getElementById('loginradio2')
    if (!d) return
    d.checked = true
}

function highlight_fade(e) {
    var el = document.getElementById(e);
    var step = 0;
    var steps = 75;
    var timer = window.setInterval(function() {
        var b = Math.ceil(128 + Math.pow(step/steps, 4) * (255-128));
	el.style.backgroundColor = "rgb("+[255,255,b].join(',')+")";
	if (step++ >= steps) {
	    window.clearInterval(timer);
	}
    }, 20);
}

