<?php

declare(strict_types=1);

namespace TestFixtures\ModernPHP;

/**
 * Demonstrates PHP 8.0+ match expressions
 */
class MatchExpressions
{
    // Simple match expression
    public function getStatusMessage(string $status): string
    {
        return match ($status) {
            'pending' => 'Operation is pending',
            'success' => 'Operation completed successfully',
            'error' => 'Operation failed',
            'cancelled' => 'Operation was cancelled',
        };
    }
    
    // Match with default arm
    public function getHttpMessage(int $code): string
    {
        return match ($code) {
            200 => 'OK',
            201 => 'Created',
            204 => 'No Content',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            500 => 'Internal Server Error',
            default => 'Unknown Status Code',
        };
    }
    
    // Match with multiple conditions
    public function categorizeNumber(int $number): string
    {
        return match (true) {
            $number === 0 => 'zero',
            $number === 1, $number === -1 => 'unit',
            $number > 0 && $number < 10 => 'single digit positive',
            $number < 0 && $number > -10 => 'single digit negative',
            $number >= 10 && $number < 100 => 'double digit positive',
            $number <= -10 && $number > -100 => 'double digit negative',
            default => 'large number',
        };
    }
    
    // Match with complex expressions
    public function calculateDiscount(string $customerType, float $amount): float
    {
        $discount = match ($customerType) {
            'vip' => match (true) {
                $amount >= 1000 => 0.20,
                $amount >= 500 => 0.15,
                $amount >= 100 => 0.10,
                default => 0.05,
            },
            'regular' => match (true) {
                $amount >= 1000 => 0.10,
                $amount >= 500 => 0.05,
                default => 0.0,
            },
            'new' => 0.15,
            default => 0.0,
        };
        
        return $amount * (1 - $discount);
    }
    
    // Type checking in match
    public function processValue(mixed $value): string
    {
        return match (true) {
            is_null($value) => 'null value',
            is_bool($value) => 'boolean: ' . ($value ? 'true' : 'false'),
            is_int($value) => 'integer: ' . $value,
            is_float($value) => 'float: ' . $value,
            is_string($value) => 'string: ' . $value,
            is_array($value) => 'array with ' . count($value) . ' elements',
            is_object($value) => 'object of class ' . get_class($value),
            is_callable($value) => 'callable',
            default => 'unknown type',
        };
    }
    
    // Match with object types
    public function handleException(\Throwable $exception): string
    {
        return match (get_class($exception)) {
            \InvalidArgumentException::class => 'Invalid argument provided',
            \RuntimeException::class => 'Runtime error occurred',
            \LogicException::class => 'Logic error in the code',
            \DomainException::class => 'Domain-specific error',
            \TypeError::class => 'Type error detected',
            \ParseError::class => 'Parse error in code',
            default => 'Unknown exception: ' . $exception->getMessage(),
        };
    }
    
    // Match with instanceof checks
    public function describeObject(object $obj): string
    {
        return match (true) {
            $obj instanceof \DateTime => 'DateTime: ' . $obj->format('Y-m-d H:i:s'),
            $obj instanceof \DateTimeImmutable => 'DateTimeImmutable: ' . $obj->format('Y-m-d H:i:s'),
            $obj instanceof \DateInterval => 'DateInterval: ' . $obj->format('%R%a days'),
            $obj instanceof \Countable => 'Countable with ' . count($obj) . ' items',
            $obj instanceof \Iterator => 'Iterator object',
            $obj instanceof \ArrayAccess => 'ArrayAccess object',
            $obj instanceof \Stringable => 'Stringable: ' . (string)$obj,
            default => 'Generic object of class ' . get_class($obj),
        };
    }
    
    // Match with array destructuring
    public function processCoordinate(array $coordinate): string
    {
        return match (count($coordinate)) {
            2 => match (true) {
                isset($coordinate['x'], $coordinate['y']) => "2D point at ({$coordinate['x']}, {$coordinate['y']})",
                isset($coordinate[0], $coordinate[1]) => "2D point at ({$coordinate[0]}, {$coordinate[1]})",
                default => 'Invalid 2D coordinate format',
            },
            3 => match (true) {
                isset($coordinate['x'], $coordinate['y'], $coordinate['z']) => "3D point at ({$coordinate['x']}, {$coordinate['y']}, {$coordinate['z']})",
                isset($coordinate[0], $coordinate[1], $coordinate[2]) => "3D point at ({$coordinate[0]}, {$coordinate[1]}, {$coordinate[2]})",
                default => 'Invalid 3D coordinate format',
            },
            default => 'Invalid coordinate dimensions',
        };
    }
    
    // Match with method calls
    public function formatDate(\DateTimeInterface $date, string $format): string
    {
        return match ($format) {
            'short' => $date->format('Y-m-d'),
            'long' => $date->format('l, F j, Y'),
            'iso' => $date->format(\DateTime::ATOM),
            'rfc' => $date->format(\DateTime::RFC2822),
            'time' => $date->format('H:i:s'),
            'datetime' => $date->format('Y-m-d H:i:s'),
            default => $date->format($format),
        };
    }
    
