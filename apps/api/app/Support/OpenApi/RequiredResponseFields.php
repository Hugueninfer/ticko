<?php

declare(strict_types=1);

namespace App\Support\OpenApi;

use Knuckles\Scribe\Attributes\ResponseField;
use ReflectionClass;
use ReflectionMethod;

/**
 * Scribe lists as "required" only the response fields described with
 * #[ResponseField]. Our resources always send every key, so this marks every
 * response property as required except the ones a resource declares with
 * `required: false` (keys sent only by some endpoints). That lets the
 * generated TypeScript types be strict without annotating every field.
 */
final readonly class RequiredResponseFields
{
    /**
     * @param  list<string>  $optional  Field paths relative to the resource, e.g. "requester.email".
     */
    public function __construct(private array $optional) {}

    /**
     * Collects the optional fields declared on every resource in app/Http/Resources.
     */
    public static function fromResources(): self
    {
        $optional = [];

        foreach (glob(app_path('Http/Resources/*.php')) ?: [] as $file) {
            $class = 'App\\Http\\Resources\\'.basename($file, '.php');

            if (! class_exists($class) || (new ReflectionClass($class))->isAbstract() || ! method_exists($class, 'toArray')) {
                continue;
            }

            foreach ((new ReflectionMethod($class, 'toArray'))->getAttributes(ResponseField::class) as $attribute) {
                $field = $attribute->newInstance();

                if ($field->required === false) {
                    $optional[] = $field->name;
                }
            }
        }

        return new self(array_values(array_unique($optional)));
    }

    /**
     * @param  array<string, mixed>  $spec
     * @return array<string, mixed>
     */
    public function apply(array $spec): array
    {
        $paths = $spec['paths'] ?? null;

        if (! is_array($paths)) {
            return $spec;
        }

        foreach ($paths as $path => $operations) {
            if (! is_array($operations)) {
                continue;
            }

            foreach ($operations as $method => $operation) {
                if (is_array($operation) && is_array($operation['responses'] ?? null)) {
                    $operation['responses'] = $this->responses($operation['responses']);
                    $operations[$method] = $operation;
                }
            }

            $paths[$path] = $operations;
        }

        $spec['paths'] = $paths;

        return $spec;
    }

    /**
     * @param  array<mixed>  $responses
     * @return array<mixed>
     */
    private function responses(array $responses): array
    {
        foreach ($responses as $status => $response) {
            $schema = $this->schemaOf($response);

            if (! is_array($response) || $schema === null || ! str_starts_with((string) $status, '2')) {
                continue;
            }

            $response['content'] = ['application/json' => ['schema' => $this->walk($schema, null)]];
            $responses[$status] = $response;
        }

        return $responses;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function schemaOf(mixed $response): ?array
    {
        if (! is_array($response) || ! is_array($response['content'] ?? null)) {
            return null;
        }

        $json = $response['content']['application/json'] ?? null;
        $schema = is_array($json) ? ($json['schema'] ?? null) : null;

        /** @var array<string, mixed>|null */
        return is_array($schema) ? $schema : null;
    }

    /**
     * @param  array<string, mixed>  $schema
     * @param  string|null  $path  Path relative to the "data" wrapper; null outside it.
     * @return array<string, mixed>
     */
    private function walk(array $schema, ?string $path): array
    {
        if (is_array($schema['items'] ?? null)) {
            /** @var array<string, mixed> $items */
            $items = $schema['items'];
            $schema['items'] = $this->walk($items, $path);
        }

        $properties = $schema['properties'] ?? null;

        if (! is_array($properties)) {
            return $schema;
        }

        $required = [];

        foreach ($properties as $name => $property) {
            $name = (string) $name;
            $child = match (true) {
                $path === null && $name === 'data' => '',
                $path === null => null,
                $path === '' => $name,
                default => $path.'.'.$name,
            };

            if (is_array($property)) {
                /** @var array<string, mixed> $property */
                $properties[$name] = $this->walk($property, $child);
            }

            if ($child === null || $child === '' || ! $this->isOptional($child)) {
                $required[] = $name;
            }
        }

        $schema['properties'] = $properties;
        $schema['required'] = $required;

        return $schema;
    }

    private function isOptional(string $path): bool
    {
        foreach ($this->optional as $name) {
            if ($path === $name || str_ends_with($path, '.'.$name)) {
                return true;
            }
        }

        return false;
    }
}
