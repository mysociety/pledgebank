/*
 * pb.js
 * Javascript parts of PledgeBank website.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: pb.js,v 1.24 2005-08-26 17:31:45 matthew Exp $
 * 
 */

var greyed = [ 
    ['title', '<Enter your pledge>'], 
    ['date', '<Date>'],
    ['name', '<Enter your name>']
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
            if (v=='other local people') d.value = 'other local person';
        } else {
            if (v=='other local person') d.value = 'other local people';
        }
    }
}

function fadein(thi) {
    id = thi.id
    for (var j = 0; j < greyed.length; j++) {
        if (greyed[j][0] == id && greyed[j][1] == thi.value) {
            thi.style.color = '#000000';
            thi.value = '';
        }
    }
}
function fadeout(thi) {
    id = thi.id
    for (var j = 0; j < greyed.length; j++) {
        if (greyed[j][0] == id && thi.value == '') {
            thi.style.color = '#999999';
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
            if (d && d.value == greyed[j][1]) d.style.color = '#999999'
        }
        d = document.getElementById('ref')
        if (d && d.value.length<6) d.style.color = '#999999'

    }

    d = document.forms.pledge
    if (d) {
        if (d.visibility) grey_pin(d.visibility[0].checked)
        if (d.place) update_place_local(d.place,false)
    }
}

function checklength(thi) {
    var l = thi.value.length
    if (l<6) thi.style.color = '#999999'
    else thi.style.color = '#000000'
}

// optionclick is "true" if user just clicked, or "false" during page load
function update_place_local(item, optionclick) {
    var d = item.form;
    var e = d.elements['country'];

    // Find country/state
    countryPicked = e.options[e.selectedIndex].value
    var arr = countryPicked.split(',')
    countryPicked = arr[0]
    state = arr[1]

    // Work out our situation
    iscountry = (countryPicked != "Global" && countryPicked != "(separator)" && countryPicked != "(choose one)");
    isuk = (countryPicked == "GB");
    if (d.elements['local1'])
        islocal = d.elements['local1'].checked
    else
        islocal = true // Happens in alert.php
    hasgazetteer = (gaze_countries[countryPicked] == 1)

    // Ghost things appropriately
    grey_local(!iscountry || !hasgazetteer); 
    grey_ifyes(!islocal || !iscountry || !hasgazetteer);
    grey_place(!islocal || !iscountry || !hasgazetteer, optionclick);
    grey_postcode(!islocal || !isuk || !hasgazetteer, optionclick);

    // Front page text
    var place_postcode_label = document.getElementById('place_postcode_label')
    if (place_postcode_label) {
        var current = place_postcode_label.childNodes[0].nodeValue
        if (current == 'Town:' && countryPicked == 'GB') {
            place_postcode_label.childNodes[0].nodeValue = 'Postcode or town:'
        } else if (current == 'Postcode or town:' && countryPicked != 'GB') {
            place_postcode_label.childNodes[0].nodeValue = 'Town:'
        }
    }
}

function grey_postcode(t, optionclick) {
    if (!document || !document.getElementById) return
    d = document.getElementById('postcode_line')
    if (!d) return
    d.style.color = t ? '#999999' : '#000000'
    grey_thing(t, 'postcode', optionclick)
}

function grey_place(t, optionclick) {
    if (!document || !document.getElementById) return
    d = document.getElementById('place_line')
    if (!d) return
    d.style.color = t ? '#999999' : '#000000'
    grey_thing(t, 'place', optionclick)
}

function grey_local(t) {
    if (!document || !document.getElementById) return
    d = document.getElementById('local_line')
    if (!d) return
    d.style.color = t ? '#999999' : '#000000'
    grey_thing(t, 'local0', false)
    grey_thing(t, 'local1', false)
}

function grey_ifyes(t) {
    if (!document || !document.getElementById) return
    d = document.getElementById('ifyes_line')
    if (!d) return
    d.style.color = t ? '#999999' : '#000000'
}

function grey_pin(t) {
    grey_thing(t, 'pin', true)
}

function grey_thing(t, e, focus) {
    var d = document.getElementById(e)
    if (t) {
        d.disabled = true
        d.style.color = '#999999'
        d.style.borderColor = '#999999'
    } else {
        d.disabled = false
        d.style.color = '#000000'
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
    if (n <= 160)
        text = "You have used " + n + " characters; " + (160 - n) + " remain.";
    else
        text = "<b>You have used " + n + " characters, which is " + (n - 160) + " more than will fit in an SMS. Please make your message shorter.</b>";
    document.getElementById("smslengthcounter").innerHTML = text;
}

