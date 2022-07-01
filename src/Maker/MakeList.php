<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Class MakeType
 */
class MakeList extends AbstractMaker
{
    /**
     * @var Inflector
     */
    private $inflector;

    public function __construct()
    {
        if (class_exists(InflectorFactory::class)) {
            $this->inflector = InflectorFactory::create()->build();
        }
    }

    /**
     * @return string
     */
    public static function getCommandDescription()
    {
        return 'list';
    }

    /**
     * @return string
     */
    public static function getCommandName(): string
    {
        return 'make:list';
    }

    /**
     * {@inheritdoc}
     */
    public function configureCommand(Command $command, InputConfiguration $inputConfig)
    {
        $command->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the enum'));

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command)
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');
            $question = new Question($argument->getDescription());

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator)
    {
        $constantClassDetails = $generator->createClassNameDetails(
            $input->getArgument('entity-class'),
            'Enums\\'
        );

        $constants = [];
        $cases = [];
        $labels = [];
        $class = $constantClassDetails->getFullName();
        if (class_exists($class)) {
            $cases = $class::cases();
            $oldLabels = $class::getLabels();

            foreach ($cases as $case) {
                $labels[$case->name] = $oldLabels[$case->name] ?? null;
            }
        }

        $processedConstants = [];
        while (true) {
            $newConstant = $this->askForConstantName($io, $cases, $processedConstants);
            $newConstant = mb_strtoupper($newConstant);

            foreach ($cases as $case) {
                $constants[$case->name] = $case->value ?? null;
            }
            if (!$newConstant) {
                break;
            }

            $oldValue = null;
            if (in_array($newConstant, array_column($cases, 'name'))) {
                foreach ($cases as $case) {
                    if ($case->name === $newConstant) {
                        $oldValue = $case->value ?? null;
                    }
                }
            }
            $nextValue = 1;
            while (in_array($nextValue, array_column($cases, 'value'))) {
                $nextValue++;
            }
            foreach ($cases as $case) {
                if ($case->name === $newConstant) {
                    $currentCase = $case;
                    break;
                }
            }

            while (true) {
                $newValue = $this->askForConstantValue($io, $nextValue, $oldValue);

                if ($newValue == $nextValue) {
                    break;
                }

                if (!in_array($newValue, array_column($cases, 'value'))) {
                    break;
                }
                if (in_array(array_column($cases, 'name'), $newConstant) && (isset($currentCase->value) && $newValue == $currentCase->value)) {
                    break;
                } else {
                    $io->writeln('<fg=red;options=bold,underscore>Значение уже занято!!!</>');
                }
            }

            $defaultLabel = $labels[$newConstant] ?? null;
            $label = $this->askForConstantLabel($io, $defaultLabel);
            $labels[$newConstant] = $label;

            $constants = array_merge($constants, [$newConstant => $newValue]);
            $processedConstants[] = $newConstant;
        }

        if (!$constants) {
            return;
        }

        $question = new Question('Тип сортировки', 'По значениям');
        $question->setAutocompleterValues(['По значениям', 'По ключам']);

        $sort = $io->askQuestion($question);

        if ($sort == 'По значениям') {
            asort($constants);
        } else {
            ksort($constants);
        }

        if (class_exists($constantClassDetails->getFullName())) {
            $ns = str_replace('App\\', '', $constantClassDetails->getFullName());
            $ns = str_replace('\\', DIRECTORY_SEPARATOR, $ns);
            $path = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $ns . '.php';

            unlink($path);
        }

        $generator->generateClass(
            $constantClassDetails->getFullName(),
            __DIR__ . '/Skeleton/Type/Type.tpl.php',
            [
                'constants' => $constants,
                'labels'    => $labels,
            ]
        );

        $generator->writeChanges();

        $this->writeSuccessMessage($io);
    }

    /**
     * @param ConsoleStyle $io
     * @param              $cases
     * @param array        $processedConstants
     *
     * @return mixed
     */
    private function askForConstantName(ConsoleStyle $io, $cases, $processedConstants = [])
    {
        $io->writeln('');
        $question = new Question('Введите константу');

        $question->setAutocompleterValues(array_diff(array_column($cases, 'name'), $processedConstants));

        $constantName = $io->askQuestion($question);

        return $constantName;
    }

    /**
     * @param ConsoleStyle $io
     * @param              $nextValue
     * @param null         $oldValue
     *
     * @return mixed
     */
    private function askForConstantValue(ConsoleStyle $io, $nextValue, $oldValue = null)
    {
        $io->writeln('');

        if ($oldValue) {
            $message = "Введите значение существующей константы, либо оставьте пустым для того чтобы оставить предыдущее значение - <fg=yellow>$oldValue</>, либо укажите следующее доступное - <fg=yellow>$nextValue</>";
            $default = $oldValue;
        } else {
            $message = "Введите значение константы (например $nextValue), либо оставьте пустым";
            $default = null;
        }
        $constantName = $io->ask($message, $default);

        return $constantName;
    }

    /**
     * @param ConsoleStyle $io
     *
     * @param string       $defaultLabel
     *
     * @return mixed
     */
    private function askForConstantLabel(ConsoleStyle $io, $defaultLabel = '')
    {
        $io->writeln('');

        $label = $io->ask("Введите описание", $defaultLabel);

        return $label;
    }

    /**
     * {@inheritdoc}
     */
    public function configureDependencies(DependencyBuilder $dependencies)
    {
    }

    private function pluralize(string $word): string
    {
        if (null !== $this->inflector) {
            return $this->inflector->pluralize($word);
        }

        return LegacyInflector::pluralize($word);
    }

    private function singularize(string $word): string
    {
        if (null !== $this->inflector) {
            return $this->inflector->singularize($word);
        }

        return LegacyInflector::singularize($word);
    }
}
