<?php

namespace App\Utils;

class Validator {
    private $errors = [];
    private $data = [];

    public function validate($data, $rules) {
        $this->data = $data;
        $this->errors = [];

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                $this->validateRule($field, $rule);
            }
        }

        return $this;
    }

    private function validateRule($field, $rule) {
        // Rozdziel regułę na nazwę i parametry
        $parts = explode(':', $rule);
        $ruleName = $parts[0];
        $parameter = $parts[1] ?? null;

        $value = $this->data[$field] ?? null;

        switch ($ruleName) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    $this->addError($field, "Pole {$field} jest wymagane.");
                }
                break;

            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $this->addError($field, "Pole {$field} musi być prawidłowym adresem email.");
                }
                break;

            case 'min':
                if (!empty($value)) {
                    if (is_string($value) && strlen($value) < $parameter) {
                        $this->addError($field, "Pole {$field} musi mieć minimum {$parameter} znaków.");
                    }
                }
                break;

            case 'max':
                if (!empty($value)) {
                    if (is_string($value) && strlen($value) > $parameter) {
                        $this->addError($field, "Pole {$field} może mieć maksymalnie {$parameter} znaków.");
                    }
                }
                break;

            case 'same':
                if ($value !== ($this->data[$parameter] ?? null)) {
                    $this->addError($field, "Pole {$field} musi być takie samo jak pole {$parameter}.");
                }
                break;

            case 'date':
                if (!empty($value)) {
                    $date = \DateTime::createFromFormat('Y-m-d', $value);
                    if (!$date || $date->format('Y-m-d') !== $value) {
                        $this->addError($field, "Pole {$field} musi być prawidłową datą.");
                    }
                }
                break;

            case 'age':
                if (!empty($value)) {
                    $birthDate = new \DateTime($value);
                    $today = new \DateTime();
                    $age = $today->diff($birthDate)->y;
                    if ($age < $parameter) {
                        $this->addError($field, "Musisz mieć minimum {$parameter} lat.");
                    }
                }
                break;

            case 'in':
                if (!empty($value)) {
                    $allowedValues = explode(',', $parameter);
                    if (!in_array($value, $allowedValues)) {
                        $this->addError($field, "Wybrana wartość dla pola {$field} jest nieprawidłowa.");
                    }
                }
                break;
        }
    }

    private function addError($field, $message) {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
    }

    public function fails() {
        return !empty($this->errors);
    }

    public function getErrors() {
        return $this->errors;
    }

    public function getFirstError() {
        foreach ($this->errors as $fieldErrors) {
            if (!empty($fieldErrors)) {
                return reset($fieldErrors);
            }
        }
        return null;
    }
}
