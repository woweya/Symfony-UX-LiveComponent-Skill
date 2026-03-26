---
name: symfony-ux-turbo
description: "Build SPA-like experiences in Symfony with Turbo (Drive, Frames, Streams) and Mercure broadcasting. TRIGGER when: user works with Turbo Frames, Turbo Streams, turbo_stream_listen, Mercure broadcasting, or #[Broadcast] attribute in Symfony. Covers Turbo Drive, Frames, Streams, and real-time broadcasting. DO NOT TRIGGER when: user works with traditional full-page reloads or non-Symfony Hotwire projects."
license: Complete terms in LICENSE.txt
---

# Symfony UX Turbo

Symfony UX Turbo integrates the Hotwire Turbo library into Symfony. It enables SPA-like navigation without writing JavaScript, and real-time broadcasting of DOM changes via Mercure or other transports.

## Installation

```bash
composer require symfony/ux-turbo

# For broadcasting (optional)
composer require symfony/mercure-bundle
```

Turbo is automatically activated. Add `{{ importmap('app') }}` (or Encore) in your base layout.

## Core Architecture

Turbo has three main techniques:

1. **Turbo Drive** — Accelerates links and form submissions (automatic)
2. **Turbo Frames** — Decomposes pages into independent contexts
3. **Turbo Streams** — Delivers page changes over WebSocket or HTTP

---

## Turbo Drive

Turbo Drive intercepts link clicks and form submissions, performs them via `fetch`, and replaces the `<body>` without a full page reload.

**It works automatically.** No changes required to existing Symfony controllers or templates.

### Opting out

```html
<!-- Disable for a single link -->
<a href="/legacy" data-turbo="false">Legacy Page</a>

<!-- Disable for an entire section -->
<div data-turbo="false">
    <a href="/legacy1">Link 1</a>
    <a href="/legacy2">Link 2</a>
</div>
```

### Progress bar

Turbo shows a progress bar for requests taking longer than 500ms. Customize it with CSS:

```css
.turbo-progress-bar {
    height: 5px;
    background-color: #667eea;
}
```

---

## Turbo Frames

Turbo Frames scope navigation to a specific part of the page. Only the content inside matching `<turbo-frame>` elements is swapped.

### Basic Frame

```twig
{# templates/home.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Home</h1>
    
    <turbo-frame id="notifications">
        <a href="{{ path('notifications_list') }}">View Notifications</a>
        {# When clicked, only this frame's content updates #}
    </turbo-frame>
    
    <p>This content never changes when the frame navigates.</p>
{% endblock %}
```

```twig
{# templates/notifications/list.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>This heading is ignored (outside the frame)</h1>
    
    <turbo-frame id="notifications">
        <ul>
            {% for notification in notifications %}
                <li>{{ notification.message }}</li>
            {% endfor %}
        </ul>
    </turbo-frame>
{% endblock %}
```

### Lazy Loading Frames

Frames with `src` load their content asynchronously:

```twig
<turbo-frame id="weather" src="{{ path('weather_widget') }}">
    <p>Loading weather...</p>
</turbo-frame>
```

### Breaking Out of Frames

Links can target the whole page or specific frames:

```twig
<turbo-frame id="messages">
    {# Break out to full page navigation #}
    <a href="{{ path('message_show', {id: msg.id}) }}" data-turbo-frame="_top">
        View Full Message
    </a>
    
    {# Target a different frame #}
    <a href="{{ path('message_detail', {id: msg.id}) }}" data-turbo-frame="detail">
        Show in Detail Panel
    </a>
</turbo-frame>
```

### Frame with Form Submission

```twig
<turbo-frame id="comment_form">
    {{ form_start(form) }}
        {{ form_widget(form) }}
        <button type="submit">Post Comment</button>
    {{ form_end(form) }}
    {# After submit, only this frame's content is swapped #}
</turbo-frame>
```

---

## Turbo Streams

Turbo Streams deliver targeted DOM updates. They can be returned from HTTP responses or broadcast over WebSocket (Mercure).

### Stream Actions

| Action    | Description                          |
|-----------|--------------------------------------|
| `append`  | Append content to a container        |
| `prepend` | Prepend content to a container       |
| `replace` | Replace an entire element            |
| `update`  | Update the innerHTML of an element   |
| `remove`  | Remove an element                    |
| `before`  | Insert content before an element     |
| `after`   | Insert content after an element      |

### Returning Streams from Controllers

