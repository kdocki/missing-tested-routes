<?php


/**
 * Get a list of routes organized by the route name
 */
function routes()
{
	$routesByKey = [];

	$routes = Route::getRoutes();

	foreach ($routes as $route)
	{
		$action = simple_route_action($route);

		if ($action) $routesByKey[$action] = $route;
	}

	return $routesByKey;
}

/**
 * easy way to get the route name
 * stripped of it's namespace (if it has one)
 */
function simple_route_action($route)
{
	$name = $route->getActionName();

	$action = $route->getAction();

	if ($name && $action && array_key_exists('namespace', $action))
	{
		$name = remove_beginning($action['namespace'] . '\\', $name);
	}

	return $name;
}

/**
 * this removes substr from the beginning of this string
 */
function remove_beginning($substr, $string)
{
	if (strpos($string, $substr) === 0)
	{
		return substr($string, strlen($substr));
	}

	return $string;
}


/**
 * find the namespace of this code
 * 
 * namespace method from gist
 * https://gist.github.com/naholyr/1885879
 */
function namespaceOf($src)
{
	$tokens = token_get_all($src);
	$count = count($tokens);
	$i = 0;
	$namespace = '';
	$namespace_ok = false;
	while ($i < $count) {
		$token = $tokens[$i];
		if (is_array($token) && $token[0] === T_NAMESPACE) {
			// Found namespace declaration
			while (++$i < $count) {
				if ($tokens[$i] === ';') {
					$namespace_ok = true;
					$namespace = trim($namespace);
					break;
				}
				$namespace .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
			}
			break;
		}
		$i++;
	}
	if (!$namespace_ok) {
		return null;
	} else {
		return $namespace;
	}
}


/**
 * find the class name (with namespace) for 
 * this file
 */
function classOf($file)
{
	$src = file_get_contents($file->getRealPath());

	$namespace = namespaceOf($src);

	$className = trim($file->getBaseName(), '.' . $file->getExtension());

	if ($namespace == null)
	{
		return $className;
	}

	return $namespace . "\\" . $className;
}


/**
 * get the reflection class for this file
 */
function reflectionOf($file)
{
	$className = classOf($file);

	require_once $file->getRealPath();

	return new ReflectionClass($className);
}


/**
 * get the annotations of a single class method
 */
function annotationsOfMethod($method)
{
	$comments = $method->getDocComment();

	preg_match_all('#@(.*?)\n#s', $comments, $annotations);
	    
    return $annotations[1];
}


/**
 * get all the annotations in a given file
 */
function annotationsOf($file)
{
	$annotations = [];

	$reflection = reflectionOf($file);

	foreach ($reflection->getMethods() as $method)
	{
		$annotations = array_merge($annotations, annotationsOfMethod($method));
	}

	return $annotations;
}


/**
 * filters out only the route annotations,
 * that is what we are interested in
 */
function filterOutRouteAnnotations($annotations)
{
	$filtered = [];

	foreach ($annotations as $annotation)
	{
		if (starts_with($annotation, 'action '))
		{
			$filtered[] = explode(' ', remove_beginning('action ', trim($annotation)))[0]; 
		}
	}

	return array_unique($filtered);
}


/**
 * we get a list of tested laravel routes
 * by using annotations in our test cases
 */
function allTestedRoutes()
{
	$base = getcwd();

	$annotations = [];

	$finder = new \Symfony\Component\Finder\Finder();

	$finder->files()->name('*Test.php')->in("{$base}/tests");

	foreach ($finder as $file)
	{
		try {
			$annotations = array_merge($annotations, annotationsOf($file));
		} catch (Exception $e) {
			print ' --- got an error on ' . $file->getBaseName() . PHP_EOL;

			// do nothing, something was probably jacked
			// up with the class...
		}
	}

	return filterOutRouteAnnotations($annotations);
}


/**
 * This function colors the text output on
 * the console for us
 */