    // Match with enum (PHP 8.1+)
    public function getColorCode(Color $color): string
    {
        return match ($color) {
            Color::RED => '#FF0000',
            Color::GREEN => '#00FF00',
            Color::BLUE => '#0000FF',
            Color::YELLOW => '#FFFF00',
            Color::BLACK => '#000000',
            Color::WHITE => '#FFFFFF',
        };
    }
    
    // Nested match expressions
    public function calculateShipping(string $country, string $shippingType, float $weight): float
    {
        return match ($country) {
            'US' => match ($shippingType) {
                'standard' => match (true) {
                    $weight <= 1 => 5.00,
                    $weight <= 5 => 10.00,
                    $weight <= 10 => 15.00,
                    default => 20.00 + ($weight - 10) * 1.50,
                },
                'express' => match (true) {
                    $weight <= 1 => 15.00,
                    $weight <= 5 => 25.00,
                    default => 35.00 + ($weight - 5) * 3.00,
                },
                default => throw new \InvalidArgumentException('Invalid shipping type'),
            },
            'CA' => match ($shippingType) {
                'standard' => $weight * 3.50,
                'express' => $weight * 7.00,
                default => throw new \InvalidArgumentException('Invalid shipping type'),
            },
            'EU' => match ($shippingType) {
                'standard' => 10.00 + $weight * 2.00,
                'express' => 25.00 + $weight * 4.00,
                default => throw new \InvalidArgumentException('Invalid shipping type'),
            },
            default => throw new \InvalidArgumentException('Shipping not available for this country'),
        };
    }
    
    // Match with arrow functions
    public function transformValue(mixed $value, string $operation): mixed
    {
        return match ($operation) {
            'double' => fn() => is_numeric($value) ? $value * 2 : null,
            'square' => fn() => is_numeric($value) ? $value ** 2 : null,
            'uppercase' => fn() => is_string($value) ? strtoupper($value) : null,
            'lowercase' => fn() => is_string($value) ? strtolower($value) : null,
            'reverse' => fn() => is_string($value) ? strrev($value) : (is_array($value) ? array_reverse($value) : null),
            'count' => fn() => is_countable($value) ? count($value) : strlen((string)$value),
            default => fn() => $value,
        }();
    }
    
    // Match in property initialization
    private string $environment;
    
    public function __construct(string $env)
    {
        $this->environment = match ($env) {
            'dev', 'development' => 'development',
            'test', 'testing' => 'testing',
            'stage', 'staging' => 'staging',
            'prod', 'production' => 'production',
            default => throw new \InvalidArgumentException("Unknown environment: $env"),
        };
    }
    
    // Match with multiple return types
    public function parseValue(string $input): int|float|string|bool|null
    {
        return match (true) {
            $input === 'null' => null,
            $input === 'true' => true,
            $input === 'false' => false,
            is_numeric($input) && strpos($input, '.') === false => (int)$input,
            is_numeric($input) => (float)$input,
            default => $input,
        };
    }
}

// Enum for color example
enum Color
{
    case RED;
    case GREEN;
    case BLUE;
    case YELLOW;
    case BLACK;
    case WHITE;
}

// Class demonstrating match in different contexts
class MatchContexts
{
    // Match in array initialization
    public function getConfigArray(string $env): array
    {
        return [
            'debug' => match ($env) {
                'development' => true,
                'testing' => true,
                'staging' => false,
                'production' => false,
                default => false,
            },
            'cache' => match ($env) {
                'development' => false,
                'testing' => false,
                'staging' => true,
                'production' => true,
                default => true,
            },
            'log_level' => match ($env) {
                'development' => 'debug',
                'testing' => 'info',
                'staging' => 'warning',
                'production' => 'error',
                default => 'info',
            },
        ];
    }
    
    // Match with generator
    public function generateSequence(string $type, int $limit): \Generator
    {
        $generator = match ($type) {
            'fibonacci' => function() use ($limit) {
                $a = 0;
                $b = 1;
                for ($i = 0; $i < $limit; $i++) {
                    yield $a;
                    [$a, $b] = [$b, $a + $b];
                }
            },
            'squares' => fn() => yield from array_map(fn($n) => $n ** 2, range(1, $limit)),
            'primes' => function() use ($limit) {
                $primes = [];
                for ($n = 2; count($primes) < $limit; $n++) {
                    $isPrime = true;
                    foreach ($primes as $prime) {
                        if ($prime > sqrt($n)) break;
                        if ($n % $prime === 0) {
                            $isPrime = false;
                            break;
                        }
                    }
                    if ($isPrime) {
                        $primes[] = $n;
                        yield $n;
                    }
                }
            },
            default => fn() => yield from range(1, $limit),
        };
        
        yield from $generator();
    }
}