#!/usr/bin/env python2.3
#
# poster.cgi:
# Creates posters for printing out, caching them in a directory.
#
# Run from the command line to test files.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: poster.cgi,v 1.90 2006-12-08 08:35:00 francis Exp $
#

# TODO:
# Upgrade to Python 2.4

import sys
sys.path.append("../../pylib")

import os
import popen2
from time import time
from pyPgSQL import PgSQL
import fcgi
import tempfile
import string
import sha
import locale
import gettext
import re
_ = gettext.gettext

import PyRTF

from reportlab.pdfgen import canvas
from reportlab.lib.units import cm
from reportlab.lib.pagesizes import A4, LETTER # only goes down to A6
papersizes = {}
papersizesRTF = {}
papersizes['A4'] = A4
papersizes['A7'] = (A4[0] * 0.5, A4[1] * 0.25)
papersizes['letter'] = LETTER
papersizesRTF['A4'] = PyRTF.StandardPaper.A4
papersizesRTF['letter'] = PyRTF.StandardPaper.LETTER

from reportlab.lib.styles import ParagraphStyle

from reportlab.lib.styles import getSampleStyleSheet
from reportlab.platypus import Paragraph, Frame
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_RIGHT

boilerplate_sms_smallprint = _("SMS operated by charity UKCOD. Available in UK only. Sign-up message costs your normal text rate. Further messages are free. ")

# If you alter this, also alter phplib/microsites.php
microsites_from_extra_domains = { 'pledge.global-cool.com' : 'global-cool',
                                  'promise.livesimply.org.uk' : 'livesimply' };
                                               

## Microsite cusomisation (XXX put in pb/pylib/microsites.py I guess)
# Return True if posters for that microsite look different from default posters
# This is used to work out what to name the cache files.
def microsites_poster_different_look(microsite):
    return microsite in ('london', 'livesimply')
# Fill colour for background of logo
def microsites_poster_box_fill_colour():
    if microsite == 'london':
        return (0.93, 0.2, 0.22)
    elif microsite == 'livesimply':
        return (0.00, 0.67, 0.71)
    else:
        return (0.6, 0.45, 0.7)
# Colour for key words and numbers in text
def microsites_poster_html_highlight_colour():
    if microsite == 'london':
        return '#31659c'
    elif microsite == 'livesimply':
        return '#00aab5'
    else:
        return '#522994'
# Colour on RTF posters
def microsites_poster_rtf_colour():
    if microsite == 'london':
        return PyRTF.Colour('pb', 49, 101, 156) # 31659c
    elif microsite == 'livesimply':
        return PyRTF.Colour('pb', 0, 170, 181) # 00aab5
    else:
        return PyRTF.Colour('pb', 82, 41, 148) # 522994
# Draw the logo at the bottom - x1 and y1
def microsites_poster_logo(c, x1, y1, w, h_purple, p_footer):
    # Draw purple bar
    c.setFillColorRGB(*microsites_poster_box_fill_colour())
    c.rect(x1, y1, w, h_purple, fill=1, stroke=0)

    if microsite == "livesimply":
        # Logo for Live Simply promise
        # ... left hand text part
        livesimply_banner_l_w = 672 * (h_purple / 120)
        c.drawInlineImage("microsites/livesimply/promise_banner_left.jpg", x1, y1, width=livesimply_banner_l_w,height=h_purple)
        # ... right hand fish part, if it fits
        livesimply_banner_r_w = 268 * (h_purple / 120)
        if w - livesimply_banner_r_w > livesimply_banner_l_w:
            c.drawInlineImage("microsites/livesimply/promise_banner_right.jpg", x1 + w - livesimply_banner_r_w, y1, width=livesimply_banner_r_w,height=h_purple)
    else:
        # Logo for main PledgeBank
        story = [
            Paragraph(_('<font color="#ffffff">Pledge</font>Bank.com'), p_footer)
        ]
        f = Frame(x1, y1+0.1*h_purple, w, h_purple, showBoundary = 0, id='Footer',
                topPadding = 0, bottomPadding = 0)
        f.addFromList(story, c)

# this is a special function to be able to use bold and italic in ttfs
# see may 2004 reportlab users mailing list
def myRegisterFont(font):
    "registers a font, including setting up info for accelerated string width"
    fontName = font.fontName
    _fonts[fontName] = font
    if font._multiByte:
        ttname = string.lower(font.fontName)
    else:
        if _stringWidth:
            _rl_accel.setfontinfo(string.lower(fontName),
                    _dummyEncoding,
                    font.face.ascent,
                    font.face.descent,
                    font.widths)

