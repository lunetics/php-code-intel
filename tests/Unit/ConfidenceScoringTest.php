<?php

declare(strict_types=1);

namespace CodeIntel\Tests\Unit;

use CodeIntel\Finder\ConfidenceScorer;
use PHPUnit\Framework\TestCase;

class ConfidenceScoringTest extends TestCase
{
    private ConfidenceScorer $scorer;

    protected function setUp(): void
    {
        $this->scorer = new ConfidenceScorer();
    }

    /**
     * @test
     * @dataProvider certainConfidenceProvider
     */
    public function assigns_certain_confidence_to_direct_usage(string $code): void
    {
        // Act
        $confidence = $this->scorer->score($code);
        
        // Assert
        $this->assertEquals('CERTAIN', $confidence);
    }

    /**
     * @return array<string, array<string>>
     */
    public function certainConfidenceProvider(): array
    {
        return [
            'direct instantiation' => ['new ClassName()'],
            'static method call' => ['ClassName::method()'],
            'class constant access' => ['ClassName::CONSTANT'],
            'instanceof check' => ['$obj instanceof ClassName'],
            'class reference' => ['ClassName::class'],
            'self reference' => ['self::method()'],
            'parent reference' => ['parent::method()'],
            'static reference' => ['static::method()'],
        ];
    }

    /**
     * @test
     * @dataProvider probableConfidenceProvider
     */
    public function assigns_probable_confidence_to_typed_usage(string $code, string $context): void
    {
        // Act
        $confidence = $this->scorer->scoreWithContext($code, $context);
        
        // Assert
        $this->assertEquals('PROBABLE', $confidence);
    }

    /**
     * @return array<string, array<string>>
     */
    public function probableConfidenceProvider(): array
    {
        return [
            'typed parameter' => [
                '$service->process()',
                'function handle(UserService $service) { $service->process(); }'
            ],
            'phpdoc type hint' => [
                '$var->method()',
                '/** @var ClassName $var */ $var->method();'
            ],
            'property with type' => [
                '$this->service->method()',
                'private UserService $service;'
            ],
            'nullsafe operator' => [
                '$obj?->method()',
                '$obj?->method();'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider possibleConfidenceProvider
     */
    public function assigns_possible_confidence_to_dynamic_usage(string $code): void
    {
        // Act
        $confidence = $this->scorer->score($code);
        
        // Assert
        $this->assertEquals('POSSIBLE', $confidence);
    }

    /**
     * @return array<string, array<string>>
     */
    public function possibleConfidenceProvider(): array
    {
        return [
            'variable class name' => ['new $className()'],
            'variable method name' => ['$obj->$method()'],
            'string class reference' => ['$class = "ClassName"; new $class()'],
            'array callable' => ['[$obj, "method"]'],
            'class exists check' => ['class_exists("ClassName")'],
            'is_a check' => ['is_a($obj, "ClassName")'],
        ];
    }

    /**
     * @test
     * @dataProvider dynamicConfidenceProvider
     */
    public function assigns_dynamic_confidence_to_magic_usage(string $code): void
    {
        // Act
        $confidence = $this->scorer->score($code);
        
        // Assert
        $this->assertEquals('DYNAMIC', $confidence);
    }

    /**
     * @return array<string, array<string>>
     */
    public function dynamicConfidenceProvider(): array
    {
        return [
            'call_user_func' => ['call_user_func([$obj, "method"])'],
            'call_user_func_array' => ['call_user_func_array($callback, $args)'],
            'magic call' => ['$obj->__call("method", [])'],
            'invoke pattern' => ['$obj("arg")'],
            'reflection' => ['$reflection->invoke($obj)'],
        ];
    }

    /**
     * @test
     */
    public function handles_complex_expressions(): void
    {
        // Arrange
        $complexCases = [
            '$this->getService()->process()' => 'PROBABLE',
            'app(UserService::class)->method()' => 'PROBABLE',
            '$factory->create()->run()' => 'POSSIBLE',
            '${$varName}::staticMethod()' => 'DYNAMIC',
        ];
        
        // Act & Assert
        foreach ($complexCases as $code => $expectedConfidence) {
            $confidence = $this->scorer->score($code);
            $this->assertEquals(
                $expectedConfidence,
                $confidence,
                "Failed for code: $code"
            );
        }
    }
}