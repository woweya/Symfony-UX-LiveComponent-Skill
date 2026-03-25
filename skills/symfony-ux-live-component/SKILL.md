---
name: symfony-ux-live-component
description: "Build reactive, server-rendered Symfony components with LiveComponent. TRIGGER when: user works with #[AsLiveComponent], #[LiveProp], #[LiveAction], ComponentWithFormTrait, live component rendering, or real-time Twig re-rendering. Covers reactive properties, actions, form integration, lifecycle hooks, polling, and deferred loading. DO NOT TRIGGER when: user works with static Twig Components without live behavior."
license: Complete terms in LICENSE.txt
---

# Symfony UX LiveComponent

LiveComponent allows you to build dynamic, reactive interfaces entirely in PHP and Twig — no custom JavaScript required. Components automatically re-render via AJAX when their state changes, inspired by Laravel Livewire and Phoenix LiveView.

## Installation

```bash
composer require symfony/ux-live-component
```

## Core Concepts

### Creating a LiveComponent

```php
// src/Twig/Components/Counter.php
namespace App\Twig\Components;

use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class Counter
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public int $count = 0;

    #[LiveAction]
    public function increment(): void
    {
        $this->count++;
    }

    #[LiveAction]
    public function decrement(): void
    {
        $this->count--;
    }
}
```

```twig
{# templates/components/Counter.html.twig #}
<div {{ attributes }}>
    <span>Count: {{ count }}</span>
    <button data-action="live#action" data-live-action-param="increment">+</button>
    <button data-action="live#action" data-live-action-param="decrement">-</button>
</div>
```

**Usage:**
```twig
{{ component('Counter', { count: 5 }) }}
```

> **Critical:** The root element MUST include `{{ attributes }}` to wire up the live behavior.

---

## LiveProp — Reactive Properties

`#[LiveProp]` marks a property as part of the component's state that survives re-renders.

### Basic Usage

```php
#[AsLiveComponent]
class UserSearch
{
    use DefaultActionTrait;

    // Read-only prop (cannot be changed from the frontend)
    #[LiveProp]
    public string $status = 'active';

    // Writable prop (can be bound to form inputs)
    #[LiveProp(writable: true)]
    public string $query = '';

    // Writable prop with URL binding
    #[LiveProp(writable: true, url: true)]
    public string $search = '';

    // Entity prop with Doctrine integration
    #[LiveProp(writable: ['email', 'name'])]
    public User $user;
}
```

### Writable Props & Data Binding

```twig
{# templates/components/UserSearch.html.twig #}
<div {{ attributes }}>
    {# Two-way binding: updates query prop on change, triggers re-render #}
    <input
        type="text"
        data-model="query"
        placeholder="Search users..."
    >
    
    {# Debounced input (waits 300ms after typing stops) #}
    <input
        type="text"
        data-model="debounce(300)|query"
        placeholder="Search with debounce..."
    >
    
    {# Update on change event (blur) instead of input #}
    <input
        type="text"
        data-model="on(change)|query"
        placeholder="Search on blur..."
    >
    
    {# Render-less binding (no re-render, just sends data) #}
    <input
        type="text"
        data-model="norender|query"
    >
    
    <ul>
        {% for user in this.getFilteredUsers() %}
            <li>{{ user.name }} - {{ user.email }}</li>
        {% endfor %}
    </ul>
</div>
```

### Prop Hydration

```php
#[AsLiveComponent]
class ProductDetail
{
    use DefaultActionTrait;

    // Doctrine entities are automatically serialized/deserialized by ID
    #[LiveProp]
    public Product $product;

    // DTOs with specific writable fields
    #[LiveProp(writable: ['street', 'city', 'zipCode'])]
    public Address $address;

    // Collections
    #[LiveProp]
    public array $tags = [];

    // Computed property — recalculated on every render, not stored in state
    public function getTotal(): float
    {
        return $this->product->getPrice() * $this->quantity;
    }
}
```

---

## LiveAction — Server-Side Methods

`#[LiveAction]` exposes PHP methods that can be called from the frontend via AJAX.

```php
#[AsLiveComponent]
class TodoList
{
    use DefaultActionTrait;

    #[LiveProp(writable: true)]
    public string $newTodoTitle = '';

    #[LiveProp]
    public array $todos = [];

    #[LiveAction]
    public function addTodo(): void
    {
        if (empty($this->newTodoTitle)) {
            return;
        }

        $this->todos[] = [
            'title' => $this->newTodoTitle,
            'completed' => false,
        ];
        $this->newTodoTitle = '';
    }

    #[LiveAction]
    public function removeTodo(#[LiveArg] int $index): void
    {
        unset($this->todos[$index]);
        $this->todos = array_values($this->todos);
    }

    #[LiveAction]
    public function toggleTodo(#[LiveArg] int $index): void
    {
        $this->todos[$index]['completed'] = !$this->todos[$index]['completed'];
    }
}
```

