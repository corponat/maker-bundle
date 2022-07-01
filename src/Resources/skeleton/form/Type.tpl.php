<?php

/**
 * @var $namespace
 * @var $bounded_full_class_name
 * @var $field_type_use_statements
 * @var $constraint_use_statements
 * @var $class_name
 * @var $form_fields
 * @var $bounded_class_name
 */

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?php if ($bounded_full_class_name): ?>
use <?= $bounded_full_class_name ?>;
<?php endif ?>
use Symfony\Component\Form\AbstractType;
<?php foreach ($field_type_use_statements as $className): ?>
use <?= $className ?>;
<?php endforeach; ?>
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
<?php foreach ($constraint_use_statements as $className): ?>
use <?= $className ?>;
<?php endforeach; ?>

class <?= $class_name ?> extends AbstractType
{
    public function getBlockPrefix()
    {
        return '';
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
<?php foreach ($form_fields as $form_field => $typeOptions): ?>
<?php $form_field = strtolower(preg_replace('/(?<=[a-z])([A-Z])/', '_$1', $form_field)); ?>
<?php if (null === $typeOptions['type'] && !$typeOptions['options_code']): ?>
            ->add('<?= $form_field ?>')
<?php elseif (null !== $typeOptions['type'] && !$typeOptions['options_code']): ?>
            ->add('<?= $form_field ?>', <?= $typeOptions['type'] ?>::class)
<?php else: ?>
            ->add('<?= $form_field ?>', <?= $typeOptions['type'] ? ($typeOptions['type'].'::class') : 'null' ?>, [
<?= $typeOptions['options_code']."\n" ?>
            ])
<?php endif; ?>
<?php endforeach; ?>
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
<?php if ($bounded_full_class_name): ?>
            'data_class'         => <?= $bounded_class_name ?>::class,
            'csrf_protection'    => false,
            'allow_extra_fields' => true,
<?php else: ?>
            'csrf_protection'    => false,
            'allow_extra_fields' => true,
<?php endif ?>
        ]);
    }
}
