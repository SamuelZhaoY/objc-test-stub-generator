<?php
// objc-generate-stub-from-class-declarations-in-folder.php
//
// Obj-C Test-Stub generator v0.1
// by Dennis Sterzenbach <dennis.sterzenbach@gmail.com>
//
// This tool scans a given directory for *.h files and automatically generates XCTestCase classes
// from the class declarations found inside each file.
//
// It simply looks into each file for the first Objective-C class declaration, takes the class name
// as base for a XCTestCase class and scans for declared public methods, generating one test-method
// for each declared instance-method.
//
// Generated XCTestCases are written out into files with equal name.
//
// Visit for more info:
// https://github.com/dennissterzenbach/objc-test-stub-generator


// TODO:
//   - add generated files to xcodeproj
//   - implement more intelligent, real "parsing" logic to make sure we grab everything correct
//   - add ability to generate additional "has<Property>" on a per-class basis
//
//   - implement the opposite way: generating class and method declarations and stubs from tests or
//     a similar, simple text-based specification.
//

// CHANGELOG:
//
// v0.1 - initial version
//
////////////////////////////////////////////////////////////////////////////////////////////////////

$pathToProject  = realpath('.');
$projectName	= 'TestProject';

// integrate imports for OCMock and OCMockObject usage?
$useOCMock 			= false;
$useOCMockObject	= false;

$ignoreClassesWithoutMethods = false; // if you want to avoid generating test cases for classes without methods.

// list of lower case class names we will generate no test class for.
$useClassBlacklist   = false;
$ignoreClassesByName = array(
	'appdelegate',
);

// list of files to automatically and always import in each test case
$autoimports		= array();

// base path for creating the files with the generated test cases
$basePathForTests	= './Tests/';


/// LOGIC: FINDING FILES TO PARSE
function scan($pathToProject) {
	$Directory 		= new RecursiveDirectoryIterator($pathToProject);
	$Iterator 		= new RecursiveIteratorIterator($Directory);
	// $HeaderFiles 	= new RegexIterator($Iterator, '/.*\.h$/i', RecursiveRegexIterator::GET_MATCH);

	return $Iterator;
}


/// LOGIC: PARSING
function parseDeclarationFromFile($filename) {
	// TODO: combine scanning for header, methods, end of declaration into this parsing function so it will become more reliable
	$contents = file($filename);


	$classDeclOpen = false;
	$methodDeclOpen = false;
	$bracketCount = 0;
	$bracketCountUponMethodDeclOpen = 0;

	$methods = array();

	foreach ($contents as $line) {
		$line = trim($line); // trim whitespace
		if (!$classDeclOpen) { // scan for class declaration opening
			if (mb_substr($line, 0, 11) == '@interface ') {
				$classDeclOpen = true;

				// reset list of methods, because we found a new class declaration
				$methods = array();

				// read class name
				$className = getClassNameFromDeclarationLine($line);
			}
		}

		if ($classDeclOpen) {
			if ((substr($line, 0, 1) == '-' || substr($line, 0, 1) == '+'))  {
				$methods[] = getMethodNameFromDeclarationLine($line);
			}

			if ($line == '@end') {
				$classDeclOpen = false;
				return array(
					'class' => $className,
					'methods' => $methods,
				);
			}
		}
	}
}

function getClassNameFromDeclarationLine($line) {
	// split line which is expected in format:
	// @interface <NameOfClass> ........
	$line_parts = explode( ' ', $line );

	return $line_parts[1];
}

function getMethodNameFromDeclarationLine($line) {
	$regexp = '/^[\S]\s*\(([\S]+\s*\**)\)\s*(\w+)(:|;)/i';
	$ex = preg_match( $regexp, $line, $matches );
	return array(
		'returnType' => $matches[1],
		'name' => $matches[2],
	);
}

function scanHeaderFileForClassName($file) {
	$contents = file($file);

	foreach($contents as $line) {
		if (mb_substr($line, 0, 11) == '@interface ') {
			return getClassNameFromDeclarationLine($line);
		}
	}

	return ''; // no class found
}


function tmpl_replace($what, $by, $in) {
	return str_replace('{{' . $what . '}}', $by, $in);
}

function generateTestMethods($listOfMethods) {
	global $numberTestMethodsGenerated;

	$testMethods = '';

	if (!empty($listOfMethods)) {
		foreach ($listOfMethods as $method) {
			$testMethods .= tmpl_replace('methodname', ucfirst($method['name']), "- (void)test{{methodname}} {\n\t//given\n\n\t//when\n\n\t//then\n\n\tXCTFail(@\"Missing Test Implementation...\");\n\n}\n\n");
			$numberTestMethodsGenerated++;
		}
	}

	return $testMethods;
}

