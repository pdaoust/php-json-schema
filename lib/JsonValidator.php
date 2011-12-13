<?php

class JsonValidationException extends Exception {};
class JsonSchemaException extends Exception {};

/**
 * JSON Schema Validator
 * 
 * Implements schema draft version 03, as defined at http://json-schema.org
 * 
 * @author Harold Asbridge <hasbridge@gmail.com>
 * @version 0.1
 */
class JsonValidator
{
    protected $schemaDefinition;
    
    /**
     * @var stdClass
     */
    protected $schema;
    
    /**
     * Initialize validation object
     * 
     * @param string $schemaFile 
     */
    public function __construct($schemaFile)
    {
        if (!file_exists($schemaFile)) {
            throw new RuntimeException('Schema file not found');
        }
        $data = file_get_contents($schemaFile);
        $this->schema = json_decode($data);
        
        if ($this->schema === null) {
            throw new JsonSchemaException('Unable to parse JSON data - syntax error?');
        }
        
        // @TODO - validate schema itself
    }
    
    /**
     * Validate schema object
     * 
     * @param mixed $entity
     * @param string $entityName
     * 
     * @return JsonValidator
     */
    public function validate($entity, $entityName = null)
    {
        $entityName = $entityName ?: 'root';
        
        // Validate root type
        $this->validateType($entity, $this->schema, $entityName);
        
        return $this;
    }
    
    /**
     * Validate object properties
     * 
     * @param object $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator
     */
    protected function validateProperties($entity, $schema, $entityName)
    {
        $properties = get_object_vars($entity);
        
        // Check defined properties
        foreach($schema->properties as $propertyName => $property) {
            if (array_key_exists($propertyName, $properties)) {
                // Check type
                $path = $entityName . '.' . $propertyName;
                $this->validateType($entity->{$propertyName}, $property, $path);
            } else {
                // Check required
                if (isset($property->required) && $property->required) {
                    throw new JsonValidationException(sprintf('Missing required property [%s] for [%s]', $propertyName, $entityName));
                }
            }
        }
        
        // Check additional properties
        if (isset($schema->additionalProperties) && !$schema->additionalProperties) {
            $extra = array_diff(array_keys((array)$entity), array_keys((array)$schema->properties));
            if (count($extra)) {
                throw new JsonValidationException(sprintf('Additional properties [%s] not allowed for property [%s]', implode(',', $extra), $entityName));
            }
        }
        
        return $this;
    }

    /**
     * Validate entity type
     * 
     * @param mixed $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator
     */
    protected function validateType($entity, $schema, $entityName)
    {
        $types = $schema->type;
        if (!is_array($types)) {
            $types = array($types);
        }
        
        $valid = false;
        
        foreach($types as $type) {
            switch($type) {
                case 'object':
                    if (is_object($entity)) {
                        $this->checkTypeObject($entity, $schema, $entityName);
                        $valid = true;
                    }
                    break;
                case 'string':
                    if (is_string($entity)) {
                        $this->checkTypeString($entity, $schema, $entityName);
                        $valid = true;
                    }
                    break;
                case 'array':
                    if (is_array($entity)) {
                        $this->checkTypeArray($entity, $schema, $entityName);
                        $valid = true;
                    }
                    break;
                case 'number':
                    if (is_numeric($entity)) {
                        $this->checkTypeNumber($entity, $schema, $entityName);
                        $valid = true;
                    }
                    break;
                case 'integer':
                    if (is_int($entity)) {
                        $this->checkTypeInteger($entity, $schema, $entityName);
                        $valid = true;
                    }
                    break;
                case 'boolean':
                    if (is_bool($entity)) {
                        $this->checkTypeBoolean($entity, $schema, $entityName);
                        $valid = true;
                    }
                    break;
                case 'null':
                    if (is_null($entity)) {
                        $this->checkTypeNull($entity, $schema, $entityName);
                        $valid = true;
                    }
                    break;
                default:
                case 'any':
                    // Do nothing
                    $valid = true;
                    break;
            }
        }
        
        if (!$valid) {
            throw new JsonValidationException(sprintf('Property [%s] must be one of the following types: [%s]', $entityName, implode(', ', $types)));
        }
        
        return $this;
    }
    
    
    /**
     * Check object type
     * 
     * @param mixed $entity
     * @param object $schema
     * @param string $entityName 
     * 
     * @return JsonValidator
     */
    protected function checkTypeObject($entity, $schema, $entityName) 
    {
        $this->validateProperties($entity, $schema, $entityName);
        
        return $this;
    }
    
    /**
     * Check number type
     * 
     * @param mixed $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkTypeNumber($entity, $schema, $entityName)
    {
        $this->checkMinimum($entity, $schema, $entityName);
        $this->checkMaximum($entity, $schema, $entityName);
        
        return $this;
    }
    
    /**
     * Check integer type
     * 
     * @param mixed $entity
     * @param object $schema
     * @param string $entityName 
     * 
     * @return JsonValidator
     */
    protected function checkTypeInteger($entity, $schema, $entityName)
    {
        $this->checkMinimum($entity, $schema, $entityName);
        $this->checkMaximum($entity, $schema, $entityName);
        
        return $this;
    }
    
