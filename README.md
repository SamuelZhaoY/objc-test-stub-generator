# objc-test-stub-generator
Obj-C Test-Stub generator for automatically generating XCTestCase classes for Objective-C class declarations

This tool scans a given directory for *.h files and automatically generates XCTestCase classes from the class declarations found inside each file.

It simply looks into each file for the first Objective-C class declaration, takes the class name to name the generated XCTestCase class after it and scans for declared public instance-methods to generate one test-method for each one declared.

Generated XCTestCases are written out into files with equal name.


You simply need to configure the pathToProject, projectName, basePathForTests settings.
The rest can be left as-is, as starting point.

# configuration
Setup where to scan for files and the name of the project

    $pathToProject  = realpath('.');
    $projectName    = 'testProject';

base path for creating the files with the generated test cases

    $basePathForTests	= './Tests/';

integrate imports for OCMock and OCMockObject usage?

    $useOCMock 		= false;
    $useOCMockObject	= false;

Blacklisting, to avoid generating unnecessary test classes:
    if you want to avoid generating test cases for classes without methods.
    
    $ignoreClassesWithoutMethods = true;

    list of lower case class names we will generate no test class for.
    
    $useClassBlacklist   = false;
    $ignoreClassesByName = array(
    	    'appdelegate',
    );