import mysociety.config
mysociety.config.set_file("../conf/general")

def add_standard_TTF(name, filename):
    myRegisterFont(ttfonts.TTFont(name, font_dir + '/'+filename+'.ttf'))
    myRegisterFont(ttfonts.TTFont(name+'-Bold', font_dir + '/'+filename+'b.ttf'))
    myRegisterFont(ttfonts.TTFont(name+'-BoldItalic', font_dir + '/'+filename+'bi.ttf'))
    myRegisterFont(ttfonts.TTFont(name+'-Italic', font_dir + '/'+filename+'i.ttf'))
    addMapping(filename, 0, 0, name)
    addMapping(filename, 1, 0, name+'-Bold')
    addMapping(filename, 0, 1, name+'-Italic')
    addMapping(filename, 1, 1, name+'-BoldItalic')

from reportlab.pdfbase import pdfmetrics, ttfonts
from reportlab.pdfbase.pdfmetrics import _fonts
from reportlab.lib.fonts import addMapping
font_dir = mysociety.config.get('PB_FONTS')
myRegisterFont(ttfonts.TTFont('Rockwell', font_dir + '/rock.ttf'))
myRegisterFont(ttfonts.TTFont('Rockwell-Bold', font_dir + '/rockb.ttf'))
pdfmetrics.registerFont(ttfonts.TTFont('Transport', font_dir + '/transport.ttf'))
addMapping('rockwell', 0, 0, 'Rockwell')
addMapping('rockwell', 1, 0, 'Rockwell-Bold')
addMapping('rockwell', 0, 1, 'Rockwell')
addMapping('rockwell', 1, 1, 'Rockwell')
add_standard_TTF('Arial', 'arial')
add_standard_TTF('Georgia', 'georgia')
add_standard_TTF('Trebuchet MS', 'trebuchet')

db = PgSQL.connect(mysociety.config.get('PB_DB_HOST') + ':' + mysociety.config.get('PB_DB_PORT') + ':' + mysociety.config.get('PB_DB_NAME') + ':' + mysociety.config.get('PB_DB_USER') + ':' + mysociety.config.get('PB_DB_PASS'))

types = ["flyers16", "flyers4", "flyers1", "flyers8"]
sizes = ["A4", "A7", "letter"]
formats = ["pdf", "png", "gif", "rtf"]

# return 1st, 2nd, 3rd etc.
def ordinal(day):
    # for now not localised, only called for GB
    assert locale.getlocale()[0] == 'en_GB'

    # English
    if day==11 or day==12:
        return 'th'
    day = day % 10
    if day==1:
        return 'st'
    elif day==2:
        return 'nd'
    elif day==3:
        return 'rd'
    return 'th'

# add commas as thousand separators to integers.
def format_integer(i):
    return locale.format("%d", i, 1)

# Also update has_sms in phplib/pledge.php
def has_sms(pledge):
    # Private pledges have no SMS for now
    if pledge['pin']:
        return False
    # Nor do byarea pledges (too hard to do interface to choose place)
    if pledge['target_type'] == 'byarea':
        return False
    # Global pledges, we do show SMS (but will flag UK only)
    if not pledge['country']:
        return True
    # Non-UK countries have no SMS
    if pledge['country'] != 'GB':
        return False
    # UK countries have SMS
    return True

domain_microsite = None
host_microsite = None
def pb_domain_url():
    if host_microsite:
        url = host_microsite
    elif domain_microsite and microsites_poster_different_look(domain_microsite):
        url = domain_microsite + '.'
        url += mysociety.config.get('WEB_DOMAIN')
    else:
        url = web_host + '.'
        url += mysociety.config.get('WEB_DOMAIN')
    return url

############################################################################
# Flyers using PyRTF for RTF generation

def rtf_repr(s):
    s = repr(s)
    if s[0:2] == "u'":
        s = s[2:-1]
    else:
        s = s[1:-1]
    for i in re.findall('\u([0-9a-f]{4})', s):
        dec = int(i, 16)
        s = re.sub('\\\u%s' % i, '\u%s?' % dec, s) # I don't quite understand why so many slashes, but it works
    for i in re.findall(r'\\x([0-9a-f]{2})', s):
        dec = int(i, 16)
	s = re.sub(r'\\x%s' % i, '\u%s?' % dec, s)
    return s

def flyerRTF(c, x1, y1, x2, y2, size, papersize, **keywords):
    w = x2 - x1
    h = y2 - y1

    h_purple = 0.15*h

    # Scale font sizes - with minimum for extreme cases
    small_writing = size * 12
    if small_writing < 4:
        small_writing = 4

    # Set up styles
    # leading generally small * 1.2, 0.9 on detail and smallprint, though, and 0 on footer
