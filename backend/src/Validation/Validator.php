<?php

declare(strict_types=1);

namespace App\Validation;

use DateTimeImmutable;

/**
 * Lightweight rule-based validator.
 *
 * Usage:
 *   $v = Validator::make($data, [
 *       'name'     => 'required|min:2|max:100',
 *       'email'    => 'required|email',
 *       'password' => 'required|min:8|confirmed',
 *   ]);
 *
 *   if ($v->fails()) {
 *       // $v->errors() → ['field' => 'First error message', ...]
 *   }
 *
 * Supported rules:
 *   required, email, min:n, max:n, numeric, same:otherField, confirmed
 *
 * Custom messages (overrides per field.rule):
 *   Validator::make($data, $rules, ['email.required' => 'We need your email.'])
 */
class Validator
{
    /** @var array<string, list<string>> */
    private array $errors = [];

    /** @param array<string, string> $customMessages */
    private function __construct(
        private readonly array $data,
        private readonly array $rules,
        private readonly array $customMessages = [],
    ) {
    }

    /**
     * @param array<string, string>      $rules
     * @param array<string, string>      $customMessages
     */
    public static function make(array $data, array $rules, array $customMessages = []): self
    {
        $v = new self($data, $rules, $customMessages);
        $v->run();

        return $v;
    }

    public function fails(): bool
    {
        return $this->errors !== [];
    }

    public function passes(): bool
    {
        return $this->errors === [];
    }

    /**
     * First error per field — suitable for simple form display.
     *
     * @return array<string, string>
     */
    public function errors(): array
    {
        return array_map(fn (array $e): string => $e[0], $this->errors);
    }

    /**
     * All errors per field.
     *
     * @return array<string, list<string>>
     */
    public function allErrors(): array
    {
        return $this->errors;
    }

    // ─── Internal ──────────────────────────────────────────────────

    private function run(): void
    {
        foreach ($this->rules as $field => $ruleString) {
            $value = $this->value($field);
            $ruleList = explode('|', $ruleString);

            // If the field is declared nullable and the value is empty, skip all checks
            $str = is_string($value) ? trim($value) : (string) ($value ?? '');
            if (in_array('nullable', $ruleList, true) && $str === '') {
                continue;
            }


            foreach (explode('|', $ruleString) as $rule) {
                [$name, $param] = array_pad(explode(':', $rule, 2), 2, null);

                if ($name === 'nullable') {
                    continue; // pseudo-rule, nothing to check
                }
                // Stop after first failure per field (bail behaviour)
                if (!$this->check($field, $value, $name, $param)) {
                    break;
                }
            }
        }
    }

    private function check(string $field, mixed $value, string $rule, ?string $param): bool
    {
        $str  = is_string($value) ? trim($value) : (string) ($value ?? '');
        $pass = match ($rule) {
            'required'  => $str !== '',
            'email'     => $str === '' || (bool) filter_var($str, FILTER_VALIDATE_EMAIL),
            'url'       => $str === '' || (bool) filter_var($str, FILTER_VALIDATE_URL),
            'min'       => strlen($str) >= (int) $param,
            'max'       => strlen($str) <= (int) $param,
            'numeric'   => is_numeric($str),
            'integer'   => $str === '' || filter_var($str, FILTER_VALIDATE_INT) !== false,
            'between'   => $this->isBetween($str, $param),
            'in'        => in_array($str, explode(',', $param ?? ''), true),
            'same'      => $str === trim((string) ($this->data[$param ?? ''] ?? '')),
            'confirmed' => $str === trim((string) ($this->data[$field . '_confirm'] ?? '')),
            'date'      => $this->isValidDate($str),
            default     => true,
        };

        if (!$pass) {
            $this->errors[$field][] = $this->message($field, $rule, $param);
        }

        return $pass;
    }

    private function isBetween(string $value, ?string $parameters): bool
    {
        if (!is_numeric($value) || $parameters === null) {
            return false;
        }

        [$minimum, $maximum] = array_pad(explode(',', $parameters, 2), 2, null);
        if (!is_numeric($minimum) || !is_numeric($maximum)) {
            return false;
        }

        return (float) $value >= (float) $minimum && (float) $value <= (float) $maximum;
    }
    /**
     * Accepts any string PHP's date parser understands (ISO 8601 with or
     * without time/offset, "Y-m-d\TH:i" from datetime-local inputs, etc.) —
     * deliberately not restricted to one format, since both HTML forms and
     * a JSON API feed this validator.
     */
    private function isValidDate(string $value): bool
    {
        if ($value === '') {
            return true; // emptiness is the 'required' rule's job
        }

        try {
            new DateTimeImmutable($value);

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    private function value(string $field): mixed
    {
        return $this->data[$field] ?? null;
    }

    private function message(string $field, string $rule, ?string $param): string
    {
        // Custom message wins
        $key = "{$field}.{$rule}";
        if (isset($this->customMessages[$key])) {
            return $this->customMessages[$key];
        }

        $label = ucfirst(str_replace('_', ' ', $field));

        return match ($rule) {
            'required'  => "{$label} is required.",
            'email'     => "{$label} must be a valid email address.",
            'url'       => "{$label} must be a valid URL.",
            'min'       => "{$label} must be at least {$param} characters.",
            'max'       => "{$label} may not exceed {$param} characters.",
            'numeric'   => "{$label} must be a number.",
            'integer'   => "{$label} must be a whole number.",
            'between'   => "{$label} must be between " . str_replace(',', ' and ', (string) $param) . '.',
            'in'        => "{$label} must be one of: {$param}.",
            'same'      => "{$label} must match {$param}.",
            'confirmed' => "{$label} confirmation does not match.",
            'date'      => "{$label} must be a date.",
            default     => "{$label} is invalid.",
        };
    }
}
