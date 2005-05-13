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
# $Id: poster.cgi,v 1.41 2005-05-13 18:31:20 matthew Exp $
#

import os
import sys
import popen2
from time import time
from pyPgSQL import PgSQL
import fcgi
import tempfile
import string
import sha

from reportlab.pdfgen import canvas
from reportlab.lib.units import cm
from reportlab.lib.pagesizes import A4 # only goes down to A6
papersizes = {}
papersizes['A4'] = A4
papersizes['A7'] = (A4[0] * 0.5, A4[1] * 0.25)

from reportlab.lib.styles import ParagraphStyle

from reportlab.lib.styles import getSampleStyleSheet
from reportlab.platypus import Paragraph, Frame
from reportlab.lib.enums import TA_LEFT, TA_CENTER, TA_RIGHT

# This is a special function to be able to use bold and italic in TTFs
# See May 2004 Reportlab Users mailing list
def myRegisterFont(font):
    "Registers a font, including setting up info for accelerated stringWidth"
    fontName = font.fontName
    _fonts[fontName] = font
    if font._multiByte:
        ttname = string.lower(font.fontName)
    else:
        if _stringWidth:
            _rl_accel.setFontInfo(string.lower(fontName),
                    _dummyEncoding,
                    font.face.ascent,
                    font.face.descent,
                    font.widths)

sys.path.append("../../pylib")
import mysociety.config
mysociety.config.set_file("../conf/general")

import PyRTF

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

db = PgSQL.connect('::' + mysociety.config.get('PB_DB_NAME') + ':' + mysociety.config.get('PB_DB_USER') + ':' + mysociety.config.get('PB_DB_PASS'))

types = ["cards", "tearoff", "flyers16", "flyers4", "flyers1", "flyers8"]
sizes = ["A4", "A7"]
formats = ["pdf", "png", "gif", "rtf"]

def ordinal(day):
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

def draw_short_pledge(x, y):
    text = "\"I will %s\"" % pledge['title']
    size = 14*10/(c.stringWidth(text, "Helvetica", 14)/cm)
    c.setFont("Helvetica", size)
    c.drawCentredString(x, (y-1)*cm, text)

    text = "Deadline: %s" % pledge['date']
    size = 14*10/(c.stringWidth(text, "Helvetica", 14)/cm)
    c.setFont("Helvetica", size)
    c.drawCentredString(x, (y-2)*cm, text)

    text = "www.pledgebank.com/%s" % ref
    size = 14*10/(c.stringWidth(text, "Helvetica", 14)/cm)
    c.setFont("Helvetica", size)
    c.drawCentredString(x, (y-3)*cm, text)

############################################################################
# Tiled cards on a sheet.

def cards():
    c.setStrokeColorRGB(0,0,0)
    c.setFillColorRGB(0,0,0)
    c.setDash(3,3)
    c.line(10.5*cm, 0, 10.5*cm, 30*cm)
    for y in (5,10,15,20,25,30):
        c.line(0,y*cm,21*cm,y*cm)
        c.setFont("ZapfDingbats",24)
        c.drawString(1*cm, y*cm-8, '"')
        draw_short_pledge(10.5*cm/2, y)
        draw_short_pledge(10.5*cm*3/2, y)
    c.rotate(-90)
    c.setFont("ZapfDingbats",24)
    c.drawString(-29*cm, 10.5*cm-8, '"')
    c.showPage()

############################################################################
# Little tear off strips at the bottom, like phone numbers on adverts on 
# student noticeboards.

def tearoff():
    x = 10.5*cm
    y = 20
    text = "\"I will %s\"" % pledge['title']
    size = 32/(c.stringWidth(text, "Helvetica", 32)/cm)*19
    c.setFont("Helvetica", size)
    c.drawCentredString(x, (y-1)*cm, text)
    c.setFont("Helvetica", size*0.8)
    text = "Deadline: %s" % pledge['date']
    c.drawCentredString(x, (y-3)*cm, text)
    text = "www.pledgebank.com/%s" % ref
    c.drawCentredString(x, (y-5)*cm, text)
    c.setDash(3,3)
    stripheight = 8*cm
    c.line(0, stripheight, 21*cm, stripheight)
    c.rotate(90)
    for x in (3, 6, 9, 12, 15, 18, 21):
        c.line(0, -x*cm, stripheight, -x*cm)
        c.setFont("ZapfDingbats", 24)
        c.drawString(1*cm, -x*cm-9, '"')
        c.setFont("Helvetica", 10)
        text = "\"%s\"" % pledge['title']
        c.drawCentredString(stripheight/2, (2.2-x)*cm, text)
        c.setFont("Helvetica", 9)
        text = "Deadline: %s" % pledge['date']
        c.drawCentredString(stripheight/2, (1.2-x)*cm, text)
        text = "www.pledgebank.com/%s" % ref
        c.drawCentredString(stripheight/2, (0.7-x)*cm, text)
    c.showPage()

