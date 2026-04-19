<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Event;
use App\Repository\LocationRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EventType extends AbstractType
{
    public function __construct(private readonly LocationRepository $locationRepository) {}

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $locations = $this->locationRepository->findBy([], ['name' => 'ASC']);
        $locationChoices = [];

        foreach ($locations as $location) {
            $locationChoices[$location->getName()] = $location->getName();
        }

        $builder
            ->add('title')
            ->add('description', TextareaType::class)
            ->add('eventDate', DateTimeType::class, [
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'attr' => [
                    'min' => (new \DateTimeImmutable('now'))->format('Y-m-d\TH:i'),
                ],
            ])
            ->add('location', ChoiceType::class, [
                'choices' => $locationChoices,
                'placeholder' => 'Select a location',
            ])
            ->add('participantLimit', IntegerType::class)
            ->add('price', MoneyType::class, [
                'currency' => 'EUR',
                'divisor' => 100,
                'label' => 'Price (EUR)',
                'help' => 'Leave at 0 for free events. Values are stored in cents.',
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Select a category',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
