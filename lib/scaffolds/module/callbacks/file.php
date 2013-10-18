<?php

use AbleCore\Modules\Theme;

// All actions are prefixed with action_
function action_testing($first_argument)
{
	// Do some stuff...
	$hello = "Hello {$first_argument}!";

	// Return the rendered theme.
	return Theme::make('test_theme', array('hello' => $hello));
}

function action_date()
{
	// Nothing to do here...

	// Return the rendered theme.
	return Theme::make('test_theme');
}

// The title function defined in the MenuManager calls.
function action_date_title()
{
	return "Lol, this is a title!";
}

function action_test_path_2($hello)
{
	// Nothing to do here either!
	$hello_world = "Hello {$hello}!";

	// Return the rendered theme.
	return Theme::make('test_theme', array('hello' => $hello_world));
}
