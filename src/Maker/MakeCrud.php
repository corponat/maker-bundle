<?php

/*
 * This file is part of the Symfony MakerBundle package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\MakerBundle\Maker;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Inflector\Inflector;
use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\MakerBundle\ConsoleStyle;
use Symfony\Bundle\MakerBundle\DependencyBuilder;
use Symfony\Bundle\MakerBundle\Doctrine\DoctrineHelper;
use Symfony\Bundle\MakerBundle\Generator;
use Symfony\Bundle\MakerBundle\InputConfiguration;
use Symfony\Bundle\MakerBundle\Renderer\FormTypeRenderer;
use Symfony\Bundle\MakerBundle\Str;
use Symfony\Bundle\MakerBundle\Util\UseStatementGenerator;
use Symfony\Bundle\MakerBundle\Validator;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManager;
use Symfony\Component\Validator\Validation;

/**
 * @author Sadicov Vladimir <sadikoff@gmail.com>
 */
final class MakeCrud extends AbstractMaker
{
    private Inflector $inflector;
    private string $controllerClassName;
    private bool $generateTests = false;

    public function __construct(private DoctrineHelper $doctrineHelper, private FormTypeRenderer $formTypeRenderer)
    {
        $this->inflector = InflectorFactory::create()->build();
    }

    public static function getCommandName(): string
    {
        return 'make:crud';
    }

    public static function getCommandDescription(): string
    {
        return 'Creates CRUD for Doctrine entity class';
    }

    public function configureCommand(Command $command, InputConfiguration $inputConfig): void
    {
        $command
            ->addArgument('entity-class', InputArgument::OPTIONAL, sprintf('The class name of the entity to create CRUD (e.g. <fg=yellow>%s</>)', Str::asClassName(Str::getRandomTerm())));

        $inputConfig->setArgumentAsNonInteractive('entity-class');
    }

    public function interact(InputInterface $input, ConsoleStyle $io, Command $command): void
    {
        if (null === $input->getArgument('entity-class')) {
            $argument = $command->getDefinition()->getArgument('entity-class');

            $entities = $this->doctrineHelper->getEntitiesForAutocomplete();

            $question = new Question($argument->getDescription());
            $question->setAutocompleterValues($entities);

            $value = $io->askQuestion($question);

            $input->setArgument('entity-class', $value);
        }

        $defaultControllerClass = Str::asClassName(sprintf('%s Controller', $input->getArgument('entity-class')));

        $this->controllerClassName = $io->ask(
            sprintf('Choose a name for your controller class (e.g. <fg=yellow>%s</>)', $defaultControllerClass),
            $defaultControllerClass
        );

        $this->generateTests = $io->confirm('Do you want to generate tests for the controller?. [Experimental]', false);
    }

