<?php

/**
 * @var         $namespace
 * @var         $class_name
 * @var array   $constants
 * @var array   $labels
 * @var boolean $useValues
 * @var boolean $isInteger
 * @var boolean $addLabels
 */

$type = $useValues ? ': ' . ($isInteger ? 'int' : 'string')  : '';

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

enum <?= $class_name ?><?= $type ?><?= "\n" ?>
{
<?php foreach ($constants as $constantName => $value): ?>
    <?php if ($useValues && !$isInteger) : ?>
        <?php $value = "'$value'" ?>
    <?php endif; ?>
    case <?= $constantName ?><?= $useValues ? " = $value" : '' ?>;
<?php endforeach; ?>
<?php if ($addLabels) : ?>

    public static function getLabels(): array
    {
        return [
<?php foreach ($constants as $constantName => $value): ?>
            self::<?= $constantName ?>-><?= $useValues ? 'value' : 'name' ?> => '<?= $labels[$constantName] ?? '' ?>',
<?php endforeach; ?>
        ];
    }

    public function label(): string
    {
        return self::getLabels()[$this-><?= $useValues ? 'value' : 'name' ?>] ?? '';
    }
<?php endif; ?>
}
