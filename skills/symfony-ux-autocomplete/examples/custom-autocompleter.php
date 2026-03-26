<?php
// ============================================================
// Custom AJAX-Powered Autocompleter
// ============================================================

namespace App\Autocompleter;

use App\Entity\Product;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;
use Symfony\UX\Autocomplete\EntityAutocompleterInterface;

#[AutoconfigureTag('ux.entity_autocompleter', ['alias' => 'product'])]
class ProductAutocompleter implements EntityAutocompleterInterface
{
    public function __construct(
        private Security $security,
    ) {
    }

    public function getEntityClass(): string
    {
        return Product::class;
    }

    /**
     * Build the query that filters results based on user input.
     * This runs server-side via AJAX as the user types.
     */
    public function createFilteredQueryBuilder(
        EntityRepository $repository,
        string $query,
    ): QueryBuilder {
        return $repository->createQueryBuilder('p')
            ->leftJoin('p.category', 'c')
            ->andWhere('p.name LIKE :query OR p.sku LIKE :query OR c.name LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->andWhere('p.active = :active')
            ->setParameter('active', true)
            ->orderBy('p.name', 'ASC')
            ->setMaxResults(20);
    }

    /**
     * The label shown in the dropdown and for selected items.
     * HTML is supported here for rich rendering.
     */
    public function getLabel(object $entity): string
    {
        /** @var Product $entity */
        return sprintf(
            '<div class="d-flex justify-content-between align-items-center">'
            . '<div>'
            . '<strong>%s</strong>'
            . '<br><small class="text-muted">SKU: %s | %s</small>'
            . '</div>'
            . '<span class="badge bg-primary">€%s</span>'
            . '</div>',
            htmlspecialchars($entity->getName(), ENT_QUOTES),
            htmlspecialchars($entity->getSku(), ENT_QUOTES),
            htmlspecialchars($entity->getCategory()?->getName() ?? 'Uncategorized', ENT_QUOTES),
            number_format($entity->getPrice(), 2)
        );
    }

    /**
     * The value stored when this option is selected.
     */
    public function getValue(object $entity): string
    {
        return (string) $entity->getId();
    }

    /**
     * Security: check if the current user is allowed to use this autocompleter.
     */
    public function isGranted(Security $security): bool
    {
        // Only authenticated users can search products
        return $security->getUser() !== null;
    }

    /**
     * Optional: group results by a property.
     * Return null for no grouping.
     */
    public function getGroupBy(): mixed
    {
        return 'category.name';
    }
}

/*
Usage in Form:

use Symfony\UX\Autocomplete\Form\BaseEntityAutocompleteType;

$builder->add('product', BaseEntityAutocompleteType::class, [
    'class' => Product::class,
    'autocomplete_url' => '/autocomplete/product',
    'placeholder' => 'Search products by name or SKU...',
]);

Or create a dedicated form type:

#[AsEntityAutocompleteField]
class ProductAutocompleteType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => Product::class,
            'placeholder' => 'Search products...',
        ]);
    }

    public function getParent(): string
    {
        return BaseEntityAutocompleteType::class;
    }
}

// Then:
$builder->add('product', ProductAutocompleteType::class);
*/