#    if iso_lang == 'eo_XX' or iso_lang == 'uk_UA' or iso_lang == 'ru_RU':
#        heading_font = ss.Fonts.TrebuchetMS
#        main_font = ss.Fonts.Georgia
#    else:
    heading_font = ss.Fonts.Transport
    main_font = ss.Fonts.Rockwell
    text_style = PyRTF.TextStyle( PyRTF.TextPS(font=heading_font, size=int(small_writing) ) )
    p_head = PyRTF.ParagraphStyle('header', text_style.Copy(), PyRTF.ParagraphPS(alignment=1, space_before = 0, space_after = 0) )
    ss.ParagraphStyles.append(p_head)
    text_style.TextPropertySet.SetSize(int(h_purple*4/5))
    p_footer = PyRTF.ParagraphStyle('footer', text_style.Copy(), PyRTF.ParagraphPS(alignment=PyRTF.ParagraphPS.RIGHT, space_before = 0, space_after = 0) )
    ss.ParagraphStyles.append(p_footer)
    text_style.TextPropertySet.SetFont(main_font)
    text_style.TextPropertySet.SetSize(int(small_writing))
    p_normal = PyRTF.ParagraphStyle('normal', text_style.Copy(), PyRTF.ParagraphPS(alignment = 1, space_before = 0, space_after = int(size*200)) )
    ss.ParagraphStyles.append(p_normal)
    p_nospaceafter = PyRTF.ParagraphStyle('nospaceafter', text_style.Copy(), PyRTF.ParagraphPS(alignment = 1, space_before = 0, space_after = 10) )
    ss.ParagraphStyles.append(p_nospaceafter)
    text_style.TextPropertySet.SetSize(int(small_writing*0.75))
    p_detail = PyRTF.ParagraphStyle('detail', text_style.Copy(), PyRTF.ParagraphPS(alignment = 1, space_before=0, space_after=int(size*100)) )
    ss.ParagraphStyles.append(p_detail)
    p_smallprint = PyRTF.ParagraphStyle('smallprint', text_style.Copy(), PyRTF.ParagraphPS(alignment=1, space_before=10, space_after=0) )
    ss.ParagraphStyles.append(p_smallprint)

    webdomain_text = PyRTF.TEXT('%s/%s' % (pb_domain_url(), ref), size=int(small_writing+6), bold=True, colour=ss.Colours.pb)

    # Draw text
    identity = ''
    if pledge['identity']:
        identity = ', ' + pledge['identity']
    story = PyRTF.Section(paper=papersizesRTF[papersize])
    story.Footer.append(PyRTF.Paragraph(ss.ParagraphStyles.footer, "PledgeBank.com"))
    story.extend([
        PyRTF.Paragraph(
                ss.ParagraphStyles.header, 
                PyRTF.TEXT(rtf_repr(_('I')),colour=ss.Colours.pb),
                rtf_repr(_(' will %s ') % pledge['title'].decode('utf-8')),
                PyRTF.B(rtf_repr(_('but only if'))), ' ', 
                PyRTF.TEXT(format_integer(pledge['target']), colour=ss.Colours.pb),
                rtf_repr(_(''' %s will %s.''') % (pledge['type'].decode('utf-8'), pledge['signup'].decode('utf-8')))
            ),
        PyRTF.Paragraph(ss.ParagraphStyles.header, PyRTF.ParagraphPS(alignment=2), '\u8212- ', PyRTF.TEXT(rtf_repr('%s%s' % (pledge['name'].decode('utf-8'), identity.decode('utf-8'))), colour=ss.Colours.pb)),
        PyRTF.Paragraph(ss.ParagraphStyles.detail, ''),
    ])

    if 'detail' in keywords and keywords['detail'] and pledge['detail']:
        d = pledge['detail'].split("\r?\n\r?\n")
        story.append(PyRTF.Paragraph(ss.ParagraphStyles.detail, PyRTF.B(rtf_repr(_('More details:'))), ' ', rtf_repr(d[0])))
        if len(d)>0:
            story.extend(
                map(lambda text: PyRTF.Paragraph(ss.ParagraphStyles.detail, rtf_repr(text)), d[1:])
            )

    if not has_sms(pledge):
        # TRANS: This is on a poster, it becomes "Pledge at http://www.pledgebank.com/PLEDGEREF", so "Pledge" in this context is a verb, as below. (Matthew Somerville, http://www.mysociety.org/pipermail/mysociety-i18n/2005-November/000104.html)
        text_para = PyRTF.Paragraph(ss.ParagraphStyles.normal, rtf_repr(_('Pledge at ')), webdomain_text)
        if pledge['pin']:
            text_para.append(rtf_repr(_(' pin ')), PyRTF.TEXT('%s' % userpin, colour=ss.Colours.pb, size=int(small_writing+4)))
        sms_smallprint = ""
    else:
        text_para = PyRTF.Paragraph(ss.ParagraphStyles.normal, 
                    # TRANS: Text is an instruction (verb in the imperative)
                    PyRTF.TEXT(rtf_repr(_('Text')), size=int(small_writing+4)), 
                    ' ', 
                    PyRTF.TEXT(rtf_repr('%s %s') % (mysociety.config.get('PB_SMS_PREFIX'), ref), bold=True, colour=ss.Colours.pb, size=int(small_writing+16)),
            # TRANS: This appears on posters/flyers in the sentence: "Text 'pledge REFERENCE' *to* 60022". (Tim Morley, 2005-11-30)
                    ' ', rtf_repr(_('to')), ' ', 
                    PyRTF.TEXT('%s' % sms_number, colour=ss.Colours.pb, bold=True),
            # TRANS: This is part of "text xxxxxxxxxx to 60022 (UK only) or pledge at http://xxxxxxxxx". Is that right? (Tim Morley, 2005-11-23)
                    rtf_repr(_(' (%s only) or pledge at ') % sms_countries_description), webdomain_text)
        sms_smallprint = _(boilerplate_sms_smallprint) # translate now lang set

    story.extend([ text_para, 
        PyRTF.Paragraph(ss.ParagraphStyles.normal, rtf_repr(_('This pledge closes on ')), PyRTF.TEXT('%s' % rtf_repr(pledge['date'].decode('utf-8')), colour=ss.Colours.pb), rtf_repr(_('. Thanks!'))),
        PyRTF.Paragraph(ss.ParagraphStyles.normal, rtf_repr(_(u'Remember, you only have to act if %d other people sign up \u2013 that\u2019s what PledgeBank is all about.')) % pledge['target'])
#        PyRTF.Paragraph(ss.ParagraphStyles.smallprint, PyRTF.B('Small print:'),
#            ' %s Questions? 08453 330 160 or team@pledgebank.com.' % sms_smallprint)
    ])

    c.Sections.append(story)


