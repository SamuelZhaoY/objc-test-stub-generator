# objc-test-stub-generator
Obj-C Test-Stub generator for automatically generating XCTestCase classes for Objective-C class declarations

This tool scans a given directory for *.h files and automatically generates XCTestCase classes from the class declarations found inside each file.

It simply looks into each file for the first Objective-C class declaration, takes the class name to name the generated XCTestCase class after it and scans for declared public instance-methods to generate one test-method for each one declared.

Generated XCTestCases are written out into files with equal name.