```php
// src/Controller/CommentController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends AbstractController
{
    public function create(): Response
    {
        // Process the comment...
        
        // Return a Turbo Stream response
        return $this->render('comment/stream.html.twig', [
            'comment' => $comment,
        ], new Response('', 200, [
            'Content-Type' => 'text/vnd.turbo-stream.html',
        ]));
    }
}
```

```twig
{# templates/comment/stream.html.twig #}
<turbo-stream action="append" targets="#comments">
    <template>
        <div id="comment_{{ comment.id }}" class="comment">
            <strong>{{ comment.author }}</strong>
            <p>{{ comment.body }}</p>
        </div>
    </template>
</turbo-stream>
```

### Detecting Turbo Stream Requests

```php
use Symfony\Component\HttpFoundation\Request;

public function create(Request $request): Response
{
    // Check if the request accepts Turbo Streams
    if ($request->getPreferredFormat() === 'text/vnd.turbo-stream.html') {
        // Return stream response
    }
    
    // Fallback to regular response
    return $this->redirectToRoute('comments_list');
}
```

---

## Broadcasting with Mercure

The `#[Broadcast]` attribute automatically broadcasts entity changes to connected clients in real-time.

### Setup

```yaml
# config/packages/mercure.yaml
mercure:
    hubs:
        default:
            url: '%env(MERCURE_URL)%'
            public_url: '%env(MERCURE_PUBLIC_URL)%'
            jwt:
                secret: '%env(MERCURE_JWT_SECRET)%'
                publish: ['*']
```

### Entity Broadcasting

```php
// src/Entity/Message.php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Turbo\Attribute\Broadcast;

#[ORM\Entity]
#[Broadcast]
class Message
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private string $content;

    // getters/setters...
}
```

### Broadcast Template

```twig
{# templates/broadcast/Message.stream.html.twig #}
{% block create %}
    <turbo-stream action="append" targets="#messages">
        <template>
            <div id="message_{{ id }}">{{ entity.content }}</div>
        </template>
    </turbo-stream>
{% endblock %}

{% block update %}
    <turbo-stream action="replace" targets="#message_{{ id }}">
        <template>
            <div id="message_{{ id }}">{{ entity.content }}</div>
        </template>
    </turbo-stream>
{% endblock %}

{% block remove %}
    <turbo-stream action="remove" targets="#message_{{ id }}"></turbo-stream>
{% endblock %}
```

### Listening for Broadcasts

```twig
{# templates/chat/index.html.twig #}
{% extends 'base.html.twig' %}

{% block body %}
    <h1>Chat</h1>
    
    <div id="messages" {{ turbo_stream_listen('App\\Entity\\Message') }}>
        {% for message in messages %}
            <div id="message_{{ message.id }}">{{ message.content }}</div>
        {% endfor %}
    </div>
    
    <turbo-frame id="message_form">
        {{ form(form) }}
    </turbo-frame>
{% endblock %}
```

### Advanced Broadcast Options

```php
use Symfony\UX\Turbo\Attribute\Broadcast;

// Custom topic and template
#[Broadcast(
    topics: ['chat_room_messages'],
    template: 'broadcast/chat_message.stream.html.twig',
    private: true
)]
class ChatMessage { /* ... */ }

// Multiple broadcast targets
#[Broadcast(topics: ['@="book_detail_" ~ entity.getId()'], template: 'broadcast/book_detail.stream.html.twig')]
#[Broadcast(topics: ['books'], template: 'broadcast/book_list.stream.html.twig')]
class Book { /* ... */ }
```

### Programmatic Publishing

```php
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

class NotificationController extends AbstractController
{
    public function notify(HubInterface $hub): Response
    {
        $update = new Update(
            'notifications',
            '<turbo-stream action="append" targets="#notifications">
                <template><div>New notification!</div></template>
            </turbo-stream>'
        );

        $hub->publish($update);

        return new Response('Published.');
    }
}
```

---

## JavaScript Event Interception

Turbo dispatches custom DOM events you can listen to for controlling navigation, rendering, and network behavior.

### Navigation Events

```javascript
// Fires before visiting a location (cancelable)
document.addEventListener('turbo:before-visit', (event) => {
    if (!confirm('Leave this page?')) {
        event.preventDefault(); // Cancels the visit
    }
    console.log(event.detail.url); // The URL being visited
});

// Fires when a visit starts
document.addEventListener('turbo:visit', (event) => {
    console.log(event.detail.url, event.detail.action); // "advance" | "replace"
});

// Fires after the page is fully loaded (equivalent of DOMContentLoaded for Turbo)
document.addEventListener('turbo:load', (event) => {
    // Re-initialize third-party libraries here
    console.log(event.detail.url, event.detail.timing);
});
```

