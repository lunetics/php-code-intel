<?php

declare(strict_types=1);

namespace TestFixtures\Inheritance;

/**
 * Base abstract class demonstrating inheritance fundamentals
 */
abstract class Animal
{
    protected string $species;
    protected int $age;
    private string $id;
    
    public function __construct(string $species, int $age)
    {
        $this->species = $species;
        $this->age = $age;
        $this->id = uniqid('animal_');
    }
    
    abstract public function makeSound(): string;
    
    abstract protected function move(): string;
    
    public function getSpecies(): string
    {
        return $this->species;
    }
    
    public function getAge(): int
    {
        return $this->age;
    }
    
    protected function getInternalId(): string
    {
        return $this->id;
    }
    
    public function describe(): string
    {
        return sprintf('%s is %d years old', $this->species, $this->age);
    }
}

/**
 * Intermediate abstract class adding mammal-specific features
 */
abstract class Mammal extends Animal
{
    protected bool $hasFur;
    protected float $bodyTemperature;
    
    public function __construct(string $species, int $age, bool $hasFur = true)
    {
        parent::__construct($species, $age);
        $this->hasFur = $hasFur;
        $this->bodyTemperature = 37.0;
    }
    
    protected function move(): string
    {
        return 'walking';
    }
    
    public function regulate(): void
    {
        $this->bodyTemperature = 37.0;
    }
    
    public function describe(): string
    {
        $parentDesc = parent::describe();
        return $parentDesc . ($this->hasFur ? ' and has fur' : ' and has no fur');
    }
    
    protected function nurseYoung(): bool
    {
        return true;
    }
}

/**
 * Final concrete class - cannot be extended
 */
final class Dog extends Mammal
{
    private string $breed;
    private static int $totalDogs = 0;
    public const DEFAULT_SOUND = 'Woof!';
    
    public function __construct(string $breed, int $age, string $name = 'Unknown')
    {
        parent::__construct('Dog', $age, true);
        $this->breed = $breed;
        self::$totalDogs++;
    }
    
    public function makeSound(): string
    {
        return self::DEFAULT_SOUND;
    }
    
    public function getBreed(): string
    {
        return $this->breed;
    }
    
    public static function getTotalDogs(): int
    {
        return self::$totalDogs;
    }
    
    public function wagTail(): string
    {
        return 'Wagging tail happily!';
    }
    
    public function describe(): string
    {
        return parent::describe() . " - Breed: {$this->breed}";
    }
}

/**
 * Another concrete mammal class
 */
class Cat extends Mammal
{
    private bool $isIndoor;
    
    public function __construct(int $age, bool $isIndoor = true)
    {
        parent::__construct('Cat', $age);
        $this->isIndoor = $isIndoor;
    }
    
    public function makeSound(): string
    {
        return 'Meow!';
    }
    
    protected function move(): string
    {
        return 'prowling silently';
    }
    
    public function purr(): string
    {
        return 'Purrrr...';
    }
    
    public function isIndoorCat(): bool
    {
        return $this->isIndoor;
    }
}

/**
 * Different inheritance branch - Bird extends Animal directly
 */
abstract class Bird extends Animal
{
    protected float $wingspan;
    protected bool $canFly;
    
    public function __construct(string $species, int $age, float $wingspan, bool $canFly = true)
    {
        parent::__construct($species, $age);
        $this->wingspan = $wingspan;
        $this->canFly = $canFly;
    }
    
    protected function move(): string
    {
        return $this->canFly ? 'flying' : 'walking';
    }
    
    public function getWingspan(): float
    {
        return $this->wingspan;
    }
    
    abstract public function buildNest(): string;
}

/**
 * Concrete bird implementation
 */
class Eagle extends Bird
{
    private float $maxAltitude;
    
    public function __construct(int $age, float $wingspan = 2.5)
    {
        parent::__construct('Eagle', $age, $wingspan);
        $this->maxAltitude = 3000.0;
    }
    
    public function makeSound(): string
    {
        return 'Screech!';
    }
    
    public function buildNest(): string
    {
        return 'Building nest on high cliff';
    }
    
    public function soar(float $altitude): void
    {
        if ($altitude <= $this->maxAltitude) {
            // Soaring at altitude
        }
    }
}

/**
 * Example showing constructor parameter evolution
 */
class Penguin extends Bird
{
    private string $colony;
    
    public function __construct(int $age, string $colony)
    {
        parent::__construct('Penguin', $age, 0.8, false); // Can't fly
        $this->colony = $colony;
    }
    
    public function makeSound(): string
    {
        return 'Squawk!';
    }
    
    public function buildNest(): string
    {
        return 'Creating pebble nest';
    }
    
    public function swim(): string
    {
        return 'Swimming underwater';
    }
    
    protected function move(): string
    {
        return 'waddling and swimming';
    }
}

// Usage examples for testing method calls
$dog = new Dog('Labrador', 3);
$result = $dog->makeSound(); // Regular method call

// Parent method call example - calls Animal::makeSound through inheritance
class ExtendedDog extends Dog 
{
    public function makeSound(): string
    {
        return parent::makeSound() . ' Extended!';
    }
}

$extendedDog = new ExtendedDog('Golden Retriever', 2);
$extendedSound = $extendedDog->makeSound();