    /**
     * Check boolean type
     * 
     * @param mixed $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkTypeBoolean($entity, $schema, $entityName)
    {
        return $this;
    }
    
    /**
     * Check string type
     * 
     * @param mixed $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator
     */
    protected function checkTypeString($entity, $schema, $entityName)
    {
        $this->checkPattern($entity, $schema, $entityName);
        $this->checkMinLength($entity, $schema, $entityName);
        $this->checkMaxLength($entity, $schema, $entityName);
        
        return $this;
    }
    
    /**
     * Check array type
     * 
     * @param mixed $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkTypeArray($entity, $schema, $entityName)
    {
        $this->checkMinItems($entity, $schema, $entityName);
        $this->checkMaxItems($entity, $schema, $entityName);
        $this->checkUniqueItems($entity, $schema, $entityName);
        $this->checkEnum($entity, $schema, $entityName);
        
        return $this;
    }
    
    /**
     * Check null type
     * 
     * @param mixed $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkTypeNull($entity, $schema, $entityName)
    {
        return $this;
    }
    
    /**
     * Check minimum value
     * 
     * @param int|float $entity
     * @param object $schema
     * @param string $entityName 
     * 
     * @return JsonValidator
     */
    protected function checkMinimum($entity, $schema, $entityName)
    {
        if (isset($schema->minimum) && $schema->minimum) {
            if ($entity < $schema->minimum) {
                throw new JsonValidationException(sprintf('Invalid value for [%s], minimum is [%s]', $entityName, $schema->minimum));
            }
        }
        
        return $this;
    }
    
    /**
     * Check maximum value
     * 
     * @param int|float $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkMaximum($entity, $schema, $entityName)
    {
        if (isset($schema->maximum) && $schema->maximum) {
            if ($entity > $schema->maximum) {
                throw new JsonValidationException(sprintf('Invalid value for [%s], maximum is [%s]', $entityName, $schema->maximum));
            }
        }
        
        return $this;
    }
    
    /**
     * Check value against regex pattern
     * 
     * @param string $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkPattern($entity, $schema, $entityName)
    {
        if (isset($schema->pattern) && $schema->pattern) {
            if (!preg_match($schema->pattern, $entity)) {
                throw new JsonValidationException(sprintf('String does not match pattern for [%s]', $entityName));
            }
        }
        
        return $this;
    }
    
    /**
     * Check string minimum length
     * 
     * @param string $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkMinLength($entity, $schema, $entityName)
    {
        if (isset($schema->minLength) && $schema->minLength) {
            if (strlen($entity) < $schema->minLength) {
                throw new JsonValidationException(sprintf('String too short for [%s], minimum length is [%s]', $entityName, $schema->minLength));
            }
        }
        
        return $this;
    }
    
    /**
     * Check string maximum length
     * 
     * @param string $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkMaxLength($entity, $schema, $entityName)
    {
        if (isset($schema->maxLength) && $schema->maxLength) {
            if (strlen($entity) > $schema->maxLength) {
                throw new JsonValidationException(sprintf('String too long for [%s], maximum length is [%s]', $entityName, $schema->maxLength));
            }
        }
        
        return $this;
    }
    
    /**
     * Check array minimum items
     * 
     * @param array $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkMinItems($entity, $schema, $entityName)
    {
        if (isset($schema->minItems) && $schema->minItems) {
            if (count($entity) < $schema->minItems) {
                throw new JsonValidationException(sprintf('Not enough array items for [%s], minimum is [%s]', $entityName, $schema->minItems));
            }
        }
        
        return $this;
    }
    
    /**
     * Check array maximum items
     * 
     * @param array $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkMaxItems($entity, $schema, $entityName)
    {
        if (isset($schema->maxItems) && $schema->maxItems) {
            if (count($entity) > $schema->maxItems) {
                throw new JsonValidationException(sprintf('Too many array items for [%s], maximum is [%s]', $entityName, $schema->maxItems));
            }
        }
        
        return $this;
    }
    
    /**
     * Check array unique items
     * 
     * @param array $entity
     * @param object $schema
     * @param string $entityName
     * 
     * @return JsonValidator 
     */
    protected function checkUniqueItems($entity, $schema, $entityName)
    {
        if (isset($schema->uniqueItems) && $schema->uniqueItems) {
            if (count(array_unique($entity)) != count($entity)) {
                throw new JsonValidationException(sprintf('All items in array [%s] must be unique', $entityName));
            }
        }
        
        return $this;
    }
    
    protected function checkEnum($entity, $schema, $entityName)
    {
        if (isset($schema->enum) && $schema->enum) {
            foreach($entity as $val) {
                if (!in_array($val, $schema->enum)) {
                    throw new JsonValidationException(sprintf('Invalid value(s) for [%s], allowable values are [%s]', $entityName, implode(',', $schema->enum)));
                }
            }
        }
    }
}