```twig
{# templates/components/TodoList.html.twig #}
<div {{ attributes }}>
    <input type="text" data-model="newTodoTitle" 
           data-action="keydown.enter->live#action"
           data-live-action-param="addTodo">
    <button data-action="live#action" data-live-action-param="addTodo">Add</button>
    
    <ul>
        {% for index, todo in todos %}
            <li>
                <input type="checkbox" 
                       {{ todo.completed ? 'checked' : '' }}
                       data-action="change->live#action"
                       data-live-action-param="toggleTodo"
                       data-live-index-param="{{ index }}">
                <span class="{{ todo.completed ? 'line-through' : '' }}">
                    {{ todo.title }}
                </span>
                <button data-action="live#action"
                        data-live-action-param="removeTodo"
                        data-live-index-param="{{ index }}">×</button>
            </li>
        {% endfor %}
    </ul>
</div>
```

### Actions with Arguments

```php
#[LiveAction]
public function save(
    #[LiveArg] int $id,
    #[LiveArg] string $status,
    EntityManagerInterface $em  // Services are auto-injected
): void {
    $entity = $em->getRepository(Task::class)->find($id);
    $entity->setStatus($status);
    $em->flush();
}
```

```twig
<button data-action="live#action"
        data-live-action-param="save"
        data-live-id-param="{{ task.id }}"
        data-live-status-param="completed">
    Complete
</button>
```

---

## Form Integration

LiveComponent integrates deeply with Symfony Forms for real-time validation.

### Form Component

```php
// src/Twig/Components/RegistrationForm.php
namespace App\Twig\Components;

use App\Entity\User;
use App\Form\RegistrationType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;
use Doctrine\ORM\EntityManagerInterface;

#[AsLiveComponent]
class RegistrationForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    #[LiveProp]
    public ?User $initialFormData = null;

    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            RegistrationType::class,
            $this->initialFormData ?? new User()
        );
    }

    #[LiveAction]
    public function save(EntityManagerInterface $em): void
    {
        // This submits the form and validates it
        $this->submitForm();

        $form = $this->getForm();
        if (!$form->isValid()) {
            return; // Errors are automatically displayed in the template
        }

        $user = $form->getData();
        $em->persist($user);
        $em->flush();

        $this->addFlash('success', 'User registered!');
        
        // Redirect after successful save
        $this->redirectToRoute('user_list');
    }
}
```

```twig
{# templates/components/RegistrationForm.html.twig #}
<div {{ attributes }}>
    {{ form_start(form, {
        attr: {
            'data-action': 'live#action:prevent',
            'data-live-action-param': 'save'
        }
    }) }}
        {{ form_row(form.email) }}
        {{ form_row(form.username) }}
        {{ form_row(form.password) }}
        
        <button type="submit">Register</button>
    {{ form_end(form) }}
</div>
```

### Real-Time Validation

Validation errors appear automatically as the user types (or on blur) because `data-model` binding triggers a re-render with form validation.

### Dynamic Form Modifications

```php
#[LiveAction]
public function onCountryChange(): void
{
    // Dynamically update form values
    // The form will be re-created with the new country,
    // which may change the "state" field options
    $this->formValues['state'] = '';
}
```

---

## Lifecycle Hooks

### PHP Hooks

```php
use Symfony\UX\LiveComponent\Attribute\PreReRender;
use Symfony\UX\LiveComponent\Attribute\PostHydrate;
use Symfony\UX\LiveComponent\Attribute\PreDehydrate;

#[AsLiveComponent]
class Dashboard
{
    use DefaultActionTrait;

    #[LiveProp]
    public string $filter = 'all';

    // Called before every re-render
    #[PreReRender]
    public function preReRender(): void
    {
        // Prepare data for rendering
    }

    // Called after the component is hydrated from the request
    #[PostHydrate]
    public function postHydrate(): void
    {
        // Component state has been restored
    }

    // Called before the component is dehydrated for the response
    #[PreDehydrate]
    public function preDehydrate(): void
    {
        // Clean up state before serialization
    }
}
```

### JavaScript Hooks

