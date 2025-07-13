<?php

declare(strict_types=1);

namespace TestFixtures\Inheritance;

/**
 * Basic trait with properties and methods
 */
trait TimestampableTrait
{
    private ?\DateTimeInterface $createdAt = null;
    private ?\DateTimeInterface $updatedAt = null;
    
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }
    
    public function setCreatedAt(\DateTimeInterface $createdAt): void
    {
        $this->createdAt = $createdAt;
    }
    
    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }
    
    public function setUpdatedAt(\DateTimeInterface $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }
    
    public function updateTimestamps(): void
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime();
        }
        $this->updatedAt = new \DateTime();
    }
}

/**
 * Trait with logging functionality
 */
trait LoggableTrait
{
    private array $logs = [];
    
    public function log(string $message, string $level = 'info'): void
    {
        $this->logs[] = [
            'timestamp' => new \DateTime(),
            'level' => $level,
            'message' => $message,
        ];
    }
    
    public function getLogs(): array
    {
        return $this->logs;
    }
    
    public function clearLogs(): void
    {
        $this->logs = [];
    }
}

/**
 * Trait using other traits (trait composition)
 */
trait AuditableTrait
{
    use TimestampableTrait;
    use LoggableTrait;
    
    private ?string $createdBy = null;
    private ?string $updatedBy = null;
    
    public function setCreatedBy(string $user): void
    {
        $this->createdBy = $user;
        $this->log("Created by {$user}");
    }
    
    public function setUpdatedBy(string $user): void
    {
        $this->updatedBy = $user;
        $this->log("Updated by {$user}");
        $this->setUpdatedAt(new \DateTime());
    }
    
    public function getAuditInfo(): array
    {
        return [
            'created_at' => $this->getCreatedAt(),
            'created_by' => $this->createdBy,
            'updated_at' => $this->getUpdatedAt(),
            'updated_by' => $this->updatedBy,
        ];
    }
}

/**
 * Trait with abstract method requirement
 */
trait CacheableTrait
{
    private array $cache = [];
    private int $cacheHits = 0;
    private int $cacheMisses = 0;
    
    abstract public function getCacheKey(): string;
    
    public function getFromCache(string $key): mixed
    {
        $fullKey = $this->getCacheKey() . ':' . $key;
        
        if (isset($this->cache[$fullKey])) {
            $this->cacheHits++;
            return $this->cache[$fullKey];
        }
        
        $this->cacheMisses++;
        return null;
    }
    
    public function setInCache(string $key, mixed $value): void
    {
        $fullKey = $this->getCacheKey() . ':' . $key;
        $this->cache[$fullKey] = $value;
    }
    
    public function getCacheStats(): array
    {
        return [
            'hits' => $this->cacheHits,
            'misses' => $this->cacheMisses,
            'hit_rate' => $this->cacheHits + $this->cacheMisses > 0 
                ? $this->cacheHits / ($this->cacheHits + $this->cacheMisses) 
                : 0,
        ];
    }
}

/**
 * Trait providing singleton pattern
 */
trait SingletonTrait
{
    private static ?self $instance = null;
    
    private function __construct()
    {
        // Prevent direct construction
    }
    
    private function __clone()
    {
        // Prevent cloning
    }
    
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
}

/**
 * Trait providing array access functionality
 */
trait ArrayAccessTrait
{
    private array $data = [];
    
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->data[$offset]);
    }
    
    public function offsetGet(mixed $offset): mixed
    {
        return $this->data[$offset] ?? null;
    }
    
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($offset === null) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }
    
    public function offsetUnset(mixed $offset): void
    {
        unset($this->data[$offset]);
    }
    
    public function toArray(): array
    {
        return $this->data;
    }
}

/**
 * Class using simple trait
 */
class Article
{
    use TimestampableTrait;
    
    private string $title;
    private string $content;
    
    public function __construct(string $title, string $content)
    {
        $this->title = $title;
        $this->content = $content;
        $this->setCreatedAt(new \DateTime());
    }
    
    public function getTitle(): string
    {
        return $this->title;
    }
    
    public function updateContent(string $content): void
    {
        $this->content = $content;
        $this->updateTimestamps();
    }
}

/**
 * Class using multiple traits
 */
class Document implements \ArrayAccess
{
    use ArrayAccessTrait;
    use LoggableTrait;
    use CacheableTrait;
    
    private string $id;
    
    public function __construct(string $id)
    {
        $this->id = $id;
        $this->log('Document created');
    }
    
    public function getCacheKey(): string
    {
        return 'document:' . $this->id;
    }
    
    public function getId(): string
    {
        return $this->id;
    }
}

/**
 * Trait with conflicting method names
 */
trait NameTrait
{
    private string $name;
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function setName(string $name): void
    {
        $this->name = $name;
    }
    
    public function display(): string
    {
        return "Name: {$this->name}";
    }
}

/**
 * Another trait with conflicting method
 */
trait TitleTrait
{
    private string $title;
    
    public function getTitle(): string
    {
        return $this->title;
    }
    
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }
    
    public function display(): string
    {
        return "Title: {$this->title}";
    }
}

/**
 * Class demonstrating trait precedence and aliasing
 */
class Book
{
    use NameTrait, TitleTrait {
        TitleTrait::display insteadof NameTrait;
        NameTrait::display as displayName;
        TitleTrait::display as displayTitle;
    }
    
    public function __construct(string $name, string $title)
    {
        $this->setName($name);
        $this->setTitle($title);
    }
    
    public function getFullDisplay(): string
    {
        return $this->displayName() . ' - ' . $this->displayTitle();
    }
}

/**
 * Class using trait with visibility change
 */
class Configuration
{
    use SingletonTrait {
        getInstance as public;
    }
    
    private array $settings = [];
    
    public function set(string $key, mixed $value): void
    {
        $this->settings[$key] = $value;
    }
    
    public function get(string $key): mixed
    {
        return $this->settings[$key] ?? null;
    }
}

/**
 * Complex class using audit trait
 */
class Order
{
    use AuditableTrait;
    
    private string $orderNumber;
    private float $total;
    private array $items = [];
    
    public function __construct(string $orderNumber)
    {
        $this->orderNumber = $orderNumber;
        $this->total = 0.0;
        $this->updateTimestamps();
        $this->log('Order created: ' . $orderNumber);
    }
    
    public function addItem(string $item, float $price): void
    {
        $this->items[] = ['item' => $item, 'price' => $price];
        $this->total += $price;
        $this->log("Item added: {$item} (${$price})");
    }
    
    public function getOrderNumber(): string
    {
        return $this->orderNumber;
    }
    
    public function getTotal(): float
    {
        return $this->total;
    }
}