### Rendering Events

```javascript
// Before the new body is rendered (allows customizing render)
document.addEventListener('turbo:before-render', (event) => {
    // Access the new body before it replaces the current one
    const newBody = event.detail.newBody;

    // Custom render function
    event.detail.render = async (currentElement, newElement) => {
        // Custom animation or transition logic
        await animateOut(currentElement);
        currentElement.replaceWith(newElement);
        await animateIn(newElement);
    };
});

// After the new body has been rendered
document.addEventListener('turbo:render', (event) => {
    console.log('Page rendered');
});
```

### Frame Events

```javascript
// Fires when frame navigation starts
document.addEventListener('turbo:before-frame-render', (event) => {
    const newFrame = event.detail.newFrame;
    console.log('Frame about to render:', newFrame.id);
});

// Fires after a frame finishes loading
document.addEventListener('turbo:frame-load', (event) => {
    console.log('Frame loaded:', event.target.id);
});

// Fires when the response doesn't contain a matching frame
document.addEventListener('turbo:frame-missing', (event) => {
    event.detail.visit(event.detail.response); // Visit the full page instead
    event.preventDefault();
});
```

### Form Submission Events

```javascript
// Before a form is submitted (the fetch hasn't started yet)
document.addEventListener('turbo:submit-start', (event) => {
    event.target.querySelector('[type=submit]').disabled = true;
    console.log(event.detail.formSubmission);
});

// After the form submission response is received
document.addEventListener('turbo:submit-end', (event) => {
    event.target.querySelector('[type=submit]').disabled = false;
    if (event.detail.success) {
        console.log('Form submitted successfully');
    }
});
```

### Turbo Stream Events

```javascript
// Before a Turbo Stream element is rendered
document.addEventListener('turbo:before-stream-render', (event) => {
    const stream = event.target; // The <turbo-stream> element
    console.log(stream.action, stream.target);

    // Override the default render
    event.detail.render = (streamElement) => {
        // Custom stream rendering logic
    };
});
```

### Morphing Events (Turbo 8+)

```javascript
// Before an element is morphed (cancelable)
document.addEventListener('turbo:before-morph-element', (event) => {
    if (event.target.classList.contains('no-morph')) {
        event.preventDefault(); // Skip morphing this element
    }
});

// Before an attribute is updated during morphing
document.addEventListener('turbo:before-morph-attribute', (event) => {
    console.log(event.detail.attributeName);
    event.preventDefault(); // Prevent this attribute from being morphed
});

// After an element is morphed
document.addEventListener('turbo:morph-element', (event) => {
    console.log('Element morphed:', event.target);
});
```

### Network / Fetch Events

```javascript
// Intercept the fetch request before it's made
document.addEventListener('turbo:before-fetch-request', (event) => {
    // Add custom headers
    event.detail.fetchOptions.headers['X-Custom-Header'] = 'value';
    event.detail.url; // Modify the URL if needed
});

// After the fetch response is received
document.addEventListener('turbo:before-fetch-response', (event) => {
    const response = event.detail.fetchResponse;
    if (response.statusCode === 401) {
        event.preventDefault();
        window.location.href = '/login';
    }
});
```

### Click Events

```javascript
// Fires when a Turbo-eligible link is clicked (cancelable)
document.addEventListener('turbo:click', (event) => {
    console.log(event.target, event.detail.url);
    // event.preventDefault() to cancel navigation
});
```

---

## HTML Data Attributes Reference

These `data-*` attributes control Turbo behavior on individual elements:

| Attribute | Values | Description |
|---|---|---|
| `data-turbo` | `"true"` / `"false"` | Enable/disable Turbo on an element and its descendants |
| `data-turbo-action` | `"advance"` / `"replace"` / `"restore"` | Override the navigation action for a link or form |
| `data-turbo-confirm` | `"Are you sure?"` | Show a confirmation dialog before navigating or submitting |
| `data-turbo-method` | `"post"` / `"put"` / `"patch"` / `"delete"` | Turn a link into a non-GET request |
| `data-turbo-stream` | (boolean attribute) | Accept Turbo Stream responses on a form/link |
| `data-turbo-prefetch` | `"true"` / `"false"` | Enable/disable link prefetching on hover |
| `data-turbo-permanent` | (boolean attribute) | Preserve an element across page navigations (must have `id`) |
| `data-turbo-temporary` | (boolean attribute) | Remove element before caching (useful for flash messages) |
| `data-turbo-root` | CSS selector | Restrict Turbo Drive to links within a container |
| `data-turbo-frame` | frame ID / `"_top"` | Target a specific frame, or `_top` to break out of the frame |
| `data-turbo-preload` | (boolean attribute) | Preload a frame's `src` eagerly in the background |