```javascript
// assets/controllers/some-custom-controller.js
import { Controller } from '@hotwired/stimulus'
import { getComponent } from '@symfony/ux-live-component'

export default class extends Controller {
    async initialize() {
        this.component = await getComponent(this.element)

        this.component.on('connect', () => {
            // Component connected to the page
        })

        this.component.on('render:started', (html, response, controls) => {
            // Before the new HTML is applied
        })

        this.component.on('render:finished', (component) => {
            // After the component has re-rendered
        })

        this.component.on('disconnect', () => {
            // Component removed from the page
        })

        this.component.on('model:set', (model, value, component) => {
            // A model value was changed
        })
    }
}
```

---

## Polling & Deferred Loading

### Polling (Auto-Refresh)

```twig
{# Re-render every 2 seconds #}
<div {{ attributes }} data-poll>
    Last updated: {{ 'now'|date('H:i:s') }}
</div>

{# Custom interval #}
<div {{ attributes }} data-poll="delay(5000)">
    {{ notifications|length }} new notifications
</div>
```

### Deferred / Lazy Loading

```php
#[AsLiveComponent]
class HeavyReport
{
    use DefaultActionTrait;
    
    // Component renders a placeholder first, 
    // then loads the real content via AJAX
    public bool $isLoading = true;

    #[LiveProp]
    public array $reportData = [];

    #[LiveAction]
    public function loadReport(ReportService $service): void
    {
        $this->reportData = $service->generate();
        $this->isLoading = false;
    }
}
```

```twig
<div {{ attributes }}>
    {% if isLoading %}
        <div data-loading>
            <span class="spinner"></span> Generating report...
        </div>
    {% else %}
        {# Render the actual report data #}
        <table>
            {% for row in reportData %}
                <tr><td>{{ row.name }}</td><td>{{ row.value }}</td></tr>
            {% endfor %}
        </table>
    {% endif %}
</div>
```

### Loading States

```twig
<div {{ attributes }}>
    <button data-action="live#action" data-live-action-param="save">
        <span data-loading="action(save)|hide">Save</span>
        <span data-loading="action(save)|show" class="d-none">Saving...</span>
    </button>
    
    {# Add class during loading #}
    <div data-loading="addClass(opacity-50)">
        Content that fades during re-render
    </div>
    
    {# Disable element during loading #}
    <button data-loading="attr(disabled)">Submit</button>
</div>
```

---

## Component Communication

### Emitting Events

```php
use Symfony\UX\LiveComponent\Attribute\LiveListener;

#[AsLiveComponent]
class ChildComponent
{
    use DefaultActionTrait;

    #[LiveAction]
    public function save(ComponentToolkit $toolkit): void
    {
        // Emit an event that parent/sibling components can listen to
        $toolkit->emit('itemSaved', ['id' => 42]);
    }
}

#[AsLiveComponent]
class ParentComponent
{
    use DefaultActionTrait;

    #[LiveListener('itemSaved')]
    public function onItemSaved(#[LiveArg] int $id): void
    {
        // React to the event from the child
        $this->refreshList();
    }
}
```

---

## Decision Tree

```
Need dynamic behavior?
├─ Simple reactive state → LiveProp(writable: true) + data-model
├─ Server-side logic → LiveAction
├─ Form with real-time validation → ComponentWithFormTrait
├─ Auto-refresh data → data-poll
├─ Cross-component communication → emit/LiveListener
├─ Custom JS interaction → getComponent() JS API
└─ Need more JS control → Combine with Stimulus controller
```

## Common Pitfalls

- **Missing `{{ attributes }}`** — The root element MUST have `{{ attributes }}` for live behavior to work
- **Missing `DefaultActionTrait`** — Always include `use DefaultActionTrait` in LiveComponents
- **Writable vs readonly** — Only `#[LiveProp(writable: true)]` props can be modified from the frontend
- **Entity serialization** — Doctrine entities are serialized by ID; make sure entities are persisted
- **Form re-creation** — `instantiateForm()` runs on every request; keep it deterministic
- **Don't call `$this->submitForm()` then read form data** — Modify `$this->formValues` array instead
- **Loading states** — Use `data-loading` attributes for UX feedback during re-renders
- Multiple `data-model` on the same input are not supported — use modifiers like `debounce()` or `on(change)` together

## Reference Files

- **examples/** - Common patterns:
  - `basic-live-component.php` - Simple reactive component with LiveProp and LiveAction
  - `form-component.php` - Complete form integration with validation
  - `component-communication.php` - Event-based communication between components
