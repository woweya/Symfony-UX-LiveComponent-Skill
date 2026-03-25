// assets/controllers/search_controller.js
// Basic Stimulus controller with targets, actions, and values

import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['input', 'results', 'count']
    
    static values = {
        url: String,
        minLength: { type: Number, default: 3 }
    }

    search() {
        const query = this.inputTarget.value
        
        if (query.length < this.minLengthValue) {
            this.resultsTarget.innerHTML = ''
            this.countTarget.textContent = '0 results'
            return
        }

        this.fetchResults(query)
    }

    async fetchResults(query) {
        const response = await fetch(`${this.urlValue}?q=${encodeURIComponent(query)}`)
        const data = await response.json()

        this.resultsTarget.innerHTML = data.results
            .map(item => `<li>${item.name}</li>`)
            .join('')

        this.countTarget.textContent = `${data.results.length} results`
    }

    clear() {
        this.inputTarget.value = ''
        this.resultsTarget.innerHTML = ''
        this.countTarget.textContent = '0 results'
    }
}

/*
Usage in Twig:

<div data-controller="search"
     data-search-url-value="{{ path('api_search') }}"
     data-search-min-length-value="2">
    
    <input data-search-target="input"
           data-action="input->search#search"
           type="text" 
           placeholder="Search...">
    
    <button data-action="search#clear">Clear</button>
    
    <span data-search-target="count">0 results</span>
    <ul data-search-target="results"></ul>
</div>
*/