### Examples

```html
<!-- Non-GET link (e.g., logout) -->
<a href="{{ path('app_logout') }}" data-turbo-method="delete">Logout</a>

<!-- Confirmation dialog before destructive action -->
<a href="{{ path('app_delete', { id: item.id }) }}"
   data-turbo-method="delete"
   data-turbo-confirm="Delete this item?">
    Delete
</a>

<!-- Accept Turbo Stream response from a form -->
<form action="{{ path('app_create') }}" method="post" data-turbo-stream>
    {{ include('_form.html.twig') }}
    <button type="submit">Create</button>
</form>

<!-- Persistent element across navigations (e.g., audio player) -->
<div id="audio-player" data-turbo-permanent>
    <audio src="..."></audio>
</div>

<!-- Restrict Turbo Drive to sidebar links only -->
<nav data-turbo-root>
    <a href="/dashboard">Dashboard</a>
    <a href="/settings">Settings</a>
</nav>

<!-- Break out of a frame into full-page navigation -->
<a href="/full-page" data-turbo-frame="_top">View Full Page</a>

<!-- Lazy loading a frame -->
<turbo-frame id="comments" src="{{ path('app_comments') }}" loading="lazy">
    Loading comments...
</turbo-frame>
```

---

## Head Meta Tags

Control Turbo behavior at the page level with `<meta>` tags in your `<head>`:

### Cache Control

```html
<!-- Skip the cache entirely for this page -->
<meta name="turbo-cache-control" content="no-cache">

<!-- Cache the page but don't show the preview (stale content) before fetching -->
<meta name="turbo-cache-control" content="no-preview">
```

### Prefetch Control

```html
<!-- Disable prefetching globally (Turbo 8+) -->
<meta name="turbo-prefetch" content="false">
```

### Asset Tracking

```html
<!-- Force a full reload when tracked assets change (e.g., after a deploy) -->
<link rel="stylesheet" href="/style.css?v=123" data-turbo-track="reload">
<script src="/app.js?v=123" data-turbo-track="reload"></script>
```

When `data-turbo-track="reload"` detects a change in the asset URL, Turbo performs a full page reload instead of a partial update, ensuring users get the latest version.

### Page Refresh Method (Turbo 8+)

```html
<!-- Use morphing (instead of full body replacement) for page refreshes -->
<meta name="turbo-refresh-method" content="morph">

<!-- Control scroll behavior: "reset" (default, scroll to top) or "preserve" -->
<meta name="turbo-refresh-scroll" content="preserve">
```

---

## Programmatic JavaScript API

### Navigation

```javascript
import * as Turbo from '@hotwired/turbo';

// Programmatic visit
Turbo.visit('/dashboard');
Turbo.visit('/dashboard', { action: 'replace' }); // No new history entry
Turbo.visit('/dashboard', { frame: 'main-content' }); // Target a specific frame

// Manipulate frames programmatically
const frame = document.querySelector('turbo-frame#comments');
frame.src = '/new-comments-url'; // Navigate the frame
frame.reload(); // Reload the frame's current src
frame.disabled = true; // Disable the frame (clicks navigate the whole page)
```

### Turbo Streams via SSE (Server-Sent Events)

```javascript
import { connectStreamSource, disconnectStreamSource } from '@hotwired/turbo';

// Connect an EventSource to receive Turbo Streams in real-time
const eventSource = new EventSource('/stream-updates');
connectStreamSource(eventSource);

// Later, disconnect
disconnectStreamSource(eventSource);
```

### Rendering Streams Programmatically

```javascript
import { renderStreamMessage } from '@hotwired/turbo';

// Render a Turbo Stream from a string (e.g., received via WebSocket)
const streamHTML = `
    <turbo-stream action="append" target="messages">
        <template><div>New message!</div></template>
    </turbo-stream>
`;
renderStreamMessage(streamHTML);
```

### Morphing API (Turbo 8+)

```javascript
import { morphElements, morphChildren } from '@hotwired/turbo';

// Morph a full element (replaces the element and its children)
morphElements(currentElement, newElement);

// Morph only the children of an element (keeps the parent intact)
morphChildren(currentElement, newElement);
```

---

## Twig Component Syntax

When using `symfony/ux-twig-component`, Turbo provides HTML-like Twig components:

