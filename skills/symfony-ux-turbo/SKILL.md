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
- **Missing `<turbo-frame>` in response** — If the response doesn't contain a matching frame, Turbo shows an error
- **Broadcast template location** — Templates must be in `templates/broadcast/EntityName.stream.html.twig`
- **Stream format** — `targets` uses CSS selector (e.g., `#id`), not just the element id
- **Form in Frame** — Wrap the form in a `<turbo-frame>` to get scoped form submission
- Turbo Drive is **on by default** — use `data-turbo="false"` to opt out per link/section
- For Mercure broadcasting, ensure the Mercure hub is running and accessible

## Reference Files

- **examples/** - Common patterns:
  - `turbo-frames.twig` - Frame-based navigation and lazy loading
  - `turbo-streams.twig` - Stream actions for DOM manipulation
  - `broadcasting.php` - Entity broadcasting with Mercure
