#!/usr/bin/env python2.3
#
# poster.cgi:
# Creates posters for printing out, caching them in a directory.
#
# Run from the command line to generate a test PDF.
#       ./poster.cgi >test.pdf 
# In this case the poster will always be regenerated.
#
# Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
# Email: matthew@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: poster.cgi,v 1.21 2005-03-24 14:09:56 francis Exp $
#

import os
import sys
from time import time
from pyPgSQL import PgSQL
import fcgi
import tempfile

from reportlab.pdfgen import canvas
from reportlab.lib.units import cm
from reportlab.lib.styles import ParagraphStyle

from reportlab.lib.styles import getSampleStyleSheet
from reportlab.platypus import Paragraph, Frame
from reportlab.lib.enums import TA_LEFT, TA_CENTER

sys.path.append("../../pylib")
import mysociety.config
mysociety.config.set_file("../conf/general")

db = PgSQL.connect('::' + mysociety.config.get('PB_DB_NAME') + ':' + mysociety.config.get('PB_DB_USER') + ':' + mysociety.config.get('PB_DB_PASS'))

types = ["cards", "tearoff", "flyers16", "flyers4", "flyers1"]
sizes = ["A4"]
formats = ["pdf", "png", "gif"]

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
# Flyers using reportlab.platypus for word wrapping

# Prints one copy of the flier at given coordinates, and font sizes.
# Returns False if it didn't all fit, or True if it did.
def flyer(c, x1, y1, x2, y2, size):
    w = x2 - x1
    h = y2 - y1

    # Draw dotted line round the outside
    c.setDash(3,3)
    # Don't use c.rect, so that the lines are always drawn the same direction,
    # and overlapping dashed lines don't cancel each other out
    c.line(x1, y1, x1, y2)
    c.line(x2, y1, x2, y2)
    c.line(x1, y1, x2, y1)
    c.line(x1, y2, x2, y2)

    # Scale font sizes - with minimum for extreme cases
    large_writing = size * 35
    small_writing = size * 20
    if small_writing < 4:
        small_writing = 4
    if large_writing < 4:
        large_writing = 4

    # Set up styles
    p_head = ParagraphStyle('normal', alignment = TA_CENTER, spaceBefore = 0, spaceAfter = size*20, 
        fontSize = large_writing, leading = size*37, fontName = 'Helvetica')
    p_normal = ParagraphStyle('normal', alignment = TA_CENTER, spaceBefore = 0, spaceAfter = size*20, 
        fontSize = small_writing, leading = size*22, fontName = 'Helvetica')
    p_nospaceafter = ParagraphStyle('normal', alignment = TA_CENTER, spaceBefore = 0, spaceAfter = 1, 
        fontSize = small_writing, leading = size*22, fontName = 'Helvetica')

    # Draw all the text
    story = [
        Paragraph(1*('''I, %s, will %s if %s %s will %s. ''' % (pledge['name'], pledge['title'],
            pledge['target'], pledge['type'], pledge['signup'])), p_head),

        Paragraph('', p_normal),
        Paragraph('''Please support me by signing up, and by
            encouraging other people to do the same. I am using the charitable service
            PledgeBank.com to gather support.''', p_normal),

        Paragraph('''It will only take you a few seconds - ''', p_nospaceafter),
        Paragraph('''<b>www.pledgebank.com/%s</b>''' % ref, p_nospaceafter),
        Paragraph('''(web sign up is free)''', p_normal),

        Paragraph('''or text <b>pledge %s</b> to <b>%s</b>''' % (ref, sms_number), p_nospaceafter),
        Paragraph('''(cost 50p)''', p_normal),

        Paragraph('''This pledge closes on %s. Thanks!''' % pledge['date'], p_normal)
    ]

    dots_body_gap = 10
    f = Frame(x1, y1, w, h, showBoundary = 0, 
        leftPadding = dots_body_gap, rightPadding = dots_body_gap, topPadding = dots_body_gap, bottomPadding = dots_body_gap
        )
    f.addFromList(story, c)

    # If it didn't fit, say so
    if len(story) > 0:
        return False
    return True