// initialize the script and its runtime env
function setup() {
	global $useOCMock;
	global $useOCMockObject;
	global $autoimports;

	$autoimports = array(
					'<XCTest/XCTest.h>',
					);

	if ($useOCMock) {
		$autoimports[] = '<OCMock.h>';
	}

	if ($useOCMockObject) {
		$autoimports[] = '<OCMockObject.h>';
	}


	// generate base path for tests
	global $basePathForTests;
	global $projectName;
	$basePathForTests = '../' . $projectName . 'Tests/';
}


/// OUTPUT: Templating
function generateImports($classname) {
	global $autoimports;

	$imports = '';
	if (!empty($autoimports)) {
		foreach ( $autoimports as $importfile ) {
			$imports .= tmpl_replace('importfile', $importfile, "#import {{importfile}}\n");
		}
	}

	$imports .= "#import \"{{classname}}.h\"\n\n";


	return $imports;
}


function generateTestFile($classname, $listOfMethods) {
	$testclassname = ucfirst($classname) . "Test";

	$headerComment = "//\n// auto-generated test class for {{classname}}\n// generated by Obj-C Test-Stub generator v0.1 by Dennis Sterzenbach <dennis.sterzenbach@gmail.com>\n// Visit for more info:\n// https://github.com/dennissterzenbach/objc-test-stub-generator\n\n";


	$imports     = generateImports($classname);
	$testMethods = generateTestMethods($listOfMethods);


	$full  = $headerComment  . $imports . "\n";
	$full .= "@interface {{testclassname}} : XCTestCase\n\n@end\n\n\n";
	$full .= "@implementation {{testclassname}}\n\n";
	$full .= "- (void)setUp {\n\n\n}\n\n";
	$full .= "- (void)tearDown {\n\n\n}\n\n";
	$full .= $testMethods;
	$full .= "\n@end\n";


	$full = tmpl_replace('classname', $classname, $full);
	$full = tmpl_replace('testclassname', $testclassname, $full);

	return $full;
}

function writeToDisk($filename, $content) {
	$writeTestFile 		= true;

	// detect if the path is missing and needs to be created
	$path 				= '';
	$filename_no_path 	= $filename;

	$parts = explode('/', $filename);

	$filename_no_path = array_pop($parts);
	if (!empty($parts)) {
		$path = implode('/', $parts);
	}

	// path is missing? create it if we should do that...
	if (!empty($path) && !realpath($path)) {
		if ($createMissingPathstructure) {
			printf("Creating missing path \"%s\"...\n", $path);
			mkdir($path, 0, true);
		} else {
			printf("Missing path \"%s\"... will abort to create %s\n", $path, $filename);
			$writeTestFile = false;
		}
	}

	if ($writeTestFile) {
		file_put_contents($filename, $content);
	}
}


function mayGenerateTestClass($className, $methods) {
	global $useClassBlacklist;
	global $ignoreClassesByName;
	global $ignoreClassesWithoutMethods;

	// if there are no methods detected, we might generate no test class...
	if ($ignoreClassesWithoutMethods && (count($methods) < 1)) {
		return false;
	}

	// when using a blacklist we might generate no test class for the given class...
	if ($useClassBlacklist) {
		if (in_array(mb_strtolower($className), $ignoreClassesByName)) {
			return false;
		}
	}

	// okay, generate the test class
	return true;
}

/// MAIN
$starttime = microtime(true);

setup();
$objects = scan($pathToProject);

$numberFilesFound 				= 0;
$numberFilesScanned 			= 0;
$numberTestFilesGenerated	 	= 0;
$numberTestMethodsGenerated		= 0;


foreach ($objects as $name => $object) {
	$numberFilesFound++;
	$lower = mb_strtolower($name);
	$split = explode('.', $lower);
	$ext   = array_pop($split);


	// only parse files with extension ".h"
	if ( $ext == 'h' ) {
		$numberFilesScanned++;

		$scanResult = parseDeclarationFromFile($name);
		$classname 	= $scanResult['class'];
		$methods 	= $scanResult['methods'];

		//$classname = scanHeaderFileForClassName($name);

		// TODO: blacklist some filenames or classnames so we do not generate any garbage test cases

		// TODO: add ability to define a text file with property names to base test generation on, like: "- (void)testHas<PropertyName> {}"

		if ( mayGenerateTestClass($classname, $methods) ) {
			$testfile 		= generateTestFile($classname, $methods);
			$testFileName 	= $basePathForTests . $classname . 'Test.m';

			writeToDisk($testFileName, $testfile);

			$numberTestFilesGenerated++;
		}

	}

}


printf("Found %d total files in %s.\nParsed %d of them.\nGenerated %d new TestCases\nwith %d test methods.\n", $numberFilesFound, $pathToProject, $numberFilesScanned, $numberTestFilesGenerated, $numberTestMethodsGenerated);
printf("Finished. Time elapsed: %.8f seconds.\n", microtime(true) - $starttime);

?>
