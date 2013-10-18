<?php

function MODULE_preprocess_block_test_block(&$variables)
{
	// Add any logic for the block in here...
	// Yes, this includes PDO queries.

	$variables['hello'] = 'World!';
}