def flyers(number):
    # Number of flyers to fit on the page
    if number == 1:
        flyers_across = 1
        flyers_down = 1
    elif number == 4:
        flyers_across = 2
        flyers_down = 2
    elif number == 16:
        flyers_across = 4
        flyers_down = 4
    else:
        raise Exception("Invalid number %d for flyers" % number)

    # Just A4 for now
    page_width = 21 * cm
    page_height = 30 * cm
    # Tweaked to make sure dotted lines are displayed on all edges
    # (not sure yet why top margin needs to be larger than others)
    margin_top = 0.4 * cm
    margin_left = 0.1 * cm
    margin_bottom = 0.1 * cm
    margin_right = 0.1 * cm

    # Calculate size of fliers
    flyer_width = (page_width - margin_left - margin_right) / flyers_across 
    flyer_height = (page_height - margin_top - margin_bottom) / flyers_down

    # Try different font sizes on a hidden canvas to get the largest
    dummyc = canvas.Canvas(None)
    size = 3.0
    while True:
        # print >>sys.stderr, "Trying flyer size %s" % size
        ok = flyer(dummyc, 0, 0, flyer_width, flyer_height, size);
        if ok:
            break
        size = size * 19 / 20
        if size * 30 < 10:
            raise Exception("Pledge text wouldn't fit on page")

    # Draw fliers
    c.setStrokeColorRGB(0,0,0)
    c.setFillColorRGB(0,0,0)
    for along in range(0, flyers_across):
        for down in range(0, flyers_down):
            flyer(c, 
                along * flyer_width + margin_left, down * flyer_height + margin_bottom, 
                (along + 1) * flyer_width + margin_left, (down + 1) * flyer_height + margin_bottom,
                size)

############################################################################

# Main loop
while fcgi.isFCGI():
    req = fcgi.Accept()
    fs = req.getFieldStorage()
    #req.err.write("got request in poster.cgi, path: %s\n" % req.env.get('PATH_INFO'))

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
is sent to standard output.

When run from CGI, files are cached in the directory PB_PDF_CACHE specified
in conf/general.  This cache is not used on the command line.""")
        parser.add_option("--size", dest="size", default="A4",
            help=", ".join(sizes));
        parser.add_option("--type", dest="type", default="flyers4",
            help=", ".join(types));
        parser.add_option("--format", dest="format", default="pdf",
            help=", ".join(formats));
        (options, args) = parser.parse_args()
        if len(args) <> 1:
            parser.print_help()
            print >>sys.stderr, "specify Pledgebank ref"
            continue
        ref = args[0] 
        size = options.size
        type = options.type 
        format = options.format

    if incgi:
        if format == 'pdf':
            req.out.write("Content-Type: application/pdf\r\n\r\n")
        elif format == 'png':
            req.out.write("Content-Type: image/png\r\n\r\n")
        elif format == 'gif':
            req.out.write("Content-Type: image/gif\r\n\r\n")
        else:
            req.out.write("Content-Type: text/plain\r\n\r\n")

    if not size in sizes:
        raise Exception, "Unknown size '%s'" % size
    if not type in types:
        raise Exception, "Unknown type '%s'" % type
    if not format in formats:
        raise Exception, "Unknown format '%s'" % format

    outdir = mysociety.config.get("PB_PDF_CACHE")
    outpdf = "%s_%s_%s.pdf" % (ref, size, type)
    outfile = "%s_%s_%s.%s" % (ref, size, type, format)

    def file_to_stdout(filename):
        f = file(filename, 'rb')
        content = f.read()
        f.close()
        req.out.write(content)

    if os.path.exists(outdir + '/' + outfile) and incgi:
        file_to_stdout(outdir + '/' + outfile)
    else:
        q = db.cursor()
        pledge = {}
        q.execute('SELECT title, date, name, type, target, signup FROM pledges WHERE ref = %s', ref)
        (pledge['title'],date,pledge['name'],pledge['type'],pledge['target'],pledge['signup']) = q.fetchone()
        q.close()

        day = date.day
        pledge['date'] = "%d%s %s" % (day, ordinal(day), date.strftime("%B %Y"))
        if pledge['signup'] == "do the same":
            pledge['signup'] = "too"
        sms_number = mysociety.config.get('PB_SMS_DISPLAY_NUMBER')

        # Generate PDF file
        (canvasfileh, canvasfilename) = tempfile.mkstemp(dir=outdir,prefix='tmp')
        c = canvas.Canvas(canvasfilename)
        try:
            if type == "cards":
                cards()
            elif type == "tearoff":
                tearoff()
            elif type == "flyers16":
                flyers(16)
            elif type == "flyers4":
                flyers(4)
            elif type == "flyers1":
                flyers(1)
            else:
                raise Exception, "Unknown type '%s'" % type
        except Exception, e:
            print >>sys.stderr, e
            c.setStrokeColorRGB(0,0,0)
            c.setFont("Helvetica", 15)
            c.drawCentredString(10.5*cm, 25*cm, str(e))
        c.save()
        os.rename(canvasfilename, outdir + '/' + outpdf)
            
        # Generate any other file type
        if format != 'pdf':
            os.spawnlp(os.P_WAIT, "convert", "convert", outdir + '/' + outpdf, outdir + '/' + outfile)

        file_to_stdout(outdir + '/' + outfile)

    req.Finish()


