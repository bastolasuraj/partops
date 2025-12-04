<?php
declare(strict_types=1);

namespace PartOps\Services;

class Validator
{
    private array $errors = [];
    private array $data = [];

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function required(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? null;
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            $this->errors[$field] = $message ?: "$field is required";
        }
        return $this;
    }

    public function email(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        if ($value && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$field] = $message ?: "$field must be a valid email";
        }
        return $this;
    }

    public function numeric(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !is_numeric($value)) {
            $this->errors[$field] = $message ?: "$field must be numeric";
        }
        return $this;
    }

    public function min(string $field, int $min, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        if (is_numeric($value) && (float)$value < $min) {
            $this->errors[$field] = $message ?: "$field must be at least $min";
        }
        return $this;
    }

    public function max(string $field, int $max, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        if (is_numeric($value) && (float)$value > $max) {
            $this->errors[$field] = $message ?: "$field must be at most $max";
        }
        return $this;
    }

    public function minLength(string $field, int $min, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        if (strlen($value) < $min) {
            $this->errors[$field] = $message ?: "$field must be at least $min characters";
        }
        return $this;
    }

    public function maxLength(string $field, int $max, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        if (strlen($value) > $max) {
            $this->errors[$field] = $message ?: "$field must be at most $max characters";
        }
        return $this;
    }

    public function in(string $field, array $allowed, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        if ($value !== '' && !in_array($value, $allowed, true)) {
            $this->errors[$field] = $message ?: "$field must be one of: " . implode(', ', $allowed);
        }
        return $this;
    }

    public function slug(string $field, string $message = ''): self
    {
        $value = $this->data[$field] ?? '';
        if ($value && !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $value)) {
            $this->errors[$field] = $message ?: "$field must be a valid slug (lowercase, hyphens only)";
        }
        return $this;
    }

    public function passes(): bool
    {
        return empty($this->errors);
    }

    public function fails(): bool
    {
        return !$this->passes();
    }

    public function errors(): array
    {
        return $this->errors;
    }

    public function firstError(): ?string
    {
        return reset($this->errors) ?: null;
    }
}