    public function generate(InputInterface $input, ConsoleStyle $io, Generator $generator): void
    {
        $entityClassDetails = $generator->createClassNameDetails(
            Validator::entityExists($input->getArgument('entity-class'), $this->doctrineHelper->getEntitiesForAutocomplete()),
            'Entity\\'
        );

        $entityDoctrineDetails = $this->doctrineHelper->createDoctrineDetails($entityClassDetails->getFullName());

        $repositoryVars = [];
        $repositoryClassName = EntityManagerInterface::class;

        if (null !== $entityDoctrineDetails->getRepositoryClass()) {
            $repositoryClassDetails = $generator->createClassNameDetails(
                '\\' . $entityDoctrineDetails->getRepositoryClass(),
                'Repository\\',
                'Repository'
            );

            $repositoryClassName = $repositoryClassDetails->getFullName();

            $repositoryVars = [
                'repository_full_class_name' => $repositoryClassName,
                'repository_class_name' => $repositoryClassDetails->getShortName(),
                'repository_var' => lcfirst($this->inflector->singularize($repositoryClassDetails->getShortName())),
            ];
        }

        $controllerClassDetails = $generator->createClassNameDetails(
            $this->controllerClassName,
            'Controller\\',
            'Controller'
        );

        $formClassDetails = $generator->createClassNameDetails(
            $entityClassDetails->getRelativeNameWithoutSuffix() . 'Type',
            'Form\\',
            'Type'
        );

        $entityVarPlural = lcfirst($this->inflector->pluralize($entityClassDetails->getShortName()));
        $entityVarSingular = lcfirst($this->inflector->singularize($entityClassDetails->getShortName()));

        $entityTwigVarPlural = Str::asTwigVariable($entityVarPlural);
        $entityTwigVarSingular = Str::asTwigVariable($entityVarSingular);

        $path = $io->ask(
            'Set namespace for route',
            $controllerClassDetails->getRelativeNameWithoutSuffix()
        );

        $routeName = Str::asRouteName($path);
        $templatesPath = Str::asFilePath($path);

        $useStatements = new UseStatementGenerator([
            $entityClassDetails->getFullName(),
            $formClassDetails->getFullName(),
            $repositoryClassName,
            AbstractController::class,
            Request::class,
            Response::class,
            Route::class,
        ]);

        $generator->generateController(
            $controllerClassDetails->getFullName(),
            'crud/controller/Controller.tpl.php',
            array_merge([
                'use_statements' => $useStatements,
                'entity_class_name'        => $entityClassDetails->getShortName(),

                'form_class_name'          => $formClassDetails->getShortName(),
                'route_path'               => Str::asRoutePath($path),
                'route_name'               => $routeName,
                'templates_path'           => $templatesPath,
                'entity_var_plural'        => $entityVarPlural,
                'entity_twig_var_plural'   => $entityTwigVarPlural,
                'entity_var_singular'      => $entityVarSingular,
                'entity_twig_var_singular' => $entityTwigVarSingular,
                'entity_identifier'        => $entityDoctrineDetails->getIdentifier() . '<\d+>',
                'use_render_form'          => method_exists(AbstractController::class, 'renderForm'),
            ],
                $repositoryVars
            )
        );

        $constraintClasses = [];
        $extraUseClasses = [];
        $fieldTypeUseStatements = [];
        $fields = [];
        foreach ($entityDoctrineDetails->getFormFields() as $name => $fieldTypeOptions) {
            $fieldTypeOptions = $fieldTypeOptions ?? ['type' => null, 'options_code' => null];

            if (isset($fieldTypeOptions['type'])) {
                $fieldTypeUseStatements[] = $fieldTypeOptions['type'];
                $fieldTypeOptions['type'] = Str::getShortClassName($fieldTypeOptions['type']);
            }

            $fields[$name] = $fieldTypeOptions;
        }

        $mergedTypeUseStatements = array_unique(array_merge($fieldTypeUseStatements, $extraUseClasses));
        sort($mergedTypeUseStatements);

        if (!class_exists($formClassDetails->getFullName())) {
            $generator->generateClass(
                $formClassDetails->getFullName(),
                'form/Type.tpl.php',
                [
                    'bounded_full_class_name'   => $entityClassDetails ? $entityClassDetails->getFullName() : null,
                    'bounded_class_name'        => $entityClassDetails ? $entityClassDetails->getShortName() : null,
                    'form_fields'               => $fields,
                    'field_type_use_statements' => $mergedTypeUseStatements,
                    'constraint_use_statements' => $constraintClasses,
                ]
            );
        } else {
            $io->text('<bg=yellow;fg=white> Класс типа уже существует! </>' . PHP_EOL);
        }

        if ($this->generateTests) {
            $testClassDetails = $generator->createClassNameDetails(
                $entityClassDetails->getRelativeNameWithoutSuffix(),
                'Test\\Controller\\',
                'ControllerTest'
            );

            $useStatements = new UseStatementGenerator([
                $entityClassDetails->getFullName(),
                WebTestCase::class,
                KernelBrowser::class,
                $repositoryClassName,
            ]);

            $usesEntityManager = EntityManagerInterface::class === $repositoryClassName;

            if ($usesEntityManager) {
                $useStatements->addUseStatement(EntityRepository::class);
            }

            $generator->generateFile(
                'tests/Controller/'.$testClassDetails->getShortName().'.php',
                $usesEntityManager ? 'crud/test/Test.EntityManager.tpl.php' : 'crud/test/Test.tpl.php',
                [
                    'use_statements' => $useStatements,
                    'entity_full_class_name' => $entityClassDetails->getFullName(),
                    'entity_class_name' => $entityClassDetails->getShortName(),
                    'entity_var_singular' => $entityVarSingular,
                    'route_path' => Str::asRoutePath($controllerClassDetails->getRelativeNameWithoutSuffix()),
                    'route_name' => $routeName,
                    'class_name' => Str::getShortClassName($testClassDetails->getFullName()),
                    'namespace' => Str::getNamespace($testClassDetails->getFullName()),
                    'form_fields' => $entityDoctrineDetails->getFormFields(),
                    'repository_class_name' => $usesEntityManager ? EntityManagerInterface::class : $repositoryVars['repository_class_name'],
                    'form_field_prefix' => strtolower(Str::asSnakeCase($entityTwigVarSingular)),
                ]
            );

            if (!class_exists(WebTestCase::class)) {
                $io->caution('You\'ll need to install the `symfony/test-pack` to execute the tests for your new controller.');
            }
        }

        $generator->writeChanges();

        $this->writeSuccessMessage($io);

        $io->text(sprintf('Next: Check your new CRUD by going to <fg=yellow>%s/</>', Str::asRoutePath($path)));
    }

    public function configureDependencies(DependencyBuilder $dependencies): void
    {
        $dependencies->addClassDependency(
            Route::class,
            'router'
        );

        $dependencies->addClassDependency(
            AbstractType::class,
            'form'
        );

        $dependencies->addClassDependency(
            Validation::class,
            'validator'
        );

        $dependencies->addClassDependency(
            TwigBundle::class,
            'twig-bundle'
        );

        $dependencies->addClassDependency(
            DoctrineBundle::class,
            'orm'
        );

        $dependencies->addClassDependency(
            CsrfTokenManager::class,
            'security-csrf'
        );

        $dependencies->addClassDependency(
            ParamConverter::class,
            'annotations'
        );
    }
}
