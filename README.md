# Auroro Schema

Reflection-based JSON Schema generation, validation, and DTO hydration for PHP 8.3+.

## Installation

```bash
composer require auroro/schema
```

## Usage

### Define a DTO

```php
use Auroro\Schema\Attribute\With;

class Contact
{
    public function __construct(
        public readonly string $name,
        #[With(format: 'email')]
        public readonly string $email,
        #[With(minimum: 0, maximum: 150)]
        public readonly int $age,
    ) {}
}
```

### Generate JSON Schema

```php
use Auroro\Schema\SchemaFactory;

$factory = new SchemaFactory();
$schema = $factory->buildParameters(Contact::class, '__construct');
```

### Validate and hydrate

```php
use Auroro\Schema\SchemaProcessor;

$processor = new SchemaProcessor();
$result = $processor->process([
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'age' => 30,
], Contact::class);

if ($result->isOk()) {
    $contact = $result->unwrap(); // Contact instance
} else {
    $errors = $result->error(); // list<ValidationError>
}
```

### Hydrate without validation

```php
use Auroro\Schema\Hydrator;

$hydrator = new Hydrator();
$contact = $hydrator->hydrate([
    'name' => 'Alice',
    'email' => 'alice@example.com',
    'age' => 30,
], Contact::class);
```

### Dehydrate to array

```php
use Auroro\Schema\Dehydrator;

$dehydrator = new Dehydrator();
$array = $dehydrator->dehydrate($contact);
// ['name' => 'Alice', 'email' => 'alice@example.com', 'age' => 30]
```

## Constraints

The `#[With]` attribute supports all standard JSON Schema constraints:

| Category | Constraints |
|----------|-------------|
| String | `minLength`, `maxLength`, `pattern`, `format` |
| Numeric | `minimum`, `maximum`, `exclusiveMinimum`, `exclusiveMaximum`, `multipleOf` |
| Array | `minItems`, `maxItems`, `uniqueItems` |
| Enum | `enum` |

Supported formats: `email`, `uri`, `date-time`, `uuid`, `date`, `time`, `hostname`, `ipv4`, `ipv6`.

## Type support

- Scalar types (`int`, `float`, `bool`, `string`)
- Backed enums
- Nested objects (recursive)
- Typed arrays via doc comments (`@param list<Item>`)
- `DateTime` / `DateTimeImmutable`
- Nullable and union types

## License

MIT — see [LICENSE](LICENSE).
