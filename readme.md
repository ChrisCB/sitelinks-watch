		
Sitelinks Watch
-----------------
April 2015 - Chris Reynolds - @ChrisReynoldsUK - http://qforquery.com & http://cleverbiscuit.com


Purpose
-----------------
A script to check daily whether Google brand term sitelinks have changed, and send an email if they do.

For example, for the brand 'clever biscuit' in the UK:
https://www.google.co.uk/search?q=clever+biscuit&gl=GB&hl=en&num=100&pws=1&gws_rd=cr

This script checks the links for you, and alerts you when they change, so you can tweak in Google's Webmaster Tools if necessary.

 		
Dependencies
-----------------
2x PHP classes. Both included in this Repo, source files at:
-> http://simplehtmldom.sourceforge.net/ 
-> http://code.stephenmorley.org/php/diff-implementation/. 
		
Requires a daily cron job: 
		crontab -e 
		0 15 * * * /path/to/this/file/index.php
		

Roadmap
-----------------

-> Improve email deliverability
-> Inline styles on the table (thank you Gmail)
-> Pretty the whole thing up
-> Add support for more than one brand 


License
-----------------
	
This is free code; you can redistribute it and/or modify it as you wish, with or without attribution.

I've uploaded this in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
A PARTICULAR PURPOSE.  

