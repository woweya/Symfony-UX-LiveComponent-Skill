<?php
// ============================================================
// LiveComponent Communication - Events Between Components
// ============================================================

namespace App\Twig\Components;

use App\Repository\CartItemRepository;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveArg;
use Symfony\UX\LiveComponent\Attribute\LiveListener;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentToolkit;
use Symfony\UX\LiveComponent\DefaultActionTrait;

// ============================================================
// Component 1: Product List (emits events)
// ============================================================

#[AsLiveComponent]
class ProductList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $filter = '';

    public function __construct(
        private CartItemRepository $cartRepo,
    ) {
    }

    #[LiveAction]
    public function addToCart(
        #[LiveArg] int $productId,
        #[LiveArg] int $quantity,
        ComponentToolkit $toolkit,
    ): void {
        $this->cartRepo->addProduct($productId, $quantity);

        // Emit event to sibling/parent components
        $toolkit->emit('cart:updated', [
            'productId' => $productId,
            'quantity' => $quantity,
        ]);

        // Emit to components UP the tree only
        // $toolkit->emitUp('cart:updated', [...]);

        // Emit to a specific component by name
        // $toolkit->emitTo('CartWidget', 'cart:updated', [...]);
    }
}

// ============================================================
// Component 2: Cart Widget (listens for events)
// ============================================================

#[AsLiveComponent]
class CartWidget
{
    use DefaultActionTrait;

    #[LiveProp]
    public int $itemCount = 0;

    #[LiveProp]
    public float $total = 0.0;

    public function __construct(
        private CartItemRepository $cartRepo,
    ) {
    }

    // Mount is called on initial render
    public function mount(): void
    {
        $this->refreshCart();
    }

    /**
     * Listen for the 'cart:updated' event emitted by ProductList.
     * This triggers a re-render of this component when the event fires.
     */
    #[LiveListener('cart:updated')]
    public function onCartUpdated(
        #[LiveArg] int $productId,
        #[LiveArg] int $quantity,
    ): void {
        $this->refreshCart();
    }

    /**
     * Listen for 'cart:item_removed' event
     */
    #[LiveListener('cart:item_removed')]
    public function onItemRemoved(): void
    {
        $this->refreshCart();
    }

    #[LiveAction]
    public function removeItem(
        #[LiveArg] int $itemId,
        ComponentToolkit $toolkit,
    ): void {
        $this->cartRepo->removeItem($itemId);
        $this->refreshCart();

        // Notify other components
        $toolkit->emit('cart:item_removed', ['itemId' => $itemId]);
    }

    private function refreshCart(): void
    {
        $this->itemCount = $this->cartRepo->getItemCount();
        $this->total = $this->cartRepo->getTotal();
    }
}

// ============================================================
// Component 3: Notification Banner (listens globally)
// ============================================================

#[AsLiveComponent]
class NotificationBanner
{
    use DefaultActionTrait;

    #[LiveProp]
    public ?string $message = null;

    #[LiveProp]
    public string $type = 'info';

    #[LiveListener('cart:updated')]
    public function onCartUpdated(#[LiveArg] int $quantity): void
    {
        $this->message = "Added {$quantity} item(s) to your cart!";
        $this->type = 'success';
    }

    #[LiveListener('cart:item_removed')]
    public function onItemRemoved(): void
    {
        $this->message = 'Item removed from cart.';
        $this->type = 'warning';
    }

    #[LiveAction]
    public function dismiss(): void
    {
        $this->message = null;
    }
}

/*
Layout template showing all three components together:

{# templates/shop/index.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    {# Global notification banner #}
    {{ component('NotificationBanner') }}
    
    <div class="row">
        <div class="col-9">
            {# Product list that emits events #}
            {{ component('ProductList') }}
        </div>
        <div class="col-3">
            {# Cart widget that listens for events #}
            {{ component('CartWidget') }}
        </div>
    </div>
{% endblock %}


Cart Widget Template: templates/components/CartWidget.html.twig

<div {{ attributes }} class="cart-widget">
    <h3>🛒 Cart ({{ itemCount }})</h3>
    <p>Total: €{{ total|number_format(2) }}</p>
    
    {% for item in this.getItems() %}
        <div class="cart-item">
            <span>{{ item.name }}</span>
            <button data-action="live#action"
                    data-live-action-param="removeItem"
                    data-live-item-id-param="{{ item.id }}">
                Remove
            </button>
        </div>
    {% endfor %}
</div>


Notification Template: templates/components/NotificationBanner.html.twig

<div {{ attributes }}>
    {% if message %}
        <div class="alert alert-{{ type }} d-flex justify-content-between">
            <span>{{ message }}</span>
            <button data-action="live#action" data-live-action-param="dismiss">×</button>
        </div>
    {% endif %}
</div>
*/
