<?php
// ============================================================
// Basic & Entity Autocomplete Form Fields
// ============================================================

namespace App\Form;

use App\Entity\Category;
use App\Entity\Tag;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ArticleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            // 1. Simple choice autocomplete
            ->add('status', ChoiceType::class, [
                'choices' => [
                    'Draft' => 'draft',
                    'Under Review' => 'review',
                    'Published' => 'published',
                    'Archived' => 'archived',
                ],
                'autocomplete' => true,
                'placeholder' => 'Select a status',
            ])

            // 2. Entity autocomplete (single selection)
            ->add('author', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'autocomplete' => true,
                'placeholder' => 'Search for an author...',
            ])

            // 3. Entity autocomplete (multiple selection)
            ->add('tags', EntityType::class, [
                'class' => Tag::class,
                'choice_label' => 'name',
                'multiple' => true,
                'autocomplete' => true,
                'tom_select_options' => [
                    'create' => true,       // Allow creating new tags
                    'createOnBlur' => true,
                    'maxItems' => 10,
                    'plugins' => ['remove_button'],
                ],
            ])

            // 4. Entity autocomplete with grouping
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'group_by' => 'parent.name', // Group by parent category
                'autocomplete' => true,
                'placeholder' => 'Choose a category',
            ])
        ;
    }
}