function color($str, $fgColor = null, $bgColor = null)
{
	$colored = '';

	$fg = [
		'black' => '0;30',
		'dark_gray' => '1;30',
		'blue' => '0;34',
		'light_blue' => '1;34',
		'green' => '0;32',
		'light_green' => '1;32',
		'cyan' => '0;36',
		'light_cyan' => '1;36',
		'red' => '0;31',
		'light_red' => '1;31',
		'purple' => '0;35',
		'light_purple' => '1;35',
		'brown' => '0;33',
		'yellow' => '1;33',
		'light_gray' => '0;37',
		'white' => '1;37',
	];

	$bg = [
		'black' => '40',
		'red' => '41',
		'green' => '42',
		'yellow' => '43',
		'blue' => '44',
		'magenta' => '45',
		'cyan' => '46',
		'light_gray' => '47',
	];

	if (isset($fg[$fgColor]))
	{
		$fgColor = $fg[$fgColor];
	}

	if (isset($bg[$bgColor]))
	{
		$bgColor = $bg[$bgColor];
	}

	if ($fgColor)
	{
		$colored .= "\033[{$fgColor}m";
	}

	if ($bgColor)
	{
		$colored .= "\033[{$bgColor}m";
	}

	return $colored . $str . "\033[0m";
}


/**
 * helper for end of line
 */
function eol($str, $fgColor = null, $bgColor = null)
{
	print color($str, $fgColor, $bgColor) . PHP_EOL;
}


/**
 * prints a test for the route with color/style formatting
 */
function print_test_for_route($route)
{
    $method = $route->methods()[0];
    $uri    = '/' . $route->uri();
    $action = simple_route_action($route);
    $routeName = $route->getName();
    $name   = $route->getName() ?: simple_route_action($route);
    $funcName = strtolower(str_replace(['.', '-', '\\', '@'], '_', $name));
    
    if (!$action) return;

    $dataLine = null;
    $callParams = color("'{$method}', \"{$uri}\"", 'yellow');
    $assertParams = '200, $response->status()';
    $incompleteParams = color("'This test is incomplete'", 'yellow');

    if (strtoupper($method) != 'GET')
    {
	    $callParams .= ', $data';
	    $dataLine = '    $data = [];';
	    $assertParams = '302, $response->status()';
    }

    eol('');
	eol("/**", 'dark_gray');
	if ($routeName) eol(" * @route {$routeName}", 'dark_gray');
	eol(" * @action {$action}", 'dark_gray');
	eol(" */", 'dark_gray');

	eol(
		color('public', 'light_red') . ' ' . 
		color('function', 'light_cyan') . ' ' . 
		color ("test_{$funcName}", 'light_green'). '()'
	);

	eol('{');
	eol('    $this->markTestIncomplete(' . $incompleteParams . ');');
	if ($dataLine) eol($dataLine);
	eol('    $response = $this->call(' . $callParams . ');');
	eol('    $this->assertEquals(' . $assertParams . ');');
	eol('}');
    eol('');
}


/**
 * prints untested routes for us so we can copy
 * and paste them into our routes file
 */
function print_untested($untested, $routes)
{
	if (count($untested) == 0)
	{
		return eol('All routes appear tested.', 'light_blue');
	}

	foreach ($untested as $name)
	{
		print_test_for_route($routes[$name]);
	}
}


/**
 * prints invalid named routes found
 */
function print_invalid($invalid)
{
	if (count($invalid) == 0) return;

	eol('');
	eol('WARNING', 'yellow');
	eol('You have ' . color('@action', 'green') . ' annotations ');
	eol('for route names that do not exist ');
	eol('in your Laravel application: ');

	foreach ($invalid as $name)
	{
		eol("   $name", 'red');
	}
}


/**
 * Runs the main prog for us (c++ style baby!)
 */
function main()
{
	$routes = routes();

	$routeNames = array_keys($routes);

	$tested = allTestedRoutes();

	$untested = array_diff($routeNames, $tested);

	$invalid = array_diff($tested, $routeNames);

	print_untested($untested, $routes);

	print_invalid($invalid);	
}


/**
 * include dependencies and run the main method
 */
$base = getcwd();

require "{$base}/bootstrap/autoload.php";

$app = require "$base/bootstrap/app.php";

$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

main();
