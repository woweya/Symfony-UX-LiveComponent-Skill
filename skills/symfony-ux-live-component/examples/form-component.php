<?php
// ============================================================
// LiveComponent Form Integration - Complete CRUD Example
// ============================================================

namespace App\Twig\Components;

use App\Entity\Article;
use App\Form\ArticleType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\ComponentWithFormTrait;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent]
class ArticleForm extends AbstractController
{
    use DefaultActionTrait;
    use ComponentWithFormTrait;

    /**
     * The initial data used to create the form.
     * Use null for "create" mode, or an existing entity for "edit" mode.
     */
    #[LiveProp]
    public ?Article $initialFormData = null;

    /**
     * Create the Symfony Form.
     * This is called on every render to re-create the form.
     */
    protected function instantiateForm(): FormInterface
    {
        return $this->createForm(
            ArticleType::class,
            $this->initialFormData ?? new Article()
        );
    }

    /**
     * Save action — called when the form is submitted via LiveAction.
     */
    #[LiveAction]
    public function save(EntityManagerInterface $em): RedirectResponse|null
    {
        // Submit the form (processes the data from formValues)
        $this->submitForm();

        // Get the form and check validation
        $form = $this->getForm();
        if (!$form->isValid()) {
            // Don't return — errors will be displayed automatically
            // The component re-renders with validation errors shown
            return null;
        }

        /** @var Article $article */
        $article = $form->getData();
        $em->persist($article);
        $em->flush();

        $this->addFlash('success', 'Article saved successfully!');

        return $this->redirectToRoute('article_show', ['id' => $article->getId()]);
    }

    /**
     * Generate a slug from the title dynamically.
     * This modifies the form values directly (not the entity).
     */
    #[LiveAction]
    public function generateSlug(): void
    {
        $title = $this->formValues['title'] ?? '';
        $this->formValues['slug'] = strtolower(
            preg_replace('/[^a-zA-Z0-9]+/', '-', trim($title))
        );
    }
}

/*
Template: templates/components/ArticleForm.html.twig

<div {{ attributes }}>
    {{ form_start(form, {
        attr: {
            'data-action': 'live#action:prevent',
            'data-live-action-param': 'save'
        }
    }) }}
        <div class="mb-3">
            {{ form_row(form.title) }}
            <button type="button"
                    data-action="live#action"
                    data-live-action-param="generateSlug"
                    class="btn btn-sm btn-secondary">
                Generate Slug
            </button>
        </div>
        
        {{ form_row(form.slug) }}
        {{ form_row(form.content) }}
        {{ form_row(form.category) }}
        {{ form_row(form.publishedAt) }}

        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-primary"
                    data-loading="attr(disabled)">
                <span data-loading="action(save)|hide">Save Article</span>
                <span data-loading="action(save)|show" class="d-none">
                    Saving...
                </span>
            </button>
            <a href="{{ path('article_index') }}" class="btn btn-secondary">Cancel</a>
        </div>
    {{ form_end(form) }}
</div>

Usage - Create:
{{ component('ArticleForm') }}

Usage - Edit:
{{ component('ArticleForm', { initialFormData: article }) }}
*/
