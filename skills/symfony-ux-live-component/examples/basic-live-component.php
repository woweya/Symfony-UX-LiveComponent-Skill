<?php
// ============================================================
// Basic LiveComponent - Reactive Counter with Search
// ============================================================

namespace App\Twig\Components;

use App\Repository\ProductRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ProductSearch
{
    use DefaultActionTrait;

    #[LiveProp(writable: true, url: true)]
    public string $query = '';

    #[LiveProp(writable: true)]
    public string $category = 'all';

    #[LiveProp(writable: true)]
    public string $sortBy = 'name';

    #[LiveProp]
    public int $page = 1;

    public function __construct(
        private ProductRepository $productRepository,
    ) {
    }

    /**
     * This method is called in the template to get filtered results.
     * It's recalculated on every render.
     */
    public function getProducts(): array
    {
        return $this->productRepository->findByFilters(
            query: $this->query,
            category: $this->category === 'all' ? null : $this->category,
            sortBy: $this->sortBy,
            page: $this->page,
        );
    }

    public function getTotalResults(): int
    {
        return $this->productRepository->countByFilters(
            query: $this->query,
            category: $this->category === 'all' ? null : $this->category,
        );
    }

    #[LiveAction]
    public function loadMore(): void
    {
        $this->page++;
    }

    #[LiveAction]
    public function resetFilters(): void
    {
        $this->query = '';
        $this->category = 'all';
        $this->sortBy = 'name';
        $this->page = 1;
    }

    #[LiveAction]
    public function addToCart(#[LiveArg] int $productId): void
    {
        // Add product to cart logic...
        // The component will re-render after this action
    }
}

/*
Template: templates/components/ProductSearch.html.twig

<div {{ attributes }}>
    <div class="filters">
        <input type="text"
               data-model="debounce(300)|query"
               placeholder="Search products..."
               value="{{ query }}">

        <select data-model="category">
            <option value="all">All Categories</option>
            <option value="electronics" {{ category == 'electronics' ? 'selected' }}>Electronics</option>
            <option value="clothing" {{ category == 'clothing' ? 'selected' }}>Clothing</option>
            <option value="books" {{ category == 'books' ? 'selected' }}>Books</option>
        </select>

        <select data-model="sortBy">
            <option value="name" {{ sortBy == 'name' ? 'selected' }}>Name</option>
            <option value="price_asc" {{ sortBy == 'price_asc' ? 'selected' }}>Price: Low to High</option>
            <option value="price_desc" {{ sortBy == 'price_desc' ? 'selected' }}>Price: High to Low</option>
        </select>

        <button data-action="live#action" data-live-action-param="resetFilters">
            Reset
        </button>
    </div>

    <p>{{ this.getTotalResults() }} results found</p>

    <div class="products" data-loading="addClass(opacity-50)">
        {% for product in this.getProducts() %}
            <div class="product-card">
                <h3>{{ product.name }}</h3>
                <p>€{{ product.price|number_format(2) }}</p>
                <button data-action="live#action"
                        data-live-action-param="addToCart"
                        data-live-product-id-param="{{ product.id }}"
                        data-loading="attr(disabled)">
                    Add to Cart
                </button>
            </div>
        {% endfor %}
    </div>

    {% if this.getProducts()|length >= 20 %}
        <button data-action="live#action" data-live-action-param="loadMore">
            Load More
        </button>
    {% endif %}
</div>

Usage in any template:
{{ component('ProductSearch') }}
{{ component('ProductSearch', { category: 'electronics' }) }}
*/
