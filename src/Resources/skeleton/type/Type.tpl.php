<?php

/**
 * @var         $namespace
 * @var         $class_name
 * @var array   $constants
 * @var array   $labels
 * @var boolean $useValues
 */

$type = $useValues ? ': int'  : '';

?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

enum <?= $class_name ?><?= $type ?><?= "\n" ?>
{
<?php foreach ($constants as $constantName => $value): ?>
    case <?= $constantName ?><?= $useValues ? " = $value" : '' ?>;
<?php endforeach; ?>

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
}
