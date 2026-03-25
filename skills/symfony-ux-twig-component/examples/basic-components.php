<?php
// ============================================================
// Twig Components - PHP Class Patterns
// ============================================================

namespace App\Twig\Components;

use Symfony\UX\TwigComponent\Attribute\AsTwigComponent;
use Symfony\UX\TwigComponent\Attribute\PreMount;
use Symfony\UX\TwigComponent\Attribute\PostMount;

// === 1. Basic Component with Props ===

#[AsTwigComponent]
class Alert
{
    public string $type = 'success'; // Optional prop with default
    public string $message;          // Required prop
    public bool $dismissible = false;

    public function getIconClass(): string
    {
        return match($this->type) {
            'success' => 'bi-check-circle',
            'danger'  => 'bi-exclamation-triangle',
            'warning' => 'bi-exclamation-circle',
            'info'    => 'bi-info-circle',
            default   => 'bi-bell',
        };
    }
}

/*
Template: templates/components/Alert.html.twig

<div class="alert alert-{{ type }}{{ dismissible ? ' alert-dismissible' : '' }}" 
     role="alert" {{ attributes }}>
    <i class="bi {{ this.iconClass }}"></i>
    {{ message }}
    {% if dismissible %}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    {% endif %}
</div>

Usage:
<twig:Alert type="danger" message="File not found!" :dismissible="true" />
<twig:Alert message="Saved successfully!" class="mb-3" />
*/


// === 2. Component with PreMount Validation ===

#[AsTwigComponent]
class Avatar
{
    public string $name;
    public string $src = '';
    public string $size = 'md'; // sm, md, lg, xl

    #[PreMount]
    public function preMount(array $data): array
    {
        $validSizes = ['sm', 'md', 'lg', 'xl'];
        $size = $data['size'] ?? 'md';
        
        if (!in_array($size, $validSizes, true)) {
            throw new \InvalidArgumentException(
                sprintf('Invalid size "%s". Allowed: %s', $size, implode(', ', $validSizes))
            );
        }

        return $data;
    }

    public function getInitials(): string
    {
        $parts = explode(' ', $this->name);
        $initials = '';
        foreach (array_slice($parts, 0, 2) as $part) {
            $initials .= mb_strtoupper(mb_substr($part, 0, 1));
        }
        return $initials;
    }

    public function getSizePixels(): int
    {
        return match($this->size) {
            'sm' => 32,
            'md' => 48,
            'lg' => 64,
            'xl' => 96,
        };
    }
}

/*
Template: templates/components/Avatar.html.twig

{% if src %}
    <img src="{{ src }}"
         alt="{{ name }}"
         width="{{ this.sizePixels }}"
         height="{{ this.sizePixels }}"
         class="avatar avatar--{{ size }} rounded-circle"
         {{ attributes }} />
{% else %}
    <div class="avatar avatar--{{ size }} avatar--initials rounded-circle"
         title="{{ name }}"
         {{ attributes }}>
        {{ this.initials }}
    </div>
{% endif %}

Usage:
<twig:Avatar name="John Doe" src="/img/john.jpg" size="lg" />
<twig:Avatar name="Jane Smith" /> {# Shows initials "JS" #}
*/


// === 3. Component with Service Injection ===

#[AsTwigComponent]
class RecentPosts
{
    public int $limit = 5;
    public ?string $category = null;

    public function __construct(
        private \App\Repository\PostRepository $postRepository,
    ) {
    }

    #[PostMount]
    public function postMount(): void
    {
        // Validate after all props are set
        if ($this->limit < 1 || $this->limit > 50) {
            $this->limit = 5;
        }
    }

    public function getPosts(): array
    {
        return $this->postRepository->findRecent($this->limit, $this->category);
    }
}

/*
Template: templates/components/RecentPosts.html.twig

<div {{ attributes }}>
    <h3>Recent Posts</h3>
    <ul>
        {% for post in this.posts %}
            <li>
                <a href="{{ path('post_show', {slug: post.slug}) }}">
                    {{ post.title }}
                </a>
                <small>{{ post.createdAt|date('M d') }}</small>
            </li>
        {% endfor %}
    </ul>
</div>

Usage:
<twig:RecentPosts />
<twig:RecentPosts limit="10" category="tech" />
*/


// === 4. Component with Custom Name and Template ===

#[AsTwigComponent('ui:data-table', template: 'components/ui/DataTable.html.twig')]
class DataTable
{
    public array $columns = [];
    public array $rows = [];
    public bool $striped = true;
    public bool $hoverable = true;
    public ?string $emptyMessage = 'No data available.';
}

/*
Template: templates/components/ui/DataTable.html.twig

<table class="table{{ striped ? ' table-striped' : '' }}{{ hoverable ? ' table-hover' : '' }}"
       {{ attributes }}>
    <thead>
        <tr>
            {% for col in columns %}
                <th>{{ col.label ?? col }}</th>
            {% endfor %}
        </tr>
    </thead>
    <tbody>
        {% if rows is empty %}
            <tr>
                <td colspan="{{ columns|length }}">{{ emptyMessage }}</td>
            </tr>
        {% else %}
            {% for row in rows %}
                <tr>
                    {% for col in columns %}
                        <td>{{ attribute(row, col.key ?? col) ?? '' }}</td>
                    {% endfor %}
                </tr>
            {% endfor %}
        {% endif %}
    </tbody>
</table>

Usage:
<twig:ui:data-table
    :columns="[{key: 'name', label: 'Name'}, {key: 'email', label: 'Email'}]"
    :rows="users"
/>
*/
