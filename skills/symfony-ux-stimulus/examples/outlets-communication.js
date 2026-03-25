// assets/controllers/filter_controller.js
// Demonstrates Outlets for cross-controller communication

import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static outlets = ['product-card']

    static values = {
        category: { type: String, default: 'all' }
    }

    filterByCategory(event) {
        this.categoryValue = event.params.category
    }

    categoryValueChanged(category) {
        this.productCardOutlets.forEach(card => {
            card.applyFilter(category)
        })
    }

    // Called when a new product-card outlet connects
    productCardOutletConnected(outlet, element) {
        // Apply current filter to newly connected card
        outlet.applyFilter(this.categoryValue)
    }

    productCardOutletDisconnected(outlet, element) {
        // Cleanup if needed
    }

    get productCount() {
        return this.productCardOutlets.filter(card => card.isVisible).length
    }
}

// assets/controllers/product_card_controller.js
// This controller is referenced as an outlet by filter_controller

import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = {
        category: String,
        price: Number
    }

    static classes = ['hidden']

    get isVisible() {
        return !this.element.classList.contains(this.hiddenClass)
    }

    applyFilter(category) {
        if (category === 'all' || this.categoryValue === category) {
            this.show()
        } else {
            this.hide()
        }
    }

    show() {
        this.element.classList.remove(this.hiddenClass)
    }

    hide() {
        this.element.classList.add(this.hiddenClass)
    }
}

/*
Usage in Twig:

<div data-controller="filter"
     data-filter-product-card-outlet=".product-card">
    
    <button data-action="filter#filterByCategory"
            data-filter-category-param="all">All</button>
    <button data-action="filter#filterByCategory"
            data-filter-category-param="electronics">Electronics</button>
    <button data-action="filter#filterByCategory"
            data-filter-category-param="clothing">Clothing</button>
</div>

<div class="product-card"
     data-controller="product-card"
     data-product-card-category-value="electronics"
     data-product-card-price-value="299"
     data-product-card-hidden-class="d-none">
    <h3>Laptop</h3>
</div>

<div class="product-card"
     data-controller="product-card"
     data-product-card-category-value="clothing"
     data-product-card-price-value="49"
     data-product-card-hidden-class="d-none">
    <h3>T-Shirt</h3>
</div>
*/
