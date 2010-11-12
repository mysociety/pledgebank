PledgeBank
==========

Checking out the code
---------------------

If you just want read-only access to everything, clone this
repository as with any other, then cd into it and run
    git submodule update --init
to fetch the contents of the commonlib submodule.

If you want to fork your own pledgebank, but don't think
you'll have to alter the common code in commonlib, then
just do the above on your forked version.

If you want to be able to alter commonlib too, then fork
pledgebank and commonlib, and do something like the following
(using dracos' forks as an example here):

-------------------
$ git clone git@github.com:dracos/pledgebank.git
[...]
$ cd pledgebank/
$ git config submodule.commonlib.url git@github.com:dracos/commonlib.git
$ git submodule update --init
[...]
Submodule path 'commonlib': checked out 'd49d5b0413b85099397cff550fec7fd94c2943fc'
-------------------

By specifying a different submodule URL, you've checked out one
that you can push back to. Note that the commonlib will not be
on a branch by default, so you'll have to cd commonlib and
git checkout master (or whatever) to do that.

If you change something in commonlib that you want the parent
project to have, you should first commit/push the change in
commonlib, then git add commonlib and commit from the parent
directory (beware, make sure there's no "/" on the end of
git add otherwise git gets all confused).

You can presumably change from a read-only commonlib to your own
forked version by removing its contents, changing
submodule.commonlib.url, and rerunning git submodule, though
I've never tried that myself.

