<?php

declare(strict_types=1);

namespace TestFixtures\BasicSymbols;

// Supporting interfaces for examples
interface Printable
{
    public function print(): string;
}

interface Serializable
{
    public function serialize(): string;
    public function unserialize(string $data): void;
}

// Supporting traits for examples
trait TimestampableTrait
{
    private ?\DateTimeImmutable $createdAt = null;
    private ?\DateTimeImmutable $updatedAt = null;

    public function setCreatedAt(\DateTimeImmutable $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function updateTimestamp(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}

trait LoggableTrait
{
    private array $logs = [];

    public function log(string $message): void
    {
        $this->logs[] = [
            'time' => new \DateTimeImmutable(),
            'message' => $message,
        ];
    }

    public function getLogs(): array
    {
        return $this->logs;
    }
}

// 1. Simple class
class SimpleClass
{
    public string $name = 'simple';

    public function getName(): string
    {
        return $this->name;
    }
}

// 2. Final class
final class FinalClass
{
    private string $data;

    public function __construct(string $data)
    {
        $this->data = $data;
    }

    public function getData(): string
    {
        return $this->data;
    }
}

// 3. Abstract class
abstract class AbstractShape
{
    protected string $color;

    public function __construct(string $color)
    {
        $this->color = $color;
    }

    abstract public function getArea(): float;

    public function getColor(): string
    {
        return $this->color;
    }
}

// 4. Class with constructor
class ClassWithConstructor
{
    private string $id;
    private string $name;
    private ?string $description;

    public function __construct(string $id, string $name, ?string $description = null)
    {
        $this->id = $id;
        $this->name = $name;
        $this->description = $description;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
}

// 5. Class extending another class
class Circle extends AbstractShape
{
    private float $radius;

    public function __construct(string $color, float $radius)
    {
        parent::__construct($color);
        $this->radius = $radius;
    }

    public function getArea(): float
    {
        return pi() * pow($this->radius, 2);
    }

    public function getRadius(): float
    {
        return $this->radius;
    }
}

class Rectangle extends AbstractShape
{
    public function __construct(
        string $color,
        private float $width,
        private float $height
    ) {
        parent::__construct($color);
    }

    public function getArea(): float
    {
        return $this->width * $this->height;
    }
}

// 6. Class implementing interfaces
class Document implements Printable, Serializable
{
    private string $title;
    private string $content;

    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
    }

    public function print(): string
    {
        return sprintf("Document: %s\n%s", $this->title, $this->content);
    }

    public function serialize(): string
    {
        return json_encode([
            'title' => $this->title,
            'content' => $this->content,
        ]);
    }

    public function unserialize(string $data): void
    {
        $decoded = json_decode($data, true);
        $this->title = $decoded['title'] ?? '';
        $this->content = $decoded['content'] ?? '';
    }
}

// 7. Class using traits
class Article implements Printable
{
    use TimestampableTrait;
    use LoggableTrait;

    private string $headline;
    private string $body;

    public function __construct(string $headline, string $body)
    {
        $this->headline = $headline;
        $this->body = $body;
        $this->setCreatedAt(new \DateTimeImmutable());
        $this->log('Article created');
    }

    public function print(): string
    {
        return sprintf("Article: %s\n%s", $this->headline, $this->body);
    }

    public function publish(): void
    {
        $this->updateTimestamp();
        $this->log('Article published');
    }
}

// Complex class combining multiple features
class ComplexEntity extends AbstractShape implements Printable, Serializable
{
    use LoggableTrait;

    private string $identifier;
    private array $metadata;

    public function __construct(string $color, string $identifier, array $metadata = [])
    {
        parent::__construct($color);
        $this->identifier = $identifier;
        $this->metadata = $metadata;
        $this->log('ComplexEntity created');
    }

    public function getArea(): float
    {
        return 0.0; // Placeholder implementation
    }

    public function print(): string
    {
        return sprintf("ComplexEntity[%s]: color=%s", $this->identifier, $this->color);
    }

    public function serialize(): string
    {
        return json_encode([
            'identifier' => $this->identifier,
            'color' => $this->color,
            'metadata' => $this->metadata,
        ]);
    }

    public function unserialize(string $data): void
    {
        $decoded = json_decode($data, true);
        $this->identifier = $decoded['identifier'] ?? '';
        $this->color = $decoded['color'] ?? '';
        $this->metadata = $decoded['metadata'] ?? [];
    }
}

// 8. Anonymous class examples
class AnonymousClassFactory
{
    public function createSimpleAnonymous(): object
    {
        return new class {
            private string $type = 'anonymous';

            public function getType(): string
            {
                return $this->type;
            }
        };
    }

    public function createAnonymousWithInterface(): Printable
    {
        return new class implements Printable {
            public function print(): string
            {
                return 'Anonymous Printable Instance';
            }
        };
    }

    public function createAnonymousWithExtension(): AbstractShape
    {
        return new class('blue') extends AbstractShape {
            public function getArea(): float
            {
                return 100.0;
            }
        };
    }

    public function createAnonymousWithConstructor(string $message): object
    {
        return new class($message) {
            private string $message;

            public function __construct(string $message)
            {
                $this->message = $message;
            }

            public function getMessage(): string
            {
                return $this->message;
            }
        };
    }
}

// Usage examples section
// These demonstrate how to find and use the various class types

// Simple class usage
$simple = new SimpleClass();
echo $simple->getName() . "\n";

// Final class usage
$final = new FinalClass('important data');
echo $final->getData() . "\n";

// Abstract class usage through concrete implementations
$circle = new Circle('red', 5.0);
echo sprintf("Circle area: %.2f\n", $circle->getArea());

$rectangle = new Rectangle('green', 10.0, 20.0);
echo sprintf("Rectangle area: %.2f\n", $rectangle->getArea());

// Class with constructor usage
$entity = new ClassWithConstructor('123', 'Test Entity', 'A test description');
echo sprintf("Entity: %s - %s\n", $entity->getId(), $entity->getName());

// Interface implementation usage
$doc = new Document('My Document', 'This is the content of my document.');
echo $doc->print() . "\n";
$serialized = $doc->serialize();
echo "Serialized: " . $serialized . "\n";

// Trait usage
$article = new Article('Breaking News', 'Something important happened today.');
$article->publish();
echo $article->print() . "\n";
if (method_exists($article, 'getCreatedAt')) {
    echo "Article has timestampable methods\n";
}

// Complex entity usage
$complex = new ComplexEntity('yellow', 'complex-1', ['type' => 'test']);
echo $complex->print() . "\n";

// Anonymous class usage
$factory = new AnonymousClassFactory();

$anon1 = $factory->createSimpleAnonymous();
echo "Anonymous type: " . $anon1->getType() . "\n";

$anon2 = $factory->createAnonymousWithInterface();
echo $anon2->print() . "\n";

$anon3 = $factory->createAnonymousWithExtension();
echo sprintf("Anonymous shape area: %.2f\n", $anon3->getArea());

$anon4 = $factory->createAnonymousWithConstructor('Hello from anonymous!');
echo $anon4->getMessage() . "\n";

// Type checking examples
var_dump($simple instanceof SimpleClass);           // true
var_dump($circle instanceof AbstractShape);         // true
var_dump($circle instanceof Circle);                // true
var_dump($doc instanceof Printable);                // true
var_dump($doc instanceof Serializable);             // true
var_dump($article instanceof Printable);            // true
var_dump($complex instanceof AbstractShape);        // true
var_dump($complex instanceof Printable);            // true

// Class reference examples
$className = SimpleClass::class;
echo "Class name: " . $className . "\n";

// Type hint examples  
function useClass(SimpleClass $instance): void {
    echo "Using instance of: " . get_class($instance) . "\n";
}

useClass($simple);

// Polymorphism examples
function printShape(AbstractShape $shape): void {
    echo sprintf("Shape color: %s, area: %.2f\n", $shape->getColor(), $shape->getArea());
}

printShape($circle);
printShape($rectangle);
printShape($complex);
printShape($anon3);

function printPrintable(Printable $printable): void {
    echo "Printable output: " . $printable->print() . "\n";
}

printPrintable($doc);
printPrintable($article);
printPrintable($complex);
printPrintable($anon2);