<?php

declare(strict_types=1);

namespace App\Validation;

/**
 * Laravel-style Form Request.
 *
 * Extend this class, define rules(), and pass raw POST data in the constructor.
 * The object validates itself immediately and exposes the result.
 *
 * Example:
 *   class CreateUserRequest extends FormRequest {
 *       protected function rules(): array {
 *           return [
 *               'name'     => 'required|min:2|max:100',
 *               'email'    => 'required|email',
 *               'password' => 'required|min:8|confirmed',
 *           ];
 *       }
 *   }
 *
 *   // In controller:
 *   $req = new CreateUserRequest((array) $request->getParsedBody());
 *   if ($req->fails()) {
 *       return $this->render(..., ['errors' => $req->errors(), 'old' => $req->all()]);
 *   }
 *   $this->service->create($req->safe());
 */
abstract class FormRequest
{
    private readonly Validator $validator;

    public function __construct(private array $data)
    {
        $this->data = $this->sanitize($data);
        $this->validator = Validator::make($data, $this->rules(), $this->messages());
    }

    /** Validation rules. */
    abstract protected function rules(): array;

    /** Override to provide custom error messages. */
    protected function messages(): array
    {
        return [];
    }

    public function fails(): bool
    {
        return $this->validator->fails();
    }

    public function passes(): bool
    {
        return $this->validator->passes();
    }

    /** @return array<string, string> First error per field. */
    public function errors(): array
    {
        return $this->validator->errors();
    }

    /** All raw input. */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Only the fields covered by rules() — safe to pass directly to a service.
     *  + these which are null
     *
     * @return array<string, mixed>
     */
    public function safe(): array
    {
        $safeData = [];
        foreach (array_keys($this->rules()) as $field) {
            // if key is not in data, we still want to include it with null value
            $safeData[$field] = $this->data[$field] ?? null;
        }

        return $safeData;
        //        return array_intersect_key($this->data, $this->rules());
    }

    /** Read a single value. */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /** Magic getter: $req->name */
    public function __get(string $name): mixed
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Recursively traverses all data and trims whitespaces from strings.
     * Optionally, you can convert empty strings directly to null here.
     */
    private function sanitize(array $data): array
    {
        $sanitized = [];

        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $trimmed = trim($value);
                // Often useful: $sanitized[$key] = $trimmed === '' ? null : $trimmed;
                $sanitized[$key] = $trimmed;
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitize($value); //recursion
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}
