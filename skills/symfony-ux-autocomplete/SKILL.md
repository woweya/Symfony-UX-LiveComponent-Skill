---
name: symfony-ux-autocomplete
description: "Build AJAX-powered autocomplete fields in Symfony forms. TRIGGER when: user works with autocomplete form types, Tom Select integration, EntityAutocompleteField, or AJAX-powered select fields in Symfony. Covers basic autocomplete, entity autocomplete, custom queries, and grouped results. DO NOT TRIGGER when: user works with plain HTML select elements or non-Symfony forms."
license: Complete terms in LICENSE.txt
---

# Symfony UX Autocomplete

Symfony UX Autocomplete provides AJAX-powered autocomplete functionality for Symfony Form select fields, powered by Tom Select.

## Installation

```bash
composer require symfony/ux-autocomplete
```

## Basic Usage

### Simple Autocomplete (Choices from Form Type)

```php
// src/Form/RecipeType.php
namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', ChoiceType::class, [
                'choices' => [
                    'Appetizer' => 'appetizer',
                    'Main Course' => 'main',
                    'Dessert' => 'dessert',
                    'Beverage' => 'beverage',
                ],
                'autocomplete' => true, // This is all you need!
            ])
        ;
    }
}
```

### Entity Autocomplete

```php
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

$builder
    ->add('author', EntityType::class, [
        'class' => User::class,
        'choice_label' => 'fullName',
        'autocomplete' => true,          // Enables Tom Select
        'placeholder' => 'Select an author',
    ])
;
```

### Multiple Selection

```php
$builder
    ->add('tags', EntityType::class, [
        'class' => Tag::class,
        'choice_label' => 'name',
        'multiple' => true,
        'autocomplete' => true,
    ])
;
```

---

## Custom Autocomplete Field (AJAX)

For large datasets, load results via AJAX instead of embedding all options in HTML.

### Step 1: Create the Autocompleter

```php
// src/Autocompleter/FoodAutocompleter.php
namespace App\Autocompleter;

use App\Entity\Food;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

#[AutoconfigureTag('ux.entity_autocompleter', ['alias' => 'food'])]
class FoodAutocompleter implements EntityAutocompleterInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function getEntityClass(): string
    {
        return Food::class;
    }

    public function createFilteredQueryBuilder(EntityRepository $repository, string $query): QueryBuilder
    {
        return $repository->createQueryBuilder('f')
            ->andWhere('f.name LIKE :query OR f.description LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('f.name', 'ASC');
    }

    public function getLabel(object $entity): string
    {
        return $entity->getName();
    }

    public function getValue(object $entity): string
    {
        return (string) $entity->getId();
    }

    public function isGranted(Security $security): bool
    {
        // Optional: restrict access
        return true;
    }

    public function getGroupBy(): mixed
    {
        // Optional: group results by category
        return 'category.name';
    }
}
```

### Step 2: Use in Form

```php
use Symfony\UX\Autocomplete\Form\AsEntityAutocompleteField;
use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

// Option A: Reference the autocompleter alias
$builder->add('food', BaseEntityAutocompleteType::class, [
    'class' => Food::class,
    'autocomplete_url' => $this->generateUrl('ux_entity_autocomplete', [
        'alias' => 'food',
    ]),
]);

// Option B: Create a dedicated form type
#[AsEntityAutocompleteField]
class FoodAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Food::class,
            'choice_label' => 'name',
            'placeholder' => 'Search for a food...',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}

// Then use it:
$builder->add('food', FoodAutocompleteType::class);
```

---

## Tom Select Options

Customize the underlying Tom Select instance:

```php
$builder->add('tags', EntityType::class, [
    'class' => Tag::class,
    'autocomplete' => true,
    'tom_select_options' => [
        'maxItems' => 5,           // Limit selections
        'create' => true,          // Allow creating new items
        'createOnBlur' => true,    // Create on blur
        'plugins' => [
            'remove_button',       // Show remove button on selected items
            'clear_button',        // Show clear all button
        ],
    ],
]);
```

---

## Custom Result Rendering

```php
class FoodAutocompleter implements EntityAutocompleterInterface
{
    // ... other methods ...

    public function getLabel(object $entity): string
    {
        // HTML is supported in labels
        return sprintf(
            '<div class="d-flex align-items-center">'
            . '<img src="%s" width="30" class="me-2">'
            . '<div><strong>%s</strong><br><small>%s</small></div>'
            . '</div>',
            htmlspecialchars($entity->getImageUrl(), ENT_QUOTES),
            htmlspecialchars($entity->getName(), ENT_QUOTES),
            htmlspecialchars($entity->getCategory()->getName(), ENT_QUOTES)
        );
    }
}
```

---

## Decision Tree

```
Need a select/autocomplete field?
├─ Small list of options → ChoiceType + autocomplete: true
├─ Entity with <1000 records → EntityType + autocomplete: true
├─ Entity with many records → Custom EntityAutocompleterInterface (AJAX)
├─ Need custom search logic → createFilteredQueryBuilder()
├─ Need grouped results → getGroupBy()
├─ Need custom rendering → HTML in getLabel()
└─ Need tag-style creation → tom_select_options.create: true
```

## Common Pitfalls

- **Missing `autocomplete: true`** — This is the simplest way to enable Tom Select on any ChoiceType/EntityType
- **Large datasets without AJAX** — Use a custom Autocompleter for entities with many records
- **Security** — Implement `isGranted()` in your autocompleter to restrict access
- **HTML escaping** — Use `htmlspecialchars()` when returning HTML labels to prevent XSS
- **Route generation** — The autocomplete endpoint is automatically registered at `ux_entity_autocomplete`

## Reference Files

- **examples/** - Common patterns:
  - `autocomplete-field.php` - Basic and entity autocomplete form fields
  - `custom-autocompleter.php` - AJAX-powered autocomplete with custom queries
