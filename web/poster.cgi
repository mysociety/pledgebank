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
# $Id: poster.cgi,v 1.13 2005-03-17 16:19:11 francis Exp $
#

import os
import sys
from time import time
from pyPgSQL import PgSQL
from reportlab.pdfgen import canvas
from reportlab.lib.units import cm
from reportlab.lib.styles import ParagraphStyle

from reportlab.lib.styles import getSampleStyleSheet
from reportlab.platypus import Paragraph, Frame
from reportlab.lib.enums import TA_LEFT, TA_CENTER

import fcgi

sys.path.append("../../pylib")
import mysociety.config
mysociety.config.set_file("../conf/general")

db = PgSQL.connect('::' + mysociety.config.get('PB_DB_NAME') + ':' + mysociety.config.get('PB_DB_USER') + ':' + mysociety.config.get('PB_DB_PASS'))

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
# Flyer using reportlab.platypus for word wrapping

def flyer(x1, y1, x2, y2):
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

    # Set up styles
    p_head = ParagraphStyle('normal', alignment = TA_CENTER, spaceBefore = 0, spaceAfter = 20, 
        fontSize = 30, leading = 32, fontName = 'Helvetica')
    p_normal = ParagraphStyle('normal', alignment = TA_CENTER, spaceBefore = 0, spaceAfter = 20, 
        fontSize = 15, leading = 16, fontName = 'Helvetica')
    p_nospaceafter = ParagraphStyle('normal', alignment = TA_CENTER, spaceBefore = 0, spaceAfter = 1, 
        fontSize = 15, leading = 16, fontName = 'Helvetica')

    # Draw
    story = [
        Paragraph('''I, %s, will %s if %s %s will %s.''' % (pledge['name'], pledge['title'],
            pledge['target'], pledge['type'], pledge['signup']), p_head),
        Paragraph('''Please support me by signing up, and by
            encouraging other people to do the same. I am using the charitable service
            PledgeBank.com to gather support.''', p_normal),
        Paragraph('''It will only take you a few seconds - sign up free at''', p_nospaceafter),
        Paragraph('''<b>www.pledgebank.com/%s</b>''' % ref, p_normal),
        Paragraph('''<p>Or text <b>pledge %s</b> to <b>12345</b>''' % ref, p_nospaceafter),
        Paragraph('''(cost 25p)''', p_normal),
        Paragraph('''This pledge closes on %s. Thanks!''' % pledge['date'], p_normal)
    ]

    dots_body_gap = 20
    f = Frame(x1, y1, w, h, showBoundary = 0, 
        leftPadding = dots_body_gap, rightPadding = dots_body_gap, topPadding = dots_body_gap, bottomPadding = dots_body_gap
        )
    f.addFromList(story, c)

def flyers():
    c.setStrokeColorRGB(0,0,0)
    c.setFillColorRGB(0,0,0)
    flyer(0, 0*cm + 0, 10.5 * cm, 0*cm + 15 * cm)
    flyer(0, 15*cm + 0, 10.5 * cm, 15*cm + 15 * cm)
    flyer(10.5 * cm, 0*cm + 0, 21 * cm, 0*cm + 15 * cm)
    flyer(10.5 * cm, 15*cm + 0, 21 * cm, 15*cm + 15 * cm)

############################################################################

# Main loop
while fcgi.isFCGI():
    req = fcgi.Accept()
    fs = req.getFieldStorage()
    # req.err.write("got request, path: %s" % req.env.get('PATH_INFO'))

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
            (type, suffix) = type.split('.')
        else:
            type = 'cards'
            suffix = 'pdf'
    else:
        incgi = False
        ref = 'automatedtest'
        size = 'A4'
        type = 'flyers'
        suffix = 'pdf'

    if incgi:
        if suffix == 'pdf':
            req.out.write("Content-Type: application/pdf\r\n\r\n")
        else:
            req.out.write("Content-Type: text/plain\r\n\r\n")

    outdir = mysociety.config.get("PB_PDF_CACHE")
    outfile = "%s_%s_%s.pdf" % (ref, size, type)

    def output_file(filename):
        f = file(filename, 'rb')
        content = f.read()
        f.close()
        req.out.write(content)

    if os.path.exists(outdir + '/' + outfile) and incgi:
        output_file(outdir + '/' + outfile)
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

        c = canvas.Canvas(outdir + '/' + outfile)
        if type == "cards":
            cards()
        elif type == "tearoff":
            tearoff()
        elif type == "flyers":
            flyers()
        else:
            raise Exception, "Unknown type '%s'" % type
        c.save()
        output_file(outdir + '/' + outfile)

    req.Finish()