############################################################################
# Flyers using PyRTF for RTF generation

def flyerRTF(c, x1, y1, x2, y2, size, **keywords):
    w = x2 - x1
    h = y2 - y1

    h_purple = 0.15*h

    # Scale font sizes - with minimum for extreme cases
    small_writing = size * 12
    if small_writing < 4:
        small_writing = 4

    # Set up styles
    # leading generally small * 1.2, 0.9 on detail and smallprint, though, and 0 on footer
    text_style = PyRTF.TextStyle( PyRTF.TextPS(font=ss.Fonts.Transport, size=int(small_writing) ) )
    p_head = PyRTF.ParagraphStyle('header', text_style.Copy(), PyRTF.ParagraphPS(alignment=1, space_before = 0, space_after = 0) )
    ss.ParagraphStyles.append(p_head)
    text_style.TextPropertySet.SetSize(int(h_purple*4/5))
    p_footer = PyRTF.ParagraphStyle('footer', text_style.Copy(), PyRTF.ParagraphPS(alignment=PyRTF.ParagraphPS.RIGHT, space_before = 0, space_after = 0) )
    ss.ParagraphStyles.append(p_footer)
    text_style.TextPropertySet.SetFont(ss.Fonts.Rockwell)
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

    webdomain_text = PyRTF.TEXT('%s/%s' % (mysociety.config.get('WEB_DOMAIN'), ref), size=int(small_writing+6), bold=True, colour=ss.Colours.pb)

    # Draw text
    identity = ''
    if pledge['identity']:
        identity = ', ' + pledge['identity']
    story = PyRTF.Section()
    story.Footer.append(PyRTF.Paragraph(ss.ParagraphStyles.footer, "PledgeBank.com"))
    story.extend([
        PyRTF.Paragraph(ss.ParagraphStyles.header, PyRTF.B('If'), ' ', PyRTF.TEXT('%s' % pledge['target'], colour=ss.Colours.pb),
        ''' %s will %s, then ''' % (pledge['type'], pledge['signup']), PyRTF.TEXT('I',colour=ss.Colours.pb),
        ' will %s.' % pledge['title']),
        PyRTF.Paragraph(ss.ParagraphStyles.header, PyRTF.ParagraphPS(alignment=2), '\x97 ', PyRTF.TEXT('%s%s' % (pledge['name'], identity), colour=ss.Colours.pb)),
        PyRTF.Paragraph(ss.ParagraphStyles.detail, ''),
    ])

    if 'detail' in keywords and keywords['detail'] and pledge['detail']:
        d = pledge['detail'].split("\n\n")
        story.append(PyRTF.Paragraph(ss.ParagraphStyles.detail, PyRTF.B('More details:'), ' ', d[0]))
        if len(d)>0:
            story.extend(
                map(lambda text: PyRTF.Paragraph(ss.ParagraphStyles.detail, text), d[1:])
            )

    text_para = PyRTF.Paragraph(ss.ParagraphStyles.normal, PyRTF.TEXT('Text', size=int(small_writing+4)),
            ' ', PyRTF.TEXT('pledge %s' % ref, bold=True, colour=ss.Colours.pb, size=int(small_writing+16)),
            ' to ', PyRTF.TEXT('%s' % sms_number, colour=ss.Colours.pb, bold=True),
            ' or pledge at ', webdomain_text)
    if pledge['password']:
        text_para.append(' password ', PyRTF.TEXT('%s' % userpassword, colour=ss.Colours.pb, size=int(small_writing+4)))

    story.extend([ text_para, 
        PyRTF.Paragraph(ss.ParagraphStyles.normal, 'This pledge closes on ', PyRTF.TEXT('%s' % pledge['date'], colour=ss.Colours.pb), '. Thanks!'),
        PyRTF.Paragraph(ss.ParagraphStyles.smallprint, PyRTF.B('Small print:'),
            ' SMS operated by charity UKCOD. Sign-up message costs your normal text rate. Further messages are free. Questions? 08453 330 160 or team@pledgebank.com.')
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

    # Draw purple bar
    c.setFillColorRGB(0.6, 0.45, 0.7)
    h_purple = 0.1*h
    c.rect(x1, y1, w, h_purple, fill=1, stroke=0)

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
    p_head = ParagraphStyle('normal', alignment = TA_LEFT, spaceBefore = 0, spaceAfter = 0, 
        fontSize = small_writing, leading = small_writing*1.2, fontName = 'Transport')
    p_normal = ParagraphStyle('normal', alignment = TA_LEFT, spaceBefore = 0, spaceAfter = size*20, 
        fontSize = small_writing, leading = small_writing*1.2, fontName = 'Rockwell')
    p_detail = ParagraphStyle('detail', alignment = TA_LEFT, spaceBefore = 0, spaceAfter = size*10, 
        fontSize = small_writing * 0.75, leading = small_writing * 0.9, fontName = 'Rockwell')
    p_nospaceafter = ParagraphStyle('normal', alignment = TA_LEFT, spaceBefore = 0, spaceAfter = 1, 
        fontSize = small_writing, leading = small_writing*1.2, fontName = 'Rockwell')
    p_footer = ParagraphStyle('normal', alignment = TA_RIGHT, spaceBefore = 0, spaceAfter = 0,
        fontSize = h_purple*4/5, leading = 0, fontName = 'Transport')
    p_smallprint = ParagraphStyle('normal', alignment = TA_LEFT, spaceBefore = 1, spaceAfter = 0,
        fontSize = small_writing * 0.75, leading = small_writing * 0.9, fontName = 'Rockwell')
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
    story = [
        Paragraph('<font color="#ffffff">Pledge</font>Bank.com', p_footer)
    ]
    dots_body_gap = 0
    f = Frame(x1, y1+0.1*h_purple, w, h_purple, showBoundary = 0, id='Footer',
        topPadding = dots_body_gap, bottomPadding = dots_body_gap
        )
    f.addFromList(story, c)

    # Main body text
    dots_body_gap = w/30

    # Check web domain fits, as that is long word that doesn't fit on
    # (and platypus/reportlab doesn't raise an error in that case)
    webdomain_text = '''<font size="+3" color="#522994"><b>%s/%s</b></font>''' % (mysociety.config.get('WEB_DOMAIN'), ref)
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
        Paragraph('''
            <b>If</b> <font color="#522994">%s</font> %s will %s, 
            then <font color="#522994">I</font> will %s.
            ''' % (
                pledge['target'], pledge['type'], pledge['signup'],
                pledge['title']
            ), p_head),
        Paragraph(u'''<para align="right">\u2014 
            <font color="#522994">%s%s</font></para>'''.encode('utf-8') 
            % (pledge['name'], identity), p_head),
        Paragraph('', p_detail),
    ]
    
    if 'detail' in keywords and keywords['detail'] and pledge['detail']:
        story.extend(
            map(lambda text: Paragraph(text, p_detail), 
                ('<b>More details:</b> %s' % pledge['detail']).split("\n\n"))
            )

    password_text = ""
    if pledge['password']:
        password_text = ''' password <font color="#522994" size="+2">%s</font>''' % userpassword

    story.extend([
        Paragraph('''<font size="+2">Text</font> <font size="+8" color="#522994">
            <b>pledge %s</b></font> to <font color="#522994"><b>%s</b></font> 
            or pledge at %s%s''' % 
            (ref, sms_number, webdomain_text, password_text), p_normal),
        Paragraph('''
            This pledge closes on <font color="#522994">%s</font>. Thanks!
            ''' % pledge['date'], p_normal),
        Paragraph('''
            <b>Small print:</b> SMS operated by charity UKCOD. Sign-up message
            costs your normal text rate. Further messages are free. Questions?
            08453 330 160 or team@pledgebank.com.
            ''', p_smallprint)
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
    if papersize == 'A4':
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
                type = 'cards'
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
                req.err.write("specify Pledgebank ref\n")
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
        q.execute('SELECT title, date, name, type, target, signup, password, identity, detail FROM pledges WHERE ref ILIKE %s', ref)
        (pledge['title'],date,pledge['name'],pledge['type'],pledge['target'],pledge['signup'],pledge['password'], pledge['identity'], pledge['detail']) = q.fetchone()
        q.close()
        day = date.day
        pledge['date'] = "%d%s %s" % (day, ordinal(day), date.strftime("%B %Y"))
        if pledge['signup'] == "do the same":
            pledge['signup'] = "too"
        sms_number = mysociety.config.get('PB_SMS_DISPLAY_NUMBER')

        # Check password
        #req.err.write("password %s\n" % pledge['password'])
        if pledge['password']:
            userpassword = fs['pw'].value
            sha_calc = sha.new()
            sha_calc.update(userpassword)
            crypt_userpassword = sha_calc.hexdigest()
            #req.err.write("userpassword %s\n" % crypt_userpassword)
            if crypt_userpassword != pledge['password']:
                raise Exception, "Correct password needed for '%s' pledge" % ref

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
        outpdf = "%s_%s_%s.pdf" % (ref, size, type)
        outfile = "%s_%s_%s.%s" % (ref, size, type, format)

        # Cache file checking
        if os.path.exists(outdir + '/' + outfile) and incgi:
            # Use cache file
            file_to_stdout(outdir + '/' + outfile)
        elif format == 'rtf':
            # Generate PDF file to get correct font size
            (canvasfileh, canvasfilename) = tempfile.mkstemp(dir=outdir,prefix='tmp')
            c = canvas.Canvas(canvasfilename, pagesize=papersizes[size])
            neededsize = flyers(1, size, detail = True)
            neededsize = neededsize * 1.9 # The MAGIC CONSTANT

            (canvasfileh, canvasfilename) = tempfile.mkstemp(dir=outdir, prefix='tmp')
            ss = PyRTF.StyleSheet()
            ss.Colours.append(PyRTF.Colour('pb', 82, 41, 9*16+4)) # 522994
            ss.Fonts.append(PyRTF.Font('Rockwell', 'roman', 0))
            ss.Fonts.append(PyRTF.Font('Transport', 'swiss', 0))
            ps = PyRTF.ParagraphStyle('Body', PyRTF.TextStyle(PyRTF.TextPS(ss.Fonts.Arial, 22)), PyRTF.ParagraphPS(space_before=60, space_after=60))
            ss.ParagraphStyles.append(ps)

            doc = PyRTF.Document(style_sheet=ss, default_language=2057, view_kind=1, view_zoom_kind=0, view_scale=100)  

            (page_width, page_height) = papersizes[size]
            if size == 'A4':
                margin_top = 1 * cm
                margin_left = 1 * cm
                margin_bottom = 1 * cm
                margin_right = 1 * cm

            flyer_width = (page_width - margin_left - margin_right)
            flyer_height = (page_height - margin_top - margin_bottom)

            flyerRTF(doc, margin_left, margin_bottom, 
                flyer_width + margin_left, flyer_height + margin_bottom,
                neededsize, detail=True)

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
                if type == "cards":
                    cards()
                elif type == "tearoff":
                    tearoff()
                elif type == "flyers16":
                    flyers(16)
                elif type == "flyers8":
                    flyers(8)
                elif type == "flyers4":
                    flyers(4)
                elif type == "flyers1" and size=='A4':
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
                cmd = "gs -q -dNOPAUSE -dBATCH -sDEVICE=ppmraw -sOutputFile=- -r288 " + outdir + '/' + outpdf + " | pnmscale 0.25 | ppmquant 256 | pnmtopng > " + outdir + '/' + outfile
                child = popen2.Popen3(cmd, True) # capture stderr
                child.tochild.close()
                # req.err.write(child.fromchild.read()) # no need for stdout in log file, just stderr
                req.err.write(child.childerr.read())
                status = child.wait()
                if os.WIFSIGNALED(status):
                    raise Exception, "%s: killed by signal %d" % (cmd, os.WTERMSIG(status))
                elif os.WEXITSTATUS(status) != 0:
                    raise Exception, "%s: exited with failure status %d" % (cmd, os.WEXITSTATUS(status))
            if (incgi):
                file_to_stdout(outdir + '/' + outfile)


    except Exception, e:
        req.out.write("Content-Type: text/plain\r\n\r\n")
        req.out.write("Sorry, we weren't able to make your poster.\n\n")
        req.out.write(str(e) + "\n")

    req.Finish()


