---
name: symfony-ux-stimulus
description: "Build interactive Symfony applications with Stimulus controllers. TRIGGER when: user works with Stimulus controllers, data-controller attributes, Symfony UX JavaScript, or Hotwire Stimulus in a Symfony project. Covers controllers, values, targets, actions, outlets, CSS classes, and lifecycle callbacks. DO NOT TRIGGER when: user works with React, Vue, or other JS frameworks."
license: Complete terms in LICENSE.txt
---

# Symfony UX Stimulus

Stimulus is a modest JavaScript framework for augmenting HTML you already have. In Symfony, it's integrated via `symfony/stimulus-bundle` and provides the JS layer for all Symfony UX components.

## Installation

```bash
composer require symfony/stimulus-bundle
```

AssetMapper (recommended) or Webpack Encore will auto-register controllers from `assets/controllers/`.

## Core Concepts

### Controllers

A Stimulus controller is a JavaScript class that connects to DOM elements via `data-controller`.

```javascript
// assets/controllers/hello_controller.js
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    connect() {
        this.element.textContent = 'Hello Stimulus!'
    }
}
```

```html
<div data-controller="hello">
    Loading...
</div>
```

**Naming convention:** `hello_controller.js` → `data-controller="hello"`, `content-loader_controller.js` → `data-controller="content-loader"`.

### Actions

Actions connect DOM events to controller methods via `data-action`.

```html
<div data-controller="greet">
    <input data-greet-target="name" type="text">
    <button data-action="click->greet#sayHello">Greet</button>
</div>
```

**Action descriptor format:** `event->controller#method`

Common shortcuts (default events):
- `<button>` → `click`
- `<input>` → `input`
- `<form>` → `submit`
- `<details>` → `toggle`

```html
<!-- These are equivalent for a button -->
<button data-action="click->greet#sayHello">Greet</button>
<button data-action="greet#sayHello">Greet</button>
```

**Action options:**
```html
<!-- Prevent default -->
<form data-action="submit->form#handle:prevent">

<!-- Stop propagation -->
<button data-action="click->nav#close:stop">

<!-- Run once -->
<button data-action="click->alert#show:once">
```

### Targets

Targets let you reference important DOM elements by name.

```javascript
// assets/controllers/search_controller.js
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static targets = ['input', 'results']

    search() {
        const query = this.inputTarget.value
        // this.inputTarget      → first matching element
        // this.inputTargets     → all matching elements (array)
        // this.hasInputTarget   → boolean check
    }
}
```

```html
<div data-controller="search">
    <input data-search-target="input" type="text">
    <div data-search-target="results"></div>
</div>
```

**Target callbacks:**
```javascript
export default class extends Controller {
    static targets = ['item']

    itemTargetConnected(element) {
        // Called when a new target element appears in the DOM
    }

    itemTargetDisconnected(element) {
        // Called when a target element is removed from the DOM
    }
}
```

### Values

Values manage state with automatic type casting and change callbacks.

```javascript
// assets/controllers/loader_controller.js
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static values = {
        url: String,
        refreshInterval: { type: Number, default: 5000 },
        active: { type: Boolean, default: false }
    }

    connect() {
        if (this.activeValue) {
            this.startRefreshing()
        }
    }

    urlValueChanged(newValue, previousValue) {
        // Automatically called when url value changes
        this.fetch()
    }

    startRefreshing() {
        setInterval(() => this.fetch(), this.refreshIntervalValue)
    }

    async fetch() {
        const response = await fetch(this.urlValue)
        // ...
    }
}
```

```html
<div data-controller="loader"
     data-loader-url-value="/api/data"
     data-loader-refresh-interval-value="3000"
     data-loader-active-value="true">
</div>
```

**Value types:** `Array`, `Boolean`, `Number`, `Object`, `String`.

### CSS Classes

CSS classes let you make style references configurable.

```javascript
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static classes = ['active', 'loading']

    toggle() {
        this.element.classList.toggle(this.activeClass)
        // this.activeClass     → single class string
        // this.activeClasses   → array of classes
        // this.hasActiveClass  → boolean check
    }
}
```

```html
<div data-controller="tabs"
     data-tabs-active-class="bg-blue-500 text-white"
     data-tabs-loading-class="opacity-50">
</div>
```

### Outlets

Outlets let controllers reference and communicate with other controllers.

```javascript
// assets/controllers/chat_controller.js
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    static outlets = ['user-status']

    selectAll(event) {
        // Access all connected outlet controller instances
        this.userStatusOutlets.forEach(status => status.markAsSelected(event))
    }

    userStatusOutletConnected(outlet, element) {
        // Called when an outlet is connected
    }

    userStatusOutletDisconnected(outlet, element) {
        // Called when an outlet is disconnected
    }
}
```

```html
<div data-controller="chat" 
     data-chat-user-status-outlet=".user-status">
    <!-- ... -->
</div>

<div class="user-status" data-controller="user-status">
    <!-- ... -->
</div>
```

### Lifecycle Callbacks

```javascript
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    initialize() {
        // Called once when the controller is first instantiated
    }

    connect() {
        // Called every time the controller is connected to the DOM
    }

    disconnect() {
        // Called every time the controller is disconnected from the DOM
        // Clean up: remove event listeners, clear intervals, etc.
    }
}
```

## Symfony Integration Patterns

### Using with Twig `stimulus_controller()`

```twig
<div {{ stimulus_controller('chart', { data: chartData|json_encode }) }}>
    <canvas></canvas>
</div>

{# Multiple controllers #}
<div {{ stimulus_controller('chart')|stimulus_controller('tooltip') }}>
</div>

{# With stimulus_action and stimulus_target #}
<button {{ stimulus_action('modal', 'open') }}>Open</button>
<div {{ stimulus_target('modal', 'content') }}>Content</div>
```

### Lazy Loading Controllers

For performance, controllers can be lazy-loaded (only downloaded when the element appears in the DOM):

```javascript
/* stimulusFetch: 'lazy' */
import { Controller } from '@hotwired/stimulus'

export default class extends Controller {
    connect() {
        // This controller's code is only loaded when the element connects
    }
}
```

### Registering Third-Party Controllers

In `assets/bootstrap.js`:
```javascript
import { startStimulusApp } from '@symfony/stimulus-bundle'

const app = startStimulusApp()

// Register a third-party controller
import TextareaAutogrow from 'stimulus-textarea-autogrow'
app.register('textarea-autogrow', TextareaAutogrow)
```

## Decision Tree

```
Need JavaScript behavior?
├─ Simple DOM manipulation → Stimulus controller
├─ State management on single element → Use Values API
├─ Cross-controller communication → Use Outlets
├─ Reacting to DOM changes → Use Target callbacks
├─ Performance-sensitive (large JS) → Use lazy loading
└─ Server-rendered updates → Combine with Turbo or LiveComponent
```

## Common Pitfalls

- **Don't** use `document.querySelector` — use Targets instead
- **Don't** store state in the DOM — use Values for typed state management
- **Don't** forget to clean up in `disconnect()` (intervals, event listeners, observers)
- **Don't** put business logic in controllers — keep them thin, delegate to services
- **Do** use lifecycle callbacks for setup/teardown
- **Do** use descriptive controller names that reflect behavior, not appearance
- Values are **always strings** in HTML — Stimulus handles type casting automatically

## Reference Files

- **examples/** - Common patterns:
  - `basic-controller.js` - Simple controller with targets and actions
  - `values-and-state.js` - State management with values and change callbacks
  - `outlets-communication.js` - Inter-controller communication via outlets
