<?php

/**
 * @covers JsonValidator
 */
class JsonValidatorTest extends PHPUnit_Framework_TestCase
{
    /**
     * Get mock object
     * 
     * @return stdClass
     */
    protected function getTestObject()
    {
        $o = new stdClass();
        $o->stringProp = "AB";
        $o->arrayProp = array('foo', 'bar');
        $o->numberProp = 1.1;
        $o->integerProp = 1;
        $o->booleanProp = false;
        $o->nullProp = null;
        $o->anyProp = 1;
        $o->multiProp = "foo";
        $o->customProp = 'asdf';
        
        $o->objectProp = new stdClass();
        $o->objectProp->foo = 'bar';
        
        return $o;
    }
    
    /**
     * Get validator object
     * 
     * @return JsonValidator 
     */
    protected function getValidator()
    {
        return new JsonValidator(TEST_DIR . '/mock/test-schema.json');
    }
    
    /**
     * @covers JsonValidator::__construct
     */
    public function testConstruct()
    {
        $v = new JsonValidator(TEST_DIR . '/mock/test-schema.json');
    }

    /**
     * @expectedException RuntimeException
     */
    public function testSchemaNotFound()
    {
        $v = new JsonValidator('asdf');
    }
    
    /**
     * @expectedException JsonSchemaException
     */
    public function testInvalidSchema()
    {
        $v = new JsonValidator(TEST_DIR . '/mock/invalid-schema.json');
    }
    
    /**
     * Test a valid object
     */
    public function testValidObject()
    {
        $o = $this->getTestObject();
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * Test multiple types for property
     */
    public function testMultiProp()
    {
        $o = $this->getTestObject();
        $v = $this->getValidator();
        $v->validate($o);
        
        $o->multiProp = 1234;
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testMissingRequired()
    {
        $o = $this->getTestObject();
        unset($o->stringProp);
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidAdditionalProperties()
    {
        $o = $this->getTestObject();
        $o->foo = 'bar';
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidString()
    {
        $o = $this->getTestObject();
        $o->stringProp = 1234;
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidNumber()
    {
        $o = $this->getTestObject();
        $o->numberProp = 'asdf';
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidInteger()
    {
        $o = $this->getTestObject();
        $o->integerProp = 'asdf';
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidBoolean()
    {
        $o = $this->getTestObject();
        $o->booleanProp = 'asdf';
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidArray()
    {
        $o = $this->getTestObject();
        $o->arrayProp = 'asdf';
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidNull()
    {
        $o = $this->getTestObject();
        $o->nullProp = 'asdf';
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidObject()
    {
        $o = $this->getTestObject();
        $o->objectProp = 'asdf';
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidMinimum()
    {
        $o = $this->getTestObject();
        $o->numberProp = 0;
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidMaximum()
    {
        $o = $this->getTestObject();
        $o->numberProp = 100;
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidPattern()
    {
        $o = $this->getTestObject();
        $o->stringProp = "1234";
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidMinLength()
    {
        $o = $this->getTestObject();
        $o->stringProp = "a";
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidMaxLength()
    {
        $o = $this->getTestObject();
        $o->stringProp = "abcd";
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidMinItems()
    {
        $o = $this->getTestObject();
        $o->arrayProp = array();
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidMaxItems()
    {
        $o = $this->getTestObject();
        $o->arrayProp = array('a', 'b', 'c', 'd');
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidUniqueItems()
    {
        $o = $this->getTestObject();
        $o->arrayProp = array('a', 'a');
        $v = $this->getValidator();
        $v->validate($o);
    }
    
    /**
     * @expectedException JsonValidationException
     */
    public function testInvalidEnum()
    {
        $o = $this->getTestObject();
        $o->arrayProp = array('foo', 'blah');
        $v = $this->getValidator();
        $v->validate($o);
    }
}