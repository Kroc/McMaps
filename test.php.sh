#!/usr/bin/php
<?php

/* NOTE: This is a shell script. Please make it executable (`chmod +x test.php.sh`),
   and run it from the terminal (`./test.php.sh`).
*/

include 'mcmap.php';
include 'mcmapbook.php';

$book = new McMapBook (
	//the full path to the data folder of the Minecraft world, e.g. '~/Application Support/minecraft/saves/World1/data/'
	'.',
	//the map ID to begin writing at, i.e. 'map_0.dat'
	0
);

//note: ensure the number of maps required already exist in your minecraft world, if you use McMaps to generate a map that
//	hasn’t yet been crafted in the game, when you craft it, it will be overwritten. just run your McMaps script again

//you can use the next ID returned to start the next book
$next_id = $book->generate (<<<TXT
Leisure by W. H. Davies

What is this life if, full of care, we have no time to stand and stare?—

No time to stand beneath the boughs, and stare as long as sheep and cows:

No time to see, when woods we pass, where squirrels hide their nuts in grass:

No time to see, in broad daylight, streams full of stars, like skies at night:

No time to turn at Beauty's glance, and watch her feet, how they can dance:

No time to wait till her mouth can enrich that smile her eyes began?

A poor life this if, full of care, we have no time to stand and stare.
TXT
);

?>