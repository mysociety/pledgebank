<?
// translate.php:
// Main code for PledgeBank website.
//
// Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
// Email: matthew@mysociety.org. WWW: http://www.mysociety.org
//
// $Id: translate.php,v 1.8 2007-02-22 23:16:40 timsk Exp $

require_once "../phplib/pb.php";
require_once '../../phplib/utility.php';

page_header(_('Translate PledgeBank into your own language')); ?>

<style type="text/css">
pre {
    margin: 0 2em;
    background-color: #eeeeee;
    padding: 3px;
}
</style>

<h2><?=_('Translating PledgeBank') ?></h2>
<p><?=_("So you want to have PledgeBank in your own language?
We've hopefully made it relatively straightforward.") ?></p>

<p><?=_('Firstly, do <a href="/contact">contact us</a> so we
can keep track of things, and perhaps put you in touch
with other people translating into the same language.
Also, <a href="https://secure.mysociety.org/admin/lists/mailman/listinfo/internationalisation">sign up to our mailing list</a> to discuss translations.') ?></p>

<p><?=_('Do let us know if there\'s anything below
that is hard to understand. The instructions assume you\'re running
Windows, apologies for that &ndash; users of other operating
systems should be able to work out what similar steps to take.') ?></p>

<p><?=_('There are two ways to translate the file - <a href="#manually">manually</a> or
<a href="#poedit">using poEdit</a>, a program to help with translations.
If you\'re new at translating, I recommend using poEdit, as it
keeps track of untranslated strings and things like that.') ?></p>

<p><?=_('If there are any problems or questions at any stage, <a href="/contact">contact us</a> or ask on the mailing list.') ?></p>

<h3><a name="poedit"></a><?=_('Using poEdit') ?></h3>

<ol>

<li><?=_('<strong>Installing PoEdit:</strong>
Download poEdit from <a href="http://www.poedit.net/">PoEdit</a>
(if running Windows, just get the simple installer version)
and install it.') ?>

<li><?=_('<strong>Getting the original file:</strong>
Save the following link to somewhere on your computer
(normally by right-clicking on the link and selecting
"Save Target As..." or "Save Link As..."):
<a href="https://secure.mysociety.org/cvstrac/getfile/mysociety/locale/PledgeBank.po">Translation file</a>.
(If you left click on the link, you can File-&gt;Save As... and then click Back to get back here.)') ?>

<li><?=_('<strong>Opening the file:</strong>
Open poEdit (Start-&gt;Programs-&gt;poEdit-&gt;poEdit). If this is the first time you\'ve run it, it will ask for your name and email address, to put them in the translation file. Then go to File-&gt;Open and open up the file you have just downloaded.
<em>Don\'t be worried by the number of strings to translate!</em>
You do not have to translate every line before sending
us what you have done &ndash; the site will simply default
to English for anything yet to be translated.') ?>

<li><?=_('<strong>Basic translation:</strong>
Select a line to translate in the top frame, and enter the translation in the bottom. poEdit keeps track of which lines have not yet been translated, and which are "fuzzy" translations (automated translations that may be wrong and should be checked).
poEdit will also tell you if a sentence has a plural (ie. needs a different translation depending on the number of things being printed out). This should be straightforward.
File-&gt;Save or the Save button will save your translations to the same file.') ?>

<li><p><?=_('<strong>% placeholders:</strong>
Sadly, these are a bit
more complicated. The first are %&nbsp;placeholders.
These appear within text and stand for something missing &ndash; %d
means a number, %s means some more text. For example, a sentence to
give the colour of a cat and its size, in Spanish:') ?>

<pre>"The cat is %s and %d centimetres long."
"El gato tiene %s y %d cent&iacute;metros de largo."</pre>

<p><?=_('The colours would be translated separately elsewhere in the file.
If a situation arises where you need to refer to the % placeholders
in a different order from the English, you need to specify which
placeholder goes where. Here\'s an example, but ask if this comes up
in your translation (hopefully it won\'t :) ) :') ?>

<pre>"I have %s the %s."                (I have <em style="color: #ff0000">read</em> the <em style="color: #00ff00">book</em>.)
"Ich habe das %2\$s %1\$s."        (Ich habe das <em style="color: #00ff00">Buch</em> <em style="color: #ff0000">gelesen</em>.)
</pre>

<li><?=_('Email me with as much or as little as you have done, and I will get it up and running as soon as possible. :-)') ?>

</ol>

<h3><a name="manually"></a><?=_('Manually') ?></h3>

<ol>

<li><?=_('<strong>Getting the original file:</strong>
Save the following link to somewhere on your computer
(normally by right-clicking on the link and selecting
"Save Target As..." or "Save Link As..."):
<a href="https://secure.mysociety.org/cvstrac/getfile/mysociety/locale/PledgeBank.po">Translation file</a>.
(If you left click on the link, you can File-&gt;Save As... and then click Back to get back here.)') ?>

<li><?=('<strong>Opening the file:</strong>
Open Notepad (Start-&gt;Programs-&gt;Accessories-&gt;Notepad), go to File-&gt;Open and open up the file you have just downloaded.
<em>Don\'t be worried by the size of this file!</em>
You do not have to translate every line before sending
us what you have done &ndash; the site will simply default
to English for anything yet to be translated.') ?>

<li><?=_('<strong>Changing the header:</strong>
Edit the following lines as follows &ndash; change the
PO-Revision-Date line to be the current date and time,
the Last-Translator line to be your name and email address, 
and the Language-Team line to your language.') ?>

<li><?=_('<strong>Basic translation:</strong>
A line beginning with a <kbd>#</kbd> is a comment and can safely be ignored.
For normal sentences, translation is as follows. A line that
begins with "<kbd>msgid</kbd>" is the English text to be translated.
The line following that (beginning "<kbd>msgstr</kbd>") should contain the translation,
within the double quotes (").') ?>
<p><?=_('For example, in French:') ?>
<pre>msgid "The cat is black."
msgstr "Le chat est noir."</pre>
<p><?=_('If the translated line needs to include a double quote, you put a slash before it. For example, in German:') ?>
<pre>msgid "He said \"I like cheese.\""
msgstr "Er sagte \"Ich liebe K&auml;se.\""</pre>
<p><?=_('If it is a large block to be translated, it will be on more than one line. To show this, the first line
is the empty string "", with the original or translated text on further lines; "\n" is used to mean a new
line. For example, in Italian:') ?></p>
<pre>msgid ""
"I have 2 dogs, 1 cat, 1 guinea pig and a giraffe.\n"
"The giraffe has a long neck, and the two dogs are"
"called Bill and Ben."
msgstr ""
"Ho 2 cani, 1 gatto, 1 cavia e un giraffe.\n"
"Il giraffe ha un collo lungo ed i due cani sono"
"denominati Bill e Ben."
</pre>

<li><?=_('<strong>Filename comments:</strong>
The comment line before the "msgid" line is the file or files
that contain that text. So anything with the comment line
":# pb/web/faq.php" is from the FAQ page. This might be useful in
providing context for the text.') ?>

<li><p><?=_('<strong>% placeholders:</strong>
Sadly, there are a couple of things that makes things a bit
more complicated. The first are %&nbsp;placeholders.
These appear within text and stand for something missing &ndash; %d
means a number, %s means some more text. For example, a sentence to
give the colour of a cat and its size, in Spanish:') ?>

<pre>msgid "The cat is %s and %d centimetres long."
msgstr "El gato tiene %s y %d cent&iacute;metros de largo."</pre>

<p><?=_('The colours would be translated separately elsewhere in the file.
If a situation arises where you need to refer to the % placeholders
in a different order from the English, you need to specify which
placeholder goes where. Here\'s an example, but ask if this comes up
in your translation (hopefully it won\'t :) ) :') ?>

<pre>msgid "I have %s the %s."                 (I have <em style="color: #ff0000">read</em> the <em style="color: #00ff00">book</em>.)
msgstr "Ich habe das %2\$s %1\$s."        (Ich habe das <em style="color: #00ff00">Buch</em> <em style="color: #ff0000">gelesen</em>.)
</pre>

<li><p><?=_('<strong>Plurals:</strong> These are the other more complicated thing.
These use % placeholders to give different results depending on the value
of a number. It\'s probably easiest if I just give an example, in Portuguese:') ?>
<pre>msgid "I have %d dog."
msgid_plural "I have %d dogs."
msgstr[0] "Eu tenho %d c&atilde;o."
msgstr[1] "Eu tenho %d c&atilde;es."</pre>
<p><?=_('Some languages have three plurals &ndash; simply add a msgstr[2] line for the third, and I can sort out making sure it uses the right one.') ?>

<li><?=_('Email me with as much or as little as you have done, and I will get it up and running as soon as possible. :-)') ?>

</ol>

<p align="right"><?=_('Matthew Somerville<br>13<sup>th</sup> November 2005') ?></p>

<?
page_footer();
?>
