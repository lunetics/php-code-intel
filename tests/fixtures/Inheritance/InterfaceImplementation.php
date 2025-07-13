<?php

declare(strict_types=1);

namespace TestFixtures\Inheritance;

/**
 * Basic interface with single method
 */
interface Nameable
{
    public function getName(): string;
}

/**
 * Interface with multiple methods
 */
interface Ageable
{
    public function getAge(): int;
    public function setAge(int $age): void;
}

/**
 * Interface extending another interface
 */
interface Identifiable extends Nameable
{
    public function getId(): string;
    public function setId(string $id): void;
}

/**
 * Interface with constants
 */
interface StatusAware
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_PENDING = 'pending';
    
    public function getStatus(): string;
    public function setStatus(string $status): void;
    public function isActive(): bool;
}

/**
 * Interface extending multiple interfaces
 */
interface StorableEntity extends Identifiable, StatusAware
{
    public function save(): bool;
    public function delete(): bool;
    public function toArray(): array;
}

/**
 * Class implementing single interface
 */
class Person implements Nameable
{
    private string $name;
    
    public function __construct(string $name)
    {
        $this->name = $name;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
}

/**
 * Class implementing multiple interfaces
 */
class User implements Identifiable, Ageable, StatusAware
{
    private string $id;
    private string $name;
    private int $age;
    private string $status;
    
    public function __construct(string $id, string $name, int $age)
    {
        $this->id = $id;
        $this->name = $name;
        $this->age = $age;
        $this->status = self::STATUS_ACTIVE;
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function setId(string $id): void
    {
        $this->id = $id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getAge(): int
    {
        return $this->age;
    }
    
    public function setAge(int $age): void
    {
        $this->age = $age;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function setStatus(string $status): void
    {
        if (!in_array($status, [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_PENDING])) {
            throw new \InvalidArgumentException('Invalid status');
        }
        $this->status = $status;
    }
    
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}

/**
 * Abstract class implementing interface partially
 */
abstract class AbstractEntity implements StorableEntity
{
    protected string $id;
    protected string $name;
    protected string $status;
    
    public function __construct(string $id, string $name)
    {
        $this->id = $id;
        $this->name = $name;
        $this->status = self::STATUS_PENDING;
    }
    
    public function getId(): string
    {
        return $this->id;
    }
    
    public function setId(string $id): void
    {
        $this->id = $id;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getStatus(): string
    {
        return $this->status;
    }
    
    public function setStatus(string $status): void
    {
        $this->status = $status;
    }
    
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
    
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
        ];
    }
    
    // Abstract methods that concrete classes must implement
    abstract public function save(): bool;
    abstract public function delete(): bool;
}

/**
 * Concrete class completing abstract implementation
 */
class Product extends AbstractEntity
{
    private float $price;
    private int $stock;
    
    public function __construct(string $id, string $name, float $price, int $stock = 0)
    {
        parent::__construct($id, $name);
        $this->price = $price;
        $this->stock = $stock;
    }
    
    public function save(): bool
    {
        // Simulate saving to database
        return true;
    }
    
    public function delete(): bool
    {
        // Simulate deleting from database
        return true;
    }
    
    public function getPrice(): float
    {
        return $this->price;
    }
    
    public function getStock(): int
    {
        return $this->stock;
    }
    
    public function toArray(): array
    {
        return array_merge(parent::toArray(), [
            'price' => $this->price,
            'stock' => $this->stock,
        ]);
    }
}

/**
 * Interface for versioned entities
 */
interface Versionable
{
    public function getVersion(): int;
    public function incrementVersion(): void;
}

/**
 * Interface for timestamped entities
 */
interface Timestamped
{
    public function getCreatedAt(): \DateTimeInterface;
    public function getUpdatedAt(): ?\DateTimeInterface;
    public function setUpdatedAt(\DateTimeInterface $date): void;
}

/**
 * Complex class implementing many interfaces through inheritance
 */
class DigitalProduct extends Product implements Versionable, Timestamped
{
    private int $version;
    private \DateTimeInterface $createdAt;
    private ?\DateTimeInterface $updatedAt = null;
    private string $downloadUrl;
    
    public function __construct(string $id, string $name, float $price, string $downloadUrl)
    {
        parent::__construct($id, $name, $price, PHP_INT_MAX); // Unlimited stock
        $this->version = 1;
        $this->createdAt = new \DateTime();
        $this->downloadUrl = $downloadUrl;
    }
    
    public function getVersion(): int
    {
        return $this->version;
    }
    
    public function incrementVersion(): void
    {
        $this->version++;
        $this->setUpdatedAt(new \DateTime());
    }
    
    public function getCreatedAt(): \DateTimeInterface
    {
        return $this->createdAt;
    }
    
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(\DateTimeInterface $date): void
    {
        $this->updatedAt = $date;
    }
    
    public function getDownloadUrl(): string
    {
        return $this->downloadUrl;
    }
    
    public function save(): bool
    {
        $this->setUpdatedAt(new \DateTime());
        return parent::save();
    }
}