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
 *   required, email, min:n, max:n, numeric, same:otherField, confirmed, regex:/pattern/
 *
 * min/max count characters (UTF-8), not bytes.
 * regex: the pattern must not contain "|" – it is the rule separator.
 * datetime_offset: ISO 8601 date-time WITH a timezone offset (`…Z` or `…+02:00`), e.g. from JS toISOString().
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

            // every rule works on one text value – an array/object (e.g. "title": []) is rejected,
            // a (string) cast would only produce a warning and a 500
            if (!$this->isScalarOrNull($value)) {
                $this->errors[$field][] = $this->message($field, 'scalar', null);
                continue;
            }

            // If the field is declared nullable and the value is empty, skip all checks
            $str = $this->stringValue($value);
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
        $str  = $this->stringValue($value);
        $pass = match ($rule) {
            'required'  => $str !== '',
            'email'     => $str === '' || (bool) filter_var($str, FILTER_VALIDATE_EMAIL),
            'url'       => $str === '' || (bool) filter_var($str, FILTER_VALIDATE_URL),
            'min'       => mb_strlen($str) >= (int) $param,
            'max'       => mb_strlen($str) <= (int) $param,
            'regex'     => $str === '' || preg_match((string) $param, $str) === 1,
            'numeric'   => is_numeric($str),
            'integer'   => $str === '' || filter_var($str, FILTER_VALIDATE_INT) !== false,
            'between'   => $this->isBetween($str, $param),
            'in'        => in_array($str, explode(',', $param ?? ''), true),
            'same'      => $str === $this->stringValue($this->data[$param ?? ''] ?? null),
            'confirmed' => $str === $this->stringValue($this->data[$field . '_confirm'] ?? null),
            'date'      => $this->isValidDate($str),
            'datetime_offset' => $this->isDateTimeWithOffset($str),
            default     => true,
        };

        if (!$pass) {
            $this->errors[$field][] = $this->message($field, $rule, $param);
        }

        return $pass;
    }

    private function isScalarOrNull(mixed $value): bool
    {
        return $value === null || is_scalar($value);
    }

    /** Trimmed text form of a value; non-scalar values (only possible for other fields) become ''. */
    private function stringValue(mixed $value): string
    {
        return $this->isScalarOrNull($value) ? trim((string) $value) : '';
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
    /**
     * Without an explicit offset the server's default timezone would silently be used,
     * so `2026-10-18T10:00` is rejected while `2026-10-18T10:00:00.000Z` / `…+02:00` pass (FR-WEEK-04).
     */
    private function isDateTimeWithOffset(string $value): bool
    {
        if ($value === '') {
            return true; // emptiness is the 'required' rule's job
        }

        $pattern = '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?(Z|[+-]\d{2}:?\d{2})$/';

        return preg_match($pattern, $value) === 1 && $this->isValidDate($value);
    }

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
            'regex'     => "{$label} format is invalid.",
            'datetime_offset' => "{$label} must be an ISO 8601 date-time with a timezone offset.",
            'scalar'    => "{$label} must be a single value, not a list or an object.",
            default     => "{$label} is invalid.",
        };
    }
}