############################################################################
# Flyers using reportlab.platypus for word wrapping

# Prints one copy of the flier at given coordinates, and font sizes.
# Returns False if it didn't all fit, or True if it did.
def flyer(c, x1, y1, x2, y2, size, **keywords):
#    size = 0.283
    w = x2 - x1
    h = y2 - y1
    h_purple = 0.1*h
    html_colour = microsites_poster_html_highlight_colour()

    c.setFillColorRGB(*microsites_poster_box_fill_colour())
    # Draw dotted line round the outside
    c.setDash(3,3)
    # Don't use c.rect, so that the lines are always drawn the same direction,
    # and overlapping dashed lines don't cancel each other out
    c.line(x1, y1, x1, y2)
    c.line(x2, y1, x2, y2)
    c.line(x1, y1, x2, y1)
    c.line(x1, y2, x2, y2)
    c.setDash()
#    c.setLineWidth(0.25)
#    for i in range(40):
#        c.line(x1,y1+i,x2,y1+i)

    # Scale font sizes - with minimum for extreme cases
    large_writing = size * 20
    small_writing = size * 12
    if small_writing < 4:
        small_writing = 4
    if large_writing < 4:
        large_writing = 4

    # Set up styles
    if iso_lang == 'eo_XX' or iso_lang == 'uk_UA' or iso_lang == 'ru_RU':
        heading_font = 'Trebuchet MS'
        main_font = 'Georgia'
    else:
        heading_font = 'Transport'
        main_font = 'Rockwell'

    p_head = ParagraphStyle('normal', alignment = TA_LEFT, spaceBefore = 0, spaceAfter = 0, 
        fontSize = small_writing, leading = small_writing*1.2, fontName = heading_font)
    p_normal = ParagraphStyle('normal', alignment = TA_LEFT, spaceBefore = 0, spaceAfter = size*20, 
        fontSize = small_writing, leading = small_writing*1.2, fontName = main_font)
    p_detail = ParagraphStyle('detail', alignment = TA_LEFT, spaceBefore = 0, spaceAfter = size*10, 
        fontSize = small_writing * 0.75, leading = small_writing * 0.9, fontName = main_font)
    p_nospaceafter = ParagraphStyle('normal', alignment = TA_LEFT, spaceBefore = 0, spaceAfter = 1, 
        fontSize = small_writing, leading = small_writing*1.2, fontName = main_font)
    p_footer = ParagraphStyle('normal', alignment = TA_RIGHT, spaceBefore = 0, spaceAfter = 0,
        fontSize = h_purple*4/5, leading = 0, fontName = heading_font)
    p_smallprint = ParagraphStyle('normal', alignment = TA_LEFT, spaceBefore = 1, spaceAfter = 0,
        fontSize = small_writing * 0.75, leading = small_writing * 0.9, fontName = main_font)
    if (w<h):
        ticksize = w*1.2
    else:
        ticksize = h
    p_tick = ParagraphStyle('normal', fontName='ZapfDingbats', alignment = TA_RIGHT, fontSize = ticksize)

    # Big tick
    story = [ Paragraph('<font color="#f4f1f8">3</font>', p_tick) ]
    if (w<h):
        f = Frame(x1, y1, w, h, showBoundary = 0)
    else:
        f = Frame(x1, y1+h/6, w, h, showBoundary = 0)
    f.addFromList(story, c)
    
    # PledgeBank logo
    microsites_poster_logo(c, x1, y1, w, h_purple, p_footer)

    # Main body text
    dots_body_gap = w/30

    # Check web domain fits, as that is long word that doesn't fit on
    # (and platypus/reportlab doesn't raise an error in that case)
    webdomain_text = '''<font size="+3" color="%s"><b>%s/%s</b></font>''' % (html_colour, pb_domain_url(), ref)
    webdomain_para = Paragraph(webdomain_text, p_normal)
    webdomain_allowed_width = w - dots_body_gap * 2
    webdomain_width = webdomain_para.wrap(webdomain_allowed_width, h)[0]
    if webdomain_width > webdomain_allowed_width:
        return False
    # print >>sys.stderr, "webdomain_width ", webdomain_width, w

    # Draw text
    identity = ''
    if pledge['identity']:
        identity = ', ' + pledge['identity']
    story = [
        Paragraph(_(
                    '''<font color="%s">I</font> will %s <b>but only if</b> <font color="%s">%s</font> %s will %s. '''
                ).encode('utf-8') % (
                html_colour, pledge['title'],
                html_colour, format_integer(pledge['target']), 
                pledge['type'], pledge['signup']
            ), p_head),
        Paragraph(u'''<para align="right">\u2014 
            <font color="%s">%s%s</font></para>'''.encode('utf-8') 
            % (html_colour, pledge['name'], identity), p_head),
        Paragraph('', p_detail),
    ]
    
    if 'detail' in keywords and keywords['detail'] and pledge['detail']:
        story.extend(
            map(lambda text: Paragraph(text, p_detail), 
                (_('<b>More details:</b> %s').encode('utf-8') % pledge['detail']).split("\r?\n\r?\n"))
            )

    if not has_sms(pledge):
        pledge_at_text = _("Pledge at ").encode('utf-8')
        if pledge['pin']:
            pin_text = _(' pin ').encode('utf-8') + '''<font color="%s" size="+2">%s</font>''' % (html_colour, userpin)
        else:
            pin_text = ''
        sms_to_text = ""
        sms_smallprint = ""
    else:
        pledge_at_text = _("pledge at ").encode('utf-8')
        pin_text = ""
        sms_to_text = _("""<font size="+2">Text</font> <font size="+8" color="%s">
            <b>%s %s</b></font> to <font color="%s"><b>%s</b></font> 
            (%s only) or """).encode('utf-8') % (html_colour, mysociety.config.get('PB_SMS_PREFIX'), ref, html_colour, sms_number, sms_countries_description.encode('utf-8'))
        sms_smallprint = _(boilerplate_sms_smallprint) # translate now lang set

    story.extend([
        Paragraph('''%s%s%s%s''' % 
            (sms_to_text, pledge_at_text, webdomain_text, pin_text), p_normal),
        Paragraph(_('''
            This pledge closes on <font color="%s">%s</font>. Thanks!
            ''').encode('utf-8') % (html_colour, pledge['date']), p_normal),
        Paragraph(_(u'Remember, you only have to act if %d other people sign up \u2013 that\u2019s what PledgeBank is all about.').encode('utf-8') % pledge['target'], p_normal)
#        Paragraph('''
#            <b>Small print:</b> %s Questions?
#            08453 330 160 or team@pledgebank.com.
#            ''' % sms_smallprint, p_smallprint)
    ])

    f = Frame(x1, y1, w, h, showBoundary = 0, 
        leftPadding = dots_body_gap, rightPadding = dots_body_gap, 
        topPadding = dots_body_gap/2, bottomPadding = h_purple + dots_body_gap/2
        )
    f.addFromList(story, c)

    # If it didn't fit, say so
    if len(story) > 0:
        return False
    return True

