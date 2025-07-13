<?php

declare(strict_types=1);

namespace TestFixtures\ModernPHP;

/**
 * Demonstrates PHP 8.0+ nullsafe operator (?->) features
 */
class NullsafeOperator
{
    private ?Address $address = null;
    private ?Company $company = null;
    private ?array $data = null;
    
    // Nullsafe method calls
    public function getCountryName(): ?string
    {
        return $this->address?->getCountry()?->getName();
    }
    
    public function getCityPostalCode(): ?string
    {
        return $this->address?->getCity()?->getPostalCode();
    }
    
    // Nullsafe property access
    public function getStreetName(): ?string
    {
        return $this->address?->street;
    }
    
    public function getCompanyName(): ?string
    {
        return $this->company?->name;
    }
    
    public function getCompanyCeo(): ?string
    {
        return $this->company?->ceo?->fullName;
    }
    
    // Chained nullsafe operations
    public function getCompanyHeadquartersCountry(): ?string
    {
        return $this->company?->getHeadquarters()?->getAddress()?->getCountry()?->getName();
    }
    
    public function getDepartmentManagerEmail(): ?string
    {
        return $this->company?->getDepartment('IT')?->getManager()?->getContact()?->email;
    }
    
    // Mixed with regular calls
    public function getFormattedAddress(): string
    {
        $street = $this->address?->street ?? 'Unknown Street';
        $city = $this->address?->getCity()?->name ?? 'Unknown City';
        $country = $this->address?->getCountry()?->getName() ?? 'Unknown Country';
        
        return sprintf('%s, %s, %s', $street, $city, $country);
    }
    
    // Nullsafe with array access
    public function getDataValue(string $key): mixed
    {
        return $this->data?->getValue($key)?->toString();
    }
    
    // Nullsafe in conditions
    public function hasValidAddress(): bool
    {
        return $this->address?->isValid() === true;
    }
    
    public function isInCountry(string $countryCode): bool
    {
        return $this->address?->getCountry()?->getCode() === $countryCode;
    }
    
    // Nullsafe with method chaining on return values
    public function getUppercaseCountryName(): ?string
    {
        return $this->address?->getCountry()?->getName()?->toUpperCase();
    }
    
    // Nullsafe in loops
    public function getAllEmployeeEmails(): array
    {
        $emails = [];
        $departments = $this->company?->getDepartments() ?? [];
        
        foreach ($departments as $department) {
            $employees = $department?->getEmployees() ?? [];
            foreach ($employees as $employee) {
                $email = $employee?->getContact()?->email;
                if ($email !== null) {
                    $emails[] = $email;
                }
            }
        }
        
        return $emails;
    }
    
    // Nullsafe with callbacks
    public function processWithCallback(callable $callback): mixed
    {
        $result = $this->company?->process($callback);
        return $result?->getValue();
    }
    
    // Complex nullsafe chains
    public function getComplexData(): ?string
    {
        return $this->company
            ?->getDepartment('Sales')
            ?->getManager()
            ?->getAssistant()
            ?->getContact()
            ?->getAddress()
            ?->getCountry()
            ?->getCapital()
            ?->getName();
    }
    
    // Nullsafe with different return types
    public function getEmployeeCount(): int
    {
        return $this->company?->getDepartment('IT')?->getEmployeeCount() ?? 0;
    }
    
    public function getCompanyRevenue(): float
    {
        return $this->company?->getFinancials()?->getRevenue() ?? 0.0;
    }
    
    public function isCompanyPublic(): bool
    {
        return $this->company?->isPubliclyTraded() ?? false;
    }
    
    // Nullsafe with static calls (not supported, showing alternative)
    public function getStaticValue(): ?string
    {
        // This doesn't work: $this->company?::getStaticValue()
        // Alternative approach:
        return $this->company !== null ? $this->company::getStaticValue() : null;
    }
    
    // Nullsafe in ternary operations
    public function getDisplayName(): string
    {
        return $this->company?->getName() !== null
            ? $this->company->getName()
            : 'No Company';
    }
    
    // Nullsafe with isset
    public function hasCompanyEmail(): bool
    {
        return isset($this->company?->getContact()?->email);
    }
    
    // Nullsafe with empty
    public function hasEmployees(): bool
    {
        return !empty($this->company?->getDepartments());
    }
    
    // Combining nullsafe with null coalescing
    public function getCompanyOrDefault(): string
    {
        return $this->company?->getName() ?? $this->company?->getLegalName() ?? 'Unknown Company';
    }
    
    // Nullsafe in match expressions
    public function categorizeCompany(): string
    {
        return match (true) {
            $this->company?->getEmployeeCount() === null => 'No data',
            $this->company->getEmployeeCount() < 10 => 'Startup',
            $this->company->getEmployeeCount() < 100 => 'Small',
            $this->company->getEmployeeCount() < 1000 => 'Medium',
            default => 'Large',
        };
    }
    
