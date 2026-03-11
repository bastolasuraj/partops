<?php
namespace App\Core;

abstract class BaseController
{
    protected Request $request;
    
    public function __construct()
    {
        $this->request = new Request();
    }
    
    protected function validate(array $rules): array
    {
        $data = $this->request->all();
        $errors = [];
        
        foreach ($rules as $field => $ruleString) {
            $fieldRules = explode('|', $ruleString);
            $value = $data[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
                $data[$field] = $value;
            }

            $isOptionalBlank = $value === '';
            if ($isOptionalBlank && !in_array('required', $fieldRules, true)) {
                $value = null;
                $data[$field] = null;
            }
            
            foreach ($fieldRules as $rule) {
                $ruleParts = explode(':', $rule);
                $ruleName = $ruleParts[0];
                $ruleParam = $ruleParts[1] ?? null;
                
                switch ($ruleName) {
                    case 'required':
                        if (empty($value) && $value !== '0' && $value !== 0) {
                            $errors[$field][] = "{$field} is required";
                        }
                        break;
                        
                    case 'string':
                        if ($value !== null && !is_string($value)) {
                            $errors[$field][] = "{$field} must be a string";
                        }
                        break;
                        
                    case 'numeric':
                        if ($value !== null && !is_numeric($value)) {
                            $errors[$field][] = "{$field} must be numeric";
                        }
                        break;
                        
                    case 'integer':
                        if ($value !== null && !filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field][] = "{$field} must be an integer";
                        }
                        break;
                        
                    case 'email':
                        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field][] = "{$field} must be a valid email";
                        }
                        break;
                        
                    case 'min':
                        if (is_string($value) && strlen($value) < (int)$ruleParam) {
                            $errors[$field][] = "{$field} must be at least {$ruleParam} characters";
                        } elseif (is_numeric($value) && $value < (int)$ruleParam) {
                            $errors[$field][] = "{$field} must be at least {$ruleParam}";
                        }
                        break;
                        
                    case 'max':
                        if (is_string($value) && strlen($value) > (int)$ruleParam) {
                            $errors[$field][] = "{$field} must not exceed {$ruleParam} characters";
                        } elseif (is_numeric($value) && $value > (int)$ruleParam) {
                            $errors[$field][] = "{$field} must not exceed {$ruleParam}";
                        }
                        break;
                }
            }
        }
        
        if (!empty($errors)) {
            Response::error('Validation failed', 422, $errors);
        }
        
        return $data;
    }
}
