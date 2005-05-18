/*
 * pb.js
 * Javascript parts of PledgeBank website.
 * 
 * Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
 * Email: matthew@mysociety.org. WWW: http://www.mysociety.org
 *
 * $Id: pb.js,v 1.9 2005-05-18 19:55:14 francis Exp $
 * 
 */

var greyed = [ 
    ['title', '<Enter your pledge>'], 
    ['date', '<Date>'],
    ['name', '<Enter your name>']
    ]

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

    if (document.pledge && document.pledge.visibility)
        grey_password(document.pledge.visibility[0].checked)

    if (document.pledge && document.pledge.local)
        update_postcode_local(false)
}

function checklength(thi) {
    var l = thi.value.length
    if (l<6) thi.style.color = '#999999'
    else thi.style.color = '#000000'
}

// optionclick is "true" if user just clicked, or "false" during page load
function update_postcode_local(optionclick) {
    isuk = document.pledge.country.options[document.pledge.country.selectedIndex].value == "UK"
    islocal = document.pledge.local1.checked
    grey_local(!isuk, optionclick)
    grey_postcode(!islocal || !isuk, optionclick)
}
function grey_postcode(t, optionclick) {
    if (t) {
        document.getElementById('postcode_line').style.color = '#999999'
    } else {
        document.getElementById('postcode_line').style.color = '#000000'
    }
    grey_thing(t, 'postcode', optionclick)
}
function grey_local(t, optionclick) {
    if (t) {
        document.getElementById('local_line').style.color = '#999999'
    } else {
        document.getElementById('local_line').style.color = '#000000'
    }
    grey_thing(t, 'local0', false)
    grey_thing(t, 'local1', false)
}

function grey_password(t) {
    grey_thing(t, 'password', true)
}

function grey_thing(t, e, focus) {
    d = document.getElementById(e)
    if (t) {
        d.disabled = true
        d.style.color = '#999999'
    } else {
        d.disabled = false
        d.style.color = '#000000'
        if (focus) {
            d.focus()
        }
    }
}
