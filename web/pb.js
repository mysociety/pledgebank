var greyed = [ ['action', '<Enter your pledge>'], ['date', '<Date>'] ]

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
	s = document.getElementById('signup');
	v = d.value;
        w = s.value;
	if (t==1) {
            if (w=='sign up') s.value = 'signs up';
	    if (v=='people') d.value = 'person';
	} else {
	    if (w=='signs up') s.value = 'sign up';
	    if (v=='person') d.value = 'people';
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
    for (var j = 0; j < greyed.length; j++) {
        d = document.getElementById(greyed[j][0])
        if (d && d.value == '') d.value = greyed[j][1]
        if (d && d.value == greyed[j][1]) d.style.color = '#999999'
    }
}

