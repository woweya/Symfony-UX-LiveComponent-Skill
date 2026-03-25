---
name: symfony-ux-twig-component
description: "Build reusable Twig Components in Symfony with PHP backing classes. TRIGGER when: user works with #[AsTwigComponent], component attributes, slots, component props, or the <twig:ComponentName> syntax. Covers component creation, props, attributes, slots, inline components, and PreMount/PostMount hooks. DO NOT TRIGGER when: user works with LiveComponents (use symfony-ux-live-component skill instead)."
license: Complete terms in LICENSE.txt
---

# Symfony UX Twig Component

Twig Components let you build reusable UI elements with a PHP class and a Twig template. Think of them as PHP-backed, server-rendered web components.

## Installation

```bash
composer require symfony/ux-twig-component
```

## Creating Components

### Basic Component

```php
// src/Twig/Components/Alert.php
namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;

#[AsTwigComponent]
class Alert
{
    public string $type = 'success';
    public string $message;
}
```

```twig
{# templates/components/Alert.html.twig #}
<div class="alert alert-{{ type }}" {{ attributes }}>
    {{ message }}
</div>
```

**Usage (two equivalent syntaxes):**
```twig
{# HTML-like syntax (recommended) #}
<twig:Alert type="danger" message="Something went wrong!" />

{# Function syntax #}
{{ component('Alert', { type: 'danger', message: 'Something went wrong!' }) }}
```

### Naming and Template Conventions

| Class                     | Component Name | Template Path                               |
|---------------------------|----------------|---------------------------------------------|
| `Alert`                   | `Alert`        | `templates/components/Alert.html.twig`      |
| `Button\Primary`          | `Button:Primary` | `templates/components/Button/Primary.html.twig` |

Custom name:
```php
#[AsTwigComponent('ui-alert')]
class Alert { /* ... */ }
// Usage: <twig:ui-alert />
```

Custom template:
```php
#[AsTwigComponent(template: 'ui/alerts/main.html.twig')]
class Alert { /* ... */ }
```

---

## Props

Public properties on the PHP class become component props.

```php
#[AsTwigComponent]
class Card
{
    public string $title;
    public string $subtitle = ''; // Optional with default
    public bool $bordered = true;
    
    // Computed property — available in template via this.fullTitle
    public function getFullTitle(): string
    {
        return $this->subtitle
            ? "{$this->title} — {$this->subtitle}"
            : $this->title;
    }
}
```

```twig
{# templates/components/Card.html.twig #}
<div class="card {{ bordered ? 'card--bordered' : '' }}" {{ attributes }}>
    <h2>{{ this.fullTitle }}</h2>
    {% block content %}{% endblock %}
</div>
```

```twig
{# Usage #}
<twig:Card title="My Card" :bordered="false">
    <p>Card content goes here.</p>
</twig:Card>
```

### Prop Colon Prefix (Dynamic Values)

```twig
{# Static string value #}
<twig:Alert type="success" />

{# Dynamic expression (Twig variable or expression) — use : prefix #}
<twig:Alert :type="alertType" />
<twig:Alert :message="'Hello ' ~ user.name" />
<twig:Alert :bordered="true" />
```

### PreMount Hook

Transform or validate props before they're set:

```php
use Symfony\UX\TwigComponent\Attribute\PreMount;

#[AsTwigComponent]
class DataTable
{
    public array $columns = [];
    public array $rows = [];

    #[PreMount]
    public function preMount(array $data): array
    {
        // Validate or transform props
        if (empty($data['columns'])) {
            throw new \InvalidArgumentException('columns prop is required.');
        }
        
        // You can modify the data before it becomes props
        $data['rows'] = $data['rows'] ?? [];
        
        return $data;
    }
}
```

### PostMount Hook

Run logic after all props are set:

```php
use Symfony\UX\TwigComponent\Attribute\PostMount;

#[AsTwigComponent]
class Navigation
{
    public array $items = [];
    public string $activeItem = '';

    #[PostMount]
    public function postMount(): void
    {
        // Run logic after props are set
        if (empty($this->activeItem) && !empty($this->items)) {
            $this->activeItem = $this->items[0]['slug'];
        }
    }
}
```

---

## Attributes (HTML Pass-Through)

Any props not mapped to PHP properties become HTML attributes, accessible via `{{ attributes }}`.

```php
#[AsTwigComponent]
class Button
{
    public string $variant = 'primary';
    // 'class', 'id', 'disabled', etc. are NOT props → they become attributes
}
```

```twig
{# templates/components/Button.html.twig #}
<button class="btn btn-{{ variant }}" {{ attributes }}>
    {% block content %}{% endblock %}
</button>
```

```twig
{# Usage: class, id, data-* all pass through to attributes #}
<twig:Button variant="danger" class="w-100" id="submit-btn" disabled>
    Delete
</twig:Button>

{# Renders: #}
{# <button class="btn btn-danger w-100" id="submit-btn" disabled>Delete</button> #}
```

### Attribute Manipulation

