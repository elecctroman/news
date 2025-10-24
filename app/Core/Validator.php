<?php
namespace App\Core;

class Validator
{
    public function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $ruleList) {
            $value = trim((string)($data[$field] ?? ''));

            foreach ($ruleList as $rule) {
                if ($rule === 'required' && $value === '') {
                    $errors[$field][] = 'Bu alan zorunludur.';
                }

                if ($rule === 'email' && $value !== '' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[$field][] = 'Geçerli bir e-posta adresi giriniz.';
                }
            }
        }

        return $errors;
    }

    public function minLength(string $value, int $length): bool
    {
        return mb_strlen(trim($value)) >= $length;
    }
}