### Turbo Frames

```twig
{# Equivalent to <turbo-frame id="my-frame"> #}
<twig:Turbo:Frame id="my-frame">
    Content here...
</twig:Turbo:Frame>

{# Lazy-loaded frame #}
<twig:Turbo:Frame id="sidebar" src="{{ path('app_sidebar') }}" loading="lazy">
    Loading...
</twig:Turbo:Frame>
```

### Turbo Streams

```twig
{# Stream actions as Twig components #}
<twig:Turbo:Stream:Append target="messages">
    <div>New message</div>
</twig:Turbo:Stream:Append>

<twig:Turbo:Stream:Prepend target="notifications">
    <div>Alert!</div>
</twig:Turbo:Stream:Prepend>

<twig:Turbo:Stream:Replace target="status-badge">
    <span class="badge badge-success">Online</span>
</twig:Turbo:Stream:Replace>

<twig:Turbo:Stream:Update target="counter">
    42
</twig:Turbo:Stream:Update>

<twig:Turbo:Stream:Remove target="deleted-item" />

<twig:Turbo:Stream:Before target="reference-item">
    <div>Inserted before</div>
</twig:Turbo:Stream:Before>

<twig:Turbo:Stream:After target="reference-item">
    <div>Inserted after</div>
</twig:Turbo:Stream:After>

{# Page refresh stream (Turbo 8+ morphing) #}
<twig:Turbo:Stream:Refresh />
```

---

## Stream Morphing (Turbo 8+)

Turbo 8 supports `method="morph"` on Stream actions, which uses DOM morphing instead of replacing:

```html
<!-- Morph an element (preserves internal state like form inputs) -->
<turbo-stream action="replace" target="user-profile" method="morph">
    <template>
        <div id="user-profile">
            <h2>Updated Name</h2>
            <p>Updated content — form inputs and focus are preserved</p>
        </div>
    </template>
</turbo-stream>

<!-- Morph only the children of the target -->
<turbo-stream action="update" target="stats-panel" method="morph">
    <template>
        <span>New stats content</span>
    </template>
</turbo-stream>
```

`method="morph"` is especially useful when updating complex UIs where preserving user state (scroll position, input values, focus) matters.

---

## Decision Tree

```
Need to update part of the page?
├─ Automatic page transition → Turbo Drive (nothing to do)
├─ Scoped navigation/forms → Turbo Frames
│   ├─ Lazy loading content → <turbo-frame src="...">
│   └─ In-place form editing → Frame wrapping the form
├─ Targeted DOM manipulation → Turbo Streams
│   ├─ From HTTP response → Return text/vnd.turbo-stream.html
│   └─ Real-time updates → Mercure + #[Broadcast]
└─ Need JS logic too → Combine with Stimulus controllers
```

## Common Pitfalls

- **Frame ID mismatch** — The `id` on `<turbo-frame>` must match between the current page and the response page
- **Missing `<turbo-frame>` in response** — If the response doesn't contain a matching frame, Turbo shows an error; use `turbo:frame-missing` to handle gracefully
- **Broadcast template location** — Templates must be in `templates/broadcast/EntityName.stream.html.twig`
- **Stream format** — `targets` uses CSS selector (e.g., `#id`), not just the element id
- **Form in Frame** — Wrap the form in a `<turbo-frame>` to get scoped form submission
- Turbo Drive is **on by default** — use `data-turbo="false"` to opt out per link/section
- For Mercure broadcasting, ensure the Mercure hub is running and accessible
- **`data-turbo-permanent` requires `id`** — The element MUST have a stable `id` attribute or it won't be preserved
- **Third-party JS re-initialization** — Libraries that bind on `DOMContentLoaded` won't re-run on Turbo navigations; use `turbo:load` or Stimulus instead
- **`data-turbo-stream` on forms** — Without this attribute, forms don't accept Turbo Stream responses even if the server sends them
- **Turbo 8 morphing** — `turbo-refresh-method="morph"` meta tag requires Turbo 8+; older versions ignore it
- **`data-turbo-track="reload"`** — Only works on `<script>` and `<link>` tags in the `<head>`; changing the URL/version triggers a full reload
- **SSE streams** — Always call `disconnectStreamSource()` when done, or the EventSource stays open and leaks

## Reference Files

- **examples/** - Common patterns:
  - `turbo-frames.twig` - Frame-based navigation and lazy loading
  - `turbo-streams.twig` - Stream actions for DOM manipulation
  - `broadcasting.php` - Entity broadcasting with Mercure
