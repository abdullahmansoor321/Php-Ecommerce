<?php

class Validator
{
    private array $data;
    private array $errors = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function validate(array $rules): bool
    {
        foreach ($rules as $field => $ruleString) {
            $ruleList = explode('|', $ruleString);
            $value = trim($this->data[$field] ?? '');

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && $value === '') {
                    $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
                    break;
                }

                if ($value !== '') {
                    if ($rule === 'email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $this->errors[$field] = 'Invalid email address format.';
                        break;
                    }

                    if (str_starts_with($rule, 'min:')) {
                        $min = (int) substr($rule, 4);
                        if (strlen($value) < $min) {
                            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " must be at least {$min} characters.";
                            break;
                        }
                    }

                    if (str_starts_with($rule, 'max:')) {
                        $max = (int) substr($rule, 4);
                        if (strlen($value) > $max) {
                            $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . " cannot exceed {$max} characters.";
                            break;
                        }
                    }

                    if ($rule === 'numeric' && !is_numeric($value)) {
                        $this->errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' must be a number.';
                        break;
                    }
                }
            }
        }

        return empty($this->errors);
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function fails(): bool
    {
        return !empty($this->errors);
    }
}