    // Setters for testing
    public function setAddress(?Address $address): void
    {
        $this->address = $address;
    }
    
    public function setCompany(?Company $company): void
    {
        $this->company = $company;
    }
}

// Supporting classes for nullsafe demonstrations
class Address
{
    public string $street;
    private ?City $city = null;
    private ?Country $country = null;
    
    public function getCity(): ?City
    {
        return $this->city;
    }
    
    public function getCountry(): ?Country
    {
        return $this->country;
    }
    
    public function isValid(): bool
    {
        return !empty($this->street) && $this->city !== null;
    }
}

class City
{
    public string $name;
    private ?string $postalCode = null;
    
    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }
}

class Country
{
    private string $name;
    private string $code;
    private ?City $capital = null;
    
    public function getName(): ?StringValue
    {
        return new StringValue($this->name);
    }
    
    public function getCode(): string
    {
        return $this->code;
    }
    
    public function getCapital(): ?City
    {
        return $this->capital;
    }
}

class StringValue
{
    public function __construct(private string $value) {}
    
    public function toString(): string
    {
        return $this->value;
    }
    
    public function toUpperCase(): string
    {
        return strtoupper($this->value);
    }
}

class Company
{
    public string $name;
    public ?Person $ceo = null;
    private ?Office $headquarters = null;
    private array $departments = [];
    private ?Financials $financials = null;
    private ?Contact $contact = null;
    
    public function getHeadquarters(): ?Office
    {
        return $this->headquarters;
    }
    
    public function getDepartment(string $name): ?Department
    {
        return $this->departments[$name] ?? null;
    }
    
    public function getDepartments(): array
    {
        return $this->departments;
    }
    
    public function getEmployeeCount(): int
    {
        $count = 0;
        foreach ($this->departments as $department) {
            $count += $department?->getEmployeeCount() ?? 0;
        }
        return $count;
    }
    
    public function getFinancials(): ?Financials
    {
        return $this->financials;
    }
    
    public function getContact(): ?Contact
    {
        return $this->contact;
    }
    
    public function getName(): string
    {
        return $this->name;
    }
    
    public function getLegalName(): ?string
    {
        return null; // For demonstration
    }
    
    public function isPubliclyTraded(): bool
    {
        return false; // For demonstration
    }
    
    public function process(callable $callback): ?Result
    {
        return new Result($callback($this));
    }
    
    public static function getStaticValue(): string
    {
        return 'static value';
    }
}

class Office
{
    private ?Address $address = null;
    
    public function getAddress(): ?Address
    {
        return $this->address;
    }
}

class Department
{
    private array $employees = [];
    private ?Person $manager = null;
    
    public function getEmployees(): array
    {
        return $this->employees;
    }
    
    public function getManager(): ?Person
    {
        return $this->manager;
    }
    
    public function getEmployeeCount(): int
    {
        return count($this->employees);
    }
}

class Person
{
    public string $fullName;
    private ?Person $assistant = null;
    private ?Contact $contact = null;
    
    public function getAssistant(): ?Person
    {
        return $this->assistant;
    }
    
    public function getContact(): ?Contact
    {
        return $this->contact;
    }
}

class Contact
{
    public ?string $email = null;
    public ?string $phone = null;
    private ?Address $address = null;
    
    public function getAddress(): ?Address
    {
        return $this->address;
    }
}

class Financials
{
    private float $revenue = 0.0;
    
    public function getRevenue(): float
    {
        return $this->revenue;
    }
}

class Result
{
    public function __construct(private mixed $value) {}
    
    public function getValue(): mixed
    {
        return $this->value;
    }
}

// Class demonstrating edge cases and complex scenarios
class NullsafeEdgeCases
{
    private ?self $next = null;
    private ?array $items = null;
    
    // Nullsafe with $this
    public function getSelfReference(): ?string
    {
        return $this->next?->next?->getName();
    }
    
    public function getName(): string
    {
        return 'EdgeCase';
    }
    
    // Nullsafe with array-like objects
    public function getArrayItem(int $index): mixed
    {
        return $this->items?->getItem($index)?->getValue();
    }
    
    // Nullsafe with magic methods
    public function getMagicProperty(): mixed
    {
        return $this->next?->magicProperty;
    }
    
    public function __get(string $name): mixed
    {
        return "Magic: $name";
    }
    
    // Nullsafe with closures
    public function executeCallback(): mixed
    {
        $callback = $this->next?->getCallback();
        return $callback?->call($this);
    }
    
    public function getCallback(): ?\Closure
    {
        return fn() => 'Callback result';
    }
}