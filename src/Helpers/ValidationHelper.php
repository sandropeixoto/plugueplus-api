<?php
declare(strict_types=1);

namespace App\Helpers;

class ValidationHelper
{
    /**
     * Basic validation utility.
     *
     * Rules example:
     * [
     *   'email' => ['required', 'email'],
     *   'password' => ['required', 'min:6']
     * ]
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;

            foreach ($fieldRules as $rule) {
                if ($rule === 'required' && (is_null($value) || $value === '')) {
                    $errors[$field][] = 'Campo obrigatório.';
                }

                if ($rule === 'email' && !is_null($value) && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Email inválido.';
                }

                if (str_starts_with($rule, 'min:')) {
                    $min = (int) substr($rule, 4);
                    if (!is_null($value) && strlen((string) $value) < $min) {
                        $errors[$field][] = "Mínimo de {$min} caracteres.";
                    }
                }
            }
        }

        return $errors;
    }
}
