<?php

/**
 * @var $namespace
 * @var $class_name
 * @var array $constants
 * @var array $labels
 */

$type = '';
foreach ($constants as $constant => $value) {
    if ($value) {
        if (is_numeric($value)) {
            $type = ': int';
        } else {
            $type = ': string';
        }
    }
    break;
}
?>
<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use App\Helpers\ArrayHelper;

/**
 * Class <?= $class_name . "\n" ?>
 */
enum <?= $class_name ?><?= $type ?>
{
<?php foreach ($constants as $constantName => $value): ?>
<?php if (!$value) : ?>
    case <?= $constantName ?>;
<?php else : ?>
<?php if (is_numeric($value)) : ?>
    case <?= $constantName ?> = <?= $value ?>;
<?php else : ?>
    case <?= $constantName ?> = '<?= $value ?>';
<?php endif; ?>
<?php endif; ?>
<?php endforeach; ?>

    public static function getLabels(): array
    {
        return [
<?php foreach ($constants as $constantName => $value): ?>
            self::<?= $constantName ?>->name => '<?= $labels[$constantName] ?? '' ?>',
<?php endforeach; ?>
        ];
    }

    public static function getLabel(\UnitEnum $enum): string
    {
        return self::getLabels()[$enum] ?? '';
    }

    public function label(): string
    {
        return self::getLabel($this);
    }
}
