<?php

declare(strict_types=1);

namespace App\Settings;

class Settings
{
    /**
     * @param array<string, mixed> $settings
     */
    public function __construct(private array $settings)
    {
    }

    /**
     * Get value in setting using dot notations
     *
     * Example: get('db.host')
     *
     * @param string $key key in setting
     * @param mixed|null $default default value if the key does not exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed
    {
        // Direct key
        if (array_key_exists($key, $this->settings)) {
            return $this->settings[$key];
        }

        // Implementation for dot notation (db.host)
        $keys = explode('.', $key);
        $value = $this->settings;

        foreach ($keys as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default; // key was not found
            }
            $value = $value[$segment];
        }

        return $value;
    }
}
