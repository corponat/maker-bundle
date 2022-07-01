<?= "<?php\n" ?>

namespace <?= $namespace ?>;

use App\Controller\AbstractRestController;
use <?= $entity_full_class_name ?>;
use <?= $form_full_class_name ?>;
<?php if (isset($repository_full_class_name)): ?>
use <?= $repository_full_class_name ?>;
<?php if (!$generator->repositoryHasAddRemoveMethods($repository_full_class_name)) : ?>
use Doctrine\ORM\EntityManagerInterface;
<?php endif; ?>
<?php else: ?>
use Doctrine\ORM\EntityManagerInterface;
<?php endif; ?>
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

<?php if ($use_attributes) { ?>
#[Route('<?= $route_path ?>')]
<?php } else { ?>
/**
 * @Route("<?= $route_path ?>")
 */
<?php } ?>
class <?= $class_name ?> extends AbstractRestController
{
<?= $generator->generateRouteForControllerMethod('', sprintf('%s_index', $route_name), ['GET']) ?>
<?php if (isset($repository_full_class_name)): ?>
    public function index(<?= $repository_class_name ?> $<?= $repository_var ?>)
    {
        return $<?= $repository_var ?>->findAll();
    }
<?php else: ?>
    public function index(EntityManagerInterface $entityManager): Response
    {
        $<?= $entity_var_plural ?> = $entityManager
            ->getRepository(<?= $entity_class_name ?>::class)
            ->findAll();

        return $<?= $entity_var_plural ?>;
    }
<?php endif ?>

<?= $generator->generateRouteForControllerMethod('', sprintf('%s_new', $route_name), ['POST']) ?>
<?php if (isset($repository_full_class_name) && $generator->repositoryHasAddRemoveMethods($repository_full_class_name)) { ?>
    public function new(Request $request, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function new(Request $request, EntityManagerInterface $entityManager)
<?php } ?>
    {
        $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>();
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>);
        $form->handleRequest($request);

<?php if (isset($repository_full_class_name) && $generator->repositoryHasAddRemoveMethods($repository_full_class_name)) { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $<?= $repository_var ?>->add($<?= $entity_var_singular ?>);
        }
<?php } else { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($<?= $entity_var_singular ?>);
            $entityManager->flush();

            return $this->created($<?= $entity_var_singular ?>);
        }
<?php } ?>

<?php if ($use_render_form) { ?>
        return $form;
<?php } else { ?>
        return $form;
<?php } ?>
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_show', $route_name), ['GET']) ?>
    public function show(<?= $entity_class_name ?> $<?= $entity_var_singular ?>)
    {
        return $<?= $entity_var_singular ?>;
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_edit', $route_name), ['PATCH']) ?>
<?php if (isset($repository_full_class_name) && $generator->repositoryHasAddRemoveMethods($repository_full_class_name)) { ?>
    public function edit(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function edit(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>, EntityManagerInterface $entityManager)
<?php } ?>
    {
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>, ['method' => 'PATCH']);
        $form->handleRequest($request);

<?php if (isset($repository_full_class_name) && $generator->repositoryHasAddRemoveMethods($repository_full_class_name)) { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $<?= $repository_var ?>->add($<?= $entity_var_singular ?>);
        }
<?php } else { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
        }
<?php } ?>

<?php if ($use_render_form) { ?>
        return $form;
<?php } else { ?>
        return $form;
<?php } ?>
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_delete', $route_name), ['DELETE']) ?>
<?php if (isset($repository_full_class_name) && $generator->repositoryHasAddRemoveMethods($repository_full_class_name)) { ?>
    public function delete(<?= $entity_class_name ?> $<?= $entity_var_singular ?>, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function delete(<?= $entity_class_name ?> $<?= $entity_var_singular ?>, EntityManagerInterface $entityManager)
<?php } ?>
    {
<?php if (isset($repository_full_class_name) && $generator->repositoryHasAddRemoveMethods($repository_full_class_name)) { ?>
        $<?= $repository_var ?>->remove($<?= $entity_var_singular ?>);
<?php } else { ?>
        $entityManager->remove($<?= $entity_var_singular ?>);
        $entityManager->flush();
<?php } ?>

        return [];
    }
}