def flyers(number, papersize='A4', **keywords):
    # Number of flyers to fit on the page
    if number == 1:
        flyers_across = 1
        flyers_down = 1
    elif number == 4:
        flyers_across = 2
        flyers_down = 2
    elif number == 8:
        flyers_across = 2
        flyers_down = 4
    elif number == 16:
        flyers_across = 4
        flyers_down = 4
    else:
        raise Exception("Invalid number %d for flyers" % number)

    # Just A4 for now
    (page_width, page_height) = papersizes[papersize]
    if papersize == 'A4' or papersize == 'letter':
        # Tweaked to make sure dotted lines are displayed on all edges
        margin_top = 1 * cm
        margin_left = 1 * cm
        margin_bottom = 1 * cm
        margin_right = 1 * cm
    elif papersize == 'A7':
        margin_top = 0
        margin_left = 0
        margin_bottom = 0
        margin_right = 0

    # Calculate size of fliers
    flyer_width = (page_width - margin_left - margin_right) / flyers_across 
    flyer_height = (page_height - margin_top - margin_bottom) / flyers_down

    # Try different font sizes on a hidden canvas to get the largest
    dummyc = canvas.Canvas(None)
    size = 3.0
    while True:
        ok = flyer(dummyc, 0, 0, flyer_width, flyer_height, size, **keywords);
        if ok:
            break
        size = size * 19 / 20
        if size * 50 < 10:
            raise Exception("Pledge text wouldn't fit on page")

    # Draw fliers
    c.setStrokeColorRGB(0,0,0)
    c.setFillColorRGB(0,0,0)
    for along in range(0, flyers_across):
        for down in range(0, flyers_down):
            flyer(c, 
                along * flyer_width + margin_left, down * flyer_height + margin_bottom, 
                (along + 1) * flyer_width + margin_left, (down + 1) * flyer_height + margin_bottom,
                size, **keywords)
    return size