```twig
{# Set default attributes (class and data-controller are merged, others overridden) #}
<div {{ attributes.defaults({class: 'card', role: 'article'}) }}>
    Content
</div>

{# Only render specific attributes #}
<div {{ attributes.only('id', 'class') }}>Content</div>

{# Exclude specific attributes #}
<div {{ attributes.without('style', 'onclick') }}>Content</div>

{# Render a single attribute value #}
<div style="color: red; {{ attributes.render('style') }}" {{ attributes }}>
    Content
</div>

{# Check if an attribute exists #}
{% if attributes.has('data-loading') %}
    <span class="spinner"></span>
{% endif %}
```

### Nested Attributes

```twig
{# Usage: #}
<twig:Dialog class="modal" title:class="text-bold" body:class="p-4">
    Dialog content
</twig:Dialog>

{# Template: #}
<div {{ attributes }}>
    <div {{ attributes.nested('title') }}>Title</div>
    <div {{ attributes.nested('body') }}>{% block content %}{% endblock %}</div>
</div>
```

---

## Slots (Block Content)

### Default Slot

```twig
{# templates/components/Card.html.twig #}
<div class="card" {{ attributes }}>
    {% block content %}
        {# Default content if nothing is passed #}
        <p>Empty card</p>
    {% endblock %}
</div>
```

```twig
<twig:Card>
    <p>This replaces the default slot content.</p>
</twig:Card>
```

### Named Slots

```twig
{# templates/components/Modal.html.twig #}
<div class="modal" {{ attributes }}>
    <div class="modal-header">
        {% block header %}
            <h5>Default Title</h5>
        {% endblock %}
    </div>
    <div class="modal-body">
        {% block content %}{% endblock %}
    </div>
    <div class="modal-footer">
        {% block footer %}
            <button type="button" class="btn btn-secondary">Close</button>
        {% endblock %}
    </div>
</div>
```

```twig
<twig:Modal>
    <twig:block name="header">
        <h5>Delete Confirmation</h5>
    </twig:block>
    
    <p>Are you sure you want to delete this item?</p>
    
    <twig:block name="footer">
        <button class="btn btn-secondary">Cancel</button>
        <button class="btn btn-danger">Delete</button>
    </twig:block>
</twig:Modal>
```

### Checking if Slot Has Content

```twig
{# templates/components/Card.html.twig #}
<div class="card" {{ attributes }}>
    {% if block('header') is not empty %}
        <div class="card-header">
            {% block header %}{% endblock %}
        </div>
    {% endif %}
    
    <div class="card-body">
        {% block content %}{% endblock %}
    </div>
</div>
```

---

## Anonymous Components (Template-Only)

For simple components without PHP logic:

```twig
{# templates/components/Badge.html.twig #}
{# No PHP class needed! #}

{% props color = 'gray', size = 'md' %}

<span class="badge badge-{{ color }} badge-{{ size }}" {{ attributes }}>
    {% block content %}{% endblock %}
</span>
```

```twig
<twig:Badge color="green" size="lg">Active</twig:Badge>
```

---

## Component Composition

```twig
{# templates/components/FormGroup.html.twig #}
{% props label, required = false, error = null %}

<div class="form-group {{ error ? 'has-error' : '' }}" {{ attributes }}>
    <label>
        {{ label }}
        {% if required %}<span class="text-danger">*</span>{% endif %}
    </label>
    {% block content %}{% endblock %}
    {% if error %}
        <div class="error-message">{{ error }}</div>
    {% endif %}
</div>
```

```twig
{# Composing components together #}
<twig:FormGroup label="Email" :required="true" :error="emailError">
    <twig:Input type="email" name="email" :value="user.email" class="form-control" />
</twig:FormGroup>
```

---

## Stimulus Integration

```twig
{# templates/components/Dropdown.html.twig #}
<div {{ attributes.defaults(stimulus_controller('dropdown')) }}>
    <button {{ stimulus_action('dropdown', 'toggle') }}>
        {% block trigger %}Toggle{% endblock %}
    </button>
    <div {{ stimulus_target('dropdown', 'menu') }} class="dropdown-menu">
        {% block content %}{% endblock %}
    </div>
</div>
```

---

## Decision Tree

```
Need reusable UI element?
├─ No PHP logic needed → Anonymous component (template-only with {% props %})
├─ Props + computed values → Standard TwigComponent
├─ Need prop validation → Use PreMount hook
├─ Need dynamic server-side behavior → Consider LiveComponent instead
├─ Composing several components → Use slots and nested components
└─ Need JS interaction → Add Stimulus controller via attributes
```

## Common Pitfalls

- **Missing `{{ attributes }}`** on root element — Extra HTML attributes won't render
- **Prop vs Attribute** — Declared public properties are props; everything else passes through as HTML attributes
- **`:` prefix for expressions** — `type="success"` is a string; `:type="myVar"` evaluates a Twig expression
- **Slots must use `{% block %}`** — Named slots are Twig blocks, not custom HTML elements
- **Anonymous component `{% props %}`** — Must be the first tag in the template
- **Don't confuse with LiveComponent** — TwigComponent is for static, server-rendered components; LiveComponent adds AJAX reactivity

## Reference Files

- **examples/** - Common patterns:
  - `basic-components.php` - Standard component with props and attributes
  - `slots-and-composition.twig` - Named slots, composition, and anonymous components
