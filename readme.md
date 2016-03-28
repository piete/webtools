# webtools
Between 2002 and 2011 PHP was my native server side web language, and I relied on it to get many things done.

Between shared web hosts at home and DIY LAMP stacks at work, I built a few tools to ease my deployment of databases and, eventually, incorporate git into my deployment scenarios.

This repo contains the two key tools for this use - install.php and sharedgit.php.

Would I use these now? Absolutely not. The scripts are poorly written and have absolutely no security whatsoever.

## install.php
While phpMyAdmin was possible, I often found it massively overkill to get things set up during development, and on a number of occasions, ended up having problems with database version mismatches between my local dev environment and my remote production environment.

## sharedgit.php
My discovery of Heroku in 2010 lead to thinking a lot about how to integrate git into deployment, and the first thing I discovered was that shared web hosts were not conducive to shell access or command line tools. With _system()_ to the rescue, I found it was possible to `./configure && make && make install` in my own shared piece of the server. This was great, but then I needed to find a way of triggering the remote git to pull for me (because I had no shell access, I couldn't git push locally). Enter `sharedgit.php`, which had a shonky `sharedgit.list` file defining remote repos I could replicate from, and an interface to futzing with that file.