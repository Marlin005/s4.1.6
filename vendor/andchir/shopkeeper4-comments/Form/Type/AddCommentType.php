<?php

namespace Andchir\CommentsBundle\Form\Type;

use Andchir\CommentsBundle\Document\CommentInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class AddCommentType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('threadId', HiddenType::class, [
                'constraints' => new NotBlank([
                    'message' => 'Parent ID cannot be empty.'
                ])
            ])
            ->add('comment', TextareaType::class, [
                'constraints' => new Length(['min' => 3])
            ])
            ->add('vote', ChoiceType::class, [
                'choices'  => [
                    '5' => 5,
                    '4' => 4,
                    '3' => 3,
                    '2' => 2,
                    '1' => 1
                ],
                'multiple' => false,
                'expanded' => true,
                'constraints' => new NotBlank([
                    'message' => 'Please rate.'
                ])
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true
        ]);
    }
}
