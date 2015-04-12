<?php	
		/*
		
		Readme
		--------
		
		Description
		A script to check daily whether Google brand term sitelinks have changed, and send an email if they do.
		
		Dependendencies
		- http://simplehtmldom.sourceforge.net/ 
		- http://code.stephenmorley.org/php/diff-implementation/. 
		
		Requires daily cron job: 
			crontab -e 
			0 15 * * * /path/to/this/file/index.php
		
		April 2015 - Chris Reynolds - @ChrisReynoldsUK
		
		1. Set common variables
		2. Call the required libraries
		3. Scrape the URLs and write them to an array
		4. Write today's values to a file
		5. Check the returned strings are the same vs. last check					
		6. If the returned strings are not the same, send an email
		7. Delete old records of sitelinks		
	
		Features to add:
			- Improve deliverabilty on mail function
			- Add graceful handling if the previous day's file can't be found
			- Inline styles on the table (thank you Gmail)
			- Pretty the whole thing up
		
		*/
		
		
		//1. Set common variables
		
		$recipients = "chris.reynolds.uk@gmail.com, chris@cleverbiscuit.com"; 			// Who the updates goes to
		$keyword = "ebay";																// Brand term to check sitelinks for
		$retentionDays = 31;															// Number of days to keep records for (default 31)
		$checks = array																	
			  (
			  //Format: "Country name", "country TLD", "Language to check", "Country Code"
			  array("UK","co.uk","en", "GB"),
			  array("Germany", "de", "de","DE"),
			  array("Australia", "com.au", "en", "AU"),
			  array("US", "com", "en", "US")
			  );
   
		//2. Call the required libraries
		
		require_once ("simple_html_dom.php");						// http://simplehtmldom.sourceforge.net/
		require_once ("class.Diff.php");							// http://code.stephenmorley.org/php/diff-implementation/
	
		//3. Scrape the URLs and write them to an array
		
		$n = 0; 
		foreach ($checks as $check) {$n++;}							// Count the number of checks to run (counts the $checks array)
	
			for ($row = 0; $row < $n; $row++) {
			
				$in = str_replace(' ','+',$keyword); 				// space is a +
				$url = "https://www.google.". $checks[$row][1] . "/search?num=10&pws=1&gws_rd=cr&hl=" . $checks[$row][2] . "&gl=" . $checks[$row][3] . "&q=" . $keyword . "&oq="  . $keyword;
				//echo $url . "<br>";								// Echo the URL constructed. For testing
															
				$html = file_get_html($url); 						// Write the HTML returned to the $HTML variable
				//echo $html; 										// View the actual markup returned by the 'file_get_html'. For testing.
			
				$concatLink = NULL;
				$siteLinks = $html->find('h3.r a.sla'); 			// Select all <a> with a tags with a class of 'sla', inside an <h3> with class 'r' (how Google marks-up site links).
				foreach ($siteLinks as $siteLink) {
				    //$title = trim($siteLink->plaintext);			// Grab link title from the array. Turned off by default.
				    $link  = trim($siteLink->href);
				
				    // If it is not a direct link but a URL reference inside it, then extract it
				    if (!preg_match('/^https?/', $link) && preg_match('/q=(.+)&amp;sa=/U', $link, $matches) && preg_match('/^https?/', $matches[1])) {
				        $link = $matches[1];
				    } else if (!preg_match('/^https?/', $link)) { 					// skip if it is not a valid link
				        continue;
				    }
				    //echo '<p>Title: ' . $title . '<br />';						// Echo link title (if turned on above). For testing.
				    //echo $link . '<br />';										// Echo each link on a new line. For testing.
				   	   
				    $concatLink = $link .  PHP_EOL . $concatLink ;					// Add each new link to the $concatLink variable						    
				}
				//echo $concatLink . '<br><br><br>'; 								// Echo concatenated string. For testing.
			
			
			//4. Write today's values to a file
			
			$countryVar = $checks[$row][3];
			if (!file_exists($countryVar)) {mkdir($countryVar, 0777, true);}		//Checks the direcotry exists, and if not, creates it.
			
			
			$todaysFile = $countryVar . "/" . $countryVar . "-" . date('d-m-Y').'.txt';		// Build filename insude a folder of the country code.						
			
			//Check the previous files exist, if not, cycle through a default 31 days to find them.
			$previousDay = 1;	
				for ($previousDay = 1 ; $previousDay < $retentionDays; $previousDay++) {
					 $yesterdayFile = $countryVar . "/" . $countryVar . "-" . date('d-m-Y',strtotime("- $previousDay days")).'.txt';	
					 if (file_exists($yesterdayFile) != 0) {
						    break 1;
					 }
				}
						
			$fh = fopen($todaysFile, 'w') or die("Bugger, can't open the file. Check file permissions etc.");		// Create a file with today's date
			fwrite($fh, $concatLink);																				// Write in today's $concatLink values
			fclose($fh);																							// Save and close. Nice and tidy like.
			//echo "Yesterday file name:" . $yesterdayFile . "<br>" . "Today is " . $todaysFile; 	    			// Echo file names. For testing.																		
			//5. Check the returned strings are the same vs. last check					
			$diffTable = Diff::toTable(Diff::compareFiles($yesterdayFile, $todaysFile));							// Using the Class.Diff library called in section 1
			//echo $diffTable;																						// Echo the diff table. For testing.	
			
			$yesterdaySHA = sha1_file($yesterdayFile);
			$todaySHA = sha1_file($todaysFile);
			//echo ' (Yeterday's file SHA: ' . sha1_file($yesterdayFile) . ')', PHP_EOL;							// Echo the SHA of the files. For Testing.
			//echo ' (Today's file SHA: ' . sha1_file($todaysFile) . ')', PHP_EOL;												
		
			if ($yesterdaySHA == $todaySHA) {
				
				
					if ($previousDay == 1) {
						
						$stickerForGrammer = "yesterday. <br>";
					} else {
						
						$stickerForGrammer = $previousDay . ' day\'s ago.<br>';
					}
					
				echo "No change to the " . $checks[$row][0] . " sitelinks since last check: " . $stickerForGrammer;		// Condition executes when no changes. No email sent.
				
				
				
			} else {
			
			//6. If the returned strings are not the same, send an email
			
				$headers = "MIME-Version: 1.0" . "\r\n";
				$headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
				//$headers .= 'From: <chris.reynolds.>' . "\r\n";
				$headers .= 'Cc: chrisreynolds@ebay.com ' . "\r\n";
			
				$msg = 	"<style>
					.diff td {
					  	vertical-align : top;
					  	white-space    : pre;
					  	white-space    : pre-wrap;
					  	font-family    : monospace;
					}	
					.diffDeleted {
					  	border: 1px solid rgb(255,192,192);
					  	background: rgb(255,224,224);	
					}
					.diffInserted {
						  border: 1px solid rgb(192,255,192);
						  background: rgb(224,255,224);
					}	
				</style>" .
				"<p>Hello, it looks like the Google brand term sitelinks have changed for " . $keyword . " in " . $countryVar ."</p><p>" .
				$diffTable .
				"</p> <p>Check it out on a <a target='_blank' href='". $url . "'/>live Google search</a>, and make sure there are no issues.</p> <p>Your friendly local sitelinks checking script.</p>";			
				
																									// Echo the whole message on screen. For testing.
				
				mail($recipients,"Sitelinks have changed. Take a look.",$msg, $headers); 			// Send the email
				echo $msg;																			// Display's the email, on screen when the file accessed directly
			}		
			
			//7. Delete old records of sitelinks		
			$deleteFile = $countryVar . "/" . $countryVar . "-" . date('d-m-Y',strtotime("-$retentionDays days")).'.txt';			
			if (file_exists($deleteFile)) {
					unlink($deleteFile);	
					echo "<br><br>FYI: Just deleted an older file: " . $deleteFile;
				
				} else {
					//echo "<br><br>No need to delete, files not yet that old: " .$deleteFile . " does not exist";
			}
	}
	
?>
