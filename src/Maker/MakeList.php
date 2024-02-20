<?php

namespace Symfony\Bundle\MakerBundle\Maker;

use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\FileManager;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
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
    /**
     * @var FileManager
     */
    private $fileManager;

    public function __construct(FileManager $fileManager)
    {
        if (class_exists(InflectorFactory::class)) {
            $this->inflector = InflectorFactory::create()->build();
        }

        $this->fileManager = $fileManager;
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

        $isInteger = false;
        $autoValues = false;
        if ($useValues = $io->askQuestion(new ConfirmationQuestion('Use backed enum?'))) {
            if ($isInteger = $io->askQuestion(new ConfirmationQuestion('Set integer?'))) {
                $autoValues = $io->askQuestion(new ConfirmationQuestion('set values auto?'));
            }
        }
        $addLabels = $io->askQuestion(new ConfirmationQuestion('Add labels?', false));

        $constants = [];
        $cases = [];
        $labels = [];
        $class = $constantClassDetails->getFullName();
        if (class_exists($class)) {
            $cases = $class::cases();
            $oldLabels = method_exists($class, 'getLabels') ? $class::getLabels() : [];

            if (is_subclass_of($class, \BackedEnum::class)) {
                foreach ($cases as $case) {
                    $labels[$case->name] = $oldLabels[$case->value] ?? null;
                    $constants[$case->name] = $useValues ? $case->value : null;
                }
            } else {
                $index = 1;
                foreach ($cases as $case) {
                    $labels[$case->name] = $oldLabels[$case->name] ?? null;
                    $constants[$case->name] = $useValues ? $index++ : null;
                }
            }
        }

        $processedConstants = [];
        while (true) {
            $newConstant = $this->askForConstantName($io, $cases, $processedConstants);
            $newConstant = mb_strtoupper(preg_replace('/((?<!^)[A-Z]|(?<=\s)\w)/um', '_$0', $newConstant));

            if (!$newConstant) {
                break;
            }

            if ($useValues) {
                $oldValue = null;
                if (in_array($newConstant, array_column($cases, 'name'))) {
                    foreach ($cases as $case) {
                        if ($case->name === $newConstant) {
                            $oldValue = $case->value ?? null;
                        }
                    }
                }
                $nextValue = $isInteger ? 1 : mb_strtolower($newConstant);
                $i = 1;
                while (in_array($nextValue, array_column($cases, 'value')) || in_array($nextValue, $constants)) {
                    $nextValue = $isInteger ? $nextValue + 1 : $nextValue . '_' . $i++;
                }
                foreach ($cases as $case) {
                    if ($case->name === $newConstant) {
                        $currentCase = $case;
                        break;
                    }
                }
                if ($autoValues) {
                    $newValue = $nextValue;
                } else {
                    while (true) {
                        $newValue = $this->askForConstantValue($io, $nextValue, $oldValue);

                        if ($newValue == $nextValue) {
                            break;
                        }

                        if (!in_array($newValue, array_column($cases, 'value'))) {
                            break;
                        }
                        if (in_array($newConstant, array_column($cases, 'name')) && (isset($currentCase->value) && $newValue == $currentCase->value)) {
                            break;
                        } else {
                            $io->writeln('<fg=red;options=bold,underscore>Значение уже занято!!!</>');
                        }
                    }
                }
            }

            if ($addLabels) {
                $defaultLabel = $labels[$newConstant] ?? null;
                $label = $this->askForConstantLabel($io, $defaultLabel);
                $labels[$newConstant] = $label;
            }

            $constants = array_merge($constants, [$newConstant => $newValue ?? null]);
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
            if ($io->askQuestion(new ConfirmationQuestion('Перенумеровать значения?', false))) {
                asort($constants);
                $orderedConstants = array_values($constants);
                ksort($constants);
                $i = 0;
                foreach ($constants as &$constant) {
                    if ($autoValues) {
                        $constant = $i++ + 1;
                    } else {
                        $constant = $orderedConstants[$i++];
                    }
                }
            } else {
                ksort($constants);
            }
        }

        if (class_exists($constantClassDetails->getFullName())) {
            unlink($this->fileManager->getRelativePathForFutureClass($constantClassDetails->getFullName()));
        }

        $generator->generateClass(
            $constantClassDetails->getFullName(),
            'type/Type.tpl.php',
            compact('constants', 'labels', 'useValues', 'isInteger', 'addLabels')
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
    private function askForConstantName(ConsoleStyle $io, $cases, array $processedConstants = [])
    {
        $io->writeln('');
        $question = new Question('Введите константу');

        $question->setAutocompleterValues(array_diff(array_column($cases, 'name'), $processedConstants));

        return $io->askQuestion($question);
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
            $default = $nextValue;
        }

        return $io->ask($message, $default);
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