############################################################################

# Main loop
while fcgi.isFCGI():
    req = fcgi.Accept()
    fs = req.getFieldStorage()
    # req.err.write("got request in poster.cgi, path: %s\n" % req.env.get('PATH_INFO'))

    try:
        if req.env.get('PATH_INFO'):
            incgi = True

            path_info = req.env.get('PATH_INFO').split('_')
            if len(path_info)>0:
                ref = path_info[0][1:]

            if len(path_info)>1 and path_info[1]:
                size = path_info[1]
            else:
                size = 'A4'
                
            if len(path_info)>2 and path_info[2]:
                type = path_info[2]
                (type, format) = type.split('.')
            else:
                type = 'flyers4'
                format = 'pdf'
        else:
            incgi = False

            from optparse import OptionParser
            parser = OptionParser()
            parser.set_usage("""
        ./poster.cgi REF [OPTIONS]

    Generates a poster or flyer for PledgeBank in PDF and other formats.  Designed
    to be run as a FastCGI script, but can be run on the command line for testing.
    REF is the PledgeBank reference of the poster to be printed.  The output
    is sent to standard output if run as a CGI.

    Files are cached in the directory PB_PDF_CACHE specified in conf/general.""")
            parser.add_option("--size", dest="size", default="A4",
                help=", ".join(sizes));
            parser.add_option("--type", dest="type", default="flyers4",
                help=", ".join(types));
            parser.add_option("--format", dest="format", default="pdf",
                help=", ".join(formats));
            (options, args) = parser.parse_args()
            if len(args) <> 1:
                parser.print_help()
                req.err.write("specify PledgeBank ref\n")
                continue
            ref = args[0] 
            size = options.size
            type = options.type 
            format = options.format

        if not size in sizes:
            raise Exception, "Unknown size '%s'" % size
        if not type in types:
            raise Exception, "Unknown type '%s'" % type
        if not format in formats:
            raise Exception, "Unknown format '%s'" % format

        # Get information from database
        q = db.cursor()
        pledge = {}
        q.execute('SELECT title, date, name, type, target, target_type, signup, pin, identity, detail, country, lang, microsite FROM pledges LEFT JOIN location ON location.id = pledges.location_id WHERE lower(ref) = %s', ref.lower())
        row = q.fetchone()
        if not row:
            raise Exception, "Unknown ref '%s'" % ref
        (pledge['title'],date,pledge['name'],pledge['type'],pledge['target'],pledge['target_type'],pledge['signup'],pledge['pin'], pledge['identity'], pledge['detail'], pledge['country'], pledge['lang'], pledge['microsite']) = row
        q.close()

        # Work out if we're on a microsite
        web_host = mysociety.config.get('WEB_HOST')
        http_host = req.env.get('HTTP_HOST')
        domain_microsite = None
        host_microsite = None
        if not http_host:
            http_host = ''
        if http_host in microsites_from_extra_domains:
            microsite = microsites_from_extra_domains[http_host]
            host_microsite = http_host
        else:
            microsite = ''
            if web_host == 'www':
                g = re.match('([^.]+)\.', http_host)
                if g:
                    microsite = g.group(1)
            else:
                g = re.match('([^.]+)\.(?:..(?:-..)?\.)?'+web_host+'\.', http_host)
                if g:
                    microsite = g.group(1)
            domain_microsite = microsite
        if not microsites_poster_different_look(microsite):
            microsite = ''
        # ... override with pledge microsite if we didn't find one that looks
        # different from URL
        if not microsite and pledge['microsite']:
            microsite = pledge['microsite']
            if not microsites_poster_different_look(microsite):
                microsite = ''

        # Set language to that of the pledge
        iso_lang = 'en_GB'
        available_langs = mysociety.config.get('PB_LANGUAGES').split("|");
        for available_lang in available_langs:
            (loop_pb_code, loop_name, loop_iso) = available_lang.split(",")
            if pledge['lang'] == loop_pb_code:
                iso_lang = loop_iso
        domain = 'PledgeBank'
        if iso_lang == 'en_GB':
            _ = lambda x: x
        else:
            translator = gettext.translation(domain, '../../locale', [iso_lang + '.UTF-8'])
            translator.install(unicode = 1)
            _ = translator.ugettext
        locale.setlocale(locale.LC_ALL, iso_lang + '.UTF-8')
        #raise Exception, "Language '%s' %s" % (iso_lang, _("Start your own pledge"))

        sms_countries_description = _('UK')

        # Set date
        day = date.day
        if iso_lang == 'en_GB':
            pledge['date'] = "%d%s %s" % (day, ordinal(day), date.strftime("%B %Y"))
        elif iso_lang == 'eo_XX':
            pledge['date'] = date.strftime("la %e-a de %B %Y")
        else:
            pledge['date'] = date.strftime("%e %B %Y")
        if pledge['signup'].decode('utf-8') == u"do the same":
            pledge['signup'] = "too"
        sms_number = mysociety.config.get('PB_SMS_DISPLAY_NUMBER')

        # Check pin
        #req.err.write("pin %s\n" % pledge['pin'])
        if pledge['pin']:
            if 'pin' not in fs:
                raise Exception, "Correct PIN needed for '%s' pledge" % ref
            userpin = fs['pin'].value
            sha_calc = sha.new()
            sha_calc.update(userpin)
            crypt_userpin = sha_calc.hexdigest()
            #req.err.write("userpin %s\n" % crypt_userpin)
            if crypt_userpin != pledge['pin']:
                raise Exception, "Correct PIN needed for '%s' pledge" % ref

        # Header
        if incgi:
            if format == 'pdf':
                req.out.write("Content-Type: application/pdf\r\n\r\n")
            elif format == 'png':
                req.out.write("Content-Type: image/png\r\n\r\n")
            elif format == 'gif':
                req.out.write("Content-Type: image/gif\r\n\r\n")
            elif format == 'rtf':
                req.out.write("Content-Type: text/rtf\r\n\r\n")
            else:
                req.out.write("Content-Type: text/plain\r\n\r\n")

        def file_to_stdout(filename):
            f = file(filename, 'rb')
            content = f.read()
            f.close()
            req.out.write(content)

        outdir = mysociety.config.get("PB_PDF_CACHE")
        if microsite:
            outpdf = "%s_%s_%s_%s.pdf" % (microsite, ref, size, type)
        else:
            outpdf = "%s_%s_%s.pdf" % (ref, size, type)
        if microsite:
            outfile = "%s_%s_%s_%s.%s" % (microsite, ref, size, type, format)
        else:
            outfile = "%s_%s_%s.%s" % (ref, size, type, format)

        # Cache file checking
        # XXX TODO - sanity check size and date, or we risk caching a failure here!
        if os.path.exists(outdir + '/' + outfile) and incgi:
            # Use cache file
            file_to_stdout(outdir + '/' + outfile)
        elif format == 'rtf':
            # Generate PDF file to get correct font size
            (canvasfileh, canvasfilename) = tempfile.mkstemp(dir=outdir,prefix='tmp')
            c = canvas.Canvas(canvasfilename, pagesize=papersizes[size])
            neededsize = flyers(1, size, detail = True)
            neededsize = neededsize * 1.9 # The MAGIC CONSTANT

            ss = PyRTF.StyleSheet()
            ss.Colours.append(microsites_poster_rtf_colour()) 
#            if iso_lang == 'eo_XX' or iso_lang == 'uk_UA' or iso_lang == 'ru_RU':
#                heading_font = 'Trebuchet MS'
#                main_font = 'Georgia'
#            else:
            heading_font = 'Transport'
            main_font = 'Rockwell'
            ss.Fonts.append(PyRTF.Font(main_font, 'roman', 0))
            ss.Fonts.append(PyRTF.Font(heading_font, 'swiss', 0))
            ps = PyRTF.ParagraphStyle('Body', PyRTF.TextStyle(PyRTF.TextPS(ss.Fonts.Arial, 22)), PyRTF.ParagraphPS(space_before=60, space_after=60))
            ss.ParagraphStyles.append(ps)

            doc = PyRTF.Document(style_sheet=ss, default_language=2057, view_kind=1, view_zoom_kind=0, view_scale=100)  

            (page_width, page_height) = papersizes[size]
            if size == 'A4' or size == 'letter':
                margin_top = 1 * cm
                margin_left = 1 * cm
                margin_bottom = 1 * cm
                margin_right = 1 * cm

            flyer_width = (page_width - margin_left - margin_right)
            flyer_height = (page_height - margin_top - margin_bottom)

            flyerRTF(doc, margin_left, margin_bottom, 
                flyer_width + margin_left, flyer_height + margin_bottom,
                neededsize, size, detail=True)

            DR = PyRTF.Renderer()
            DR.Write(doc, file(canvasfilename, 'w'))
            os.rename(canvasfilename, outdir + '/' + outfile)
            os.chmod(outdir + '/' + outfile, 0644)
            if (incgi):
                file_to_stdout(outdir + '/' + outfile)
        else:
            # Generate PDF file
            (canvasfileh, canvasfilename) = tempfile.mkstemp(dir=outdir,prefix='tmp')
            c = canvas.Canvas(canvasfilename, pagesize=papersizes[size])
            try:
                if type == "flyers16":
                    flyers(16)
                elif type == "flyers8":
                    flyers(8, size)
                elif type == "flyers4":
                    flyers(4)
                elif type == "flyers1" and (size=='A4' or size=='letter'):
                    flyers(1, size, detail = True)
                elif type == "flyers1":
                    flyers(1, size)
                else:
                    raise Exception, "Unknown type '%s'" % type
            except Exception, e:
                req.err.write(string.join(e.args,' '))
                c.setStrokeColorRGB(0,0,0)
                c.setFont("Helvetica", 15)
                c.drawCentredString(10.5*cm, 25*cm, str(e))
            c.save()
            os.rename(canvasfilename, outdir + '/' + outpdf)
            os.chmod(outdir + '/' + outpdf, 0644)
                
            # Generate any other file type
            if format != 'pdf':
                # Call out to "convert" from ImageMagick
                cmd = "gs-afpl -q -dNOPAUSE -dBATCH -sDEVICE=ppmraw -sOutputFile=- -r288 " + outdir + '/' + outpdf + " | pnmscale 0.25 | ppmquant 256 | pnmtopng > " + outdir + '/' + outfile
                child = popen2.Popen3(cmd, True) # capture stderr
                child.tochild.close()
                # no need for stdout in log file, so ignore this
                # req.err.write(child.fromchild.read()) 
                # fetch standard error lines
                errorlines = child.childerr.readlines()
                # filter out progress messages from errors
                errorlines = filter(lambda x: 'making histogram' not in x, errorlines)
                errorlines = filter(lambda x: 'colors found' not in x, errorlines)
                errorlines = filter(lambda x: 'colors...' not in x, errorlines)
                # write out anything left to apache log
                req.err.writelines(errorlines)
                status = child.wait()
                if os.WIFSIGNALED(status):
                    raise Exception, "%s: killed by signal %d" % (cmd, os.WTERMSIG(status))
                elif os.WEXITSTATUS(status) != 0:
                    raise Exception, "%s: exited with failure status %d" % (cmd, os.WEXITSTATUS(status))
            if (incgi):
                file_to_stdout(outdir + '/' + outfile)


    except Exception, e:
        req.out.write("Content-Type: text/plain\r\n\r\n")
        req.out.write(_("Sorry, we weren't able to make your poster.\n\n").encode('utf-8'))
        req.out.write(str(e) + "\n")

    req.Finish()


