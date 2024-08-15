<?= "<?php\n" ?>

namespace <?= $class_data->getNamespace() ?>;

<?= $class_data->getUseStatements(); ?>

#[Route('<?= $route_path ?>')]
<?= $class_data->getClassDeclaration() ?>

{
<?= $generator->generateRouteForControllerMethod('', sprintf('%s_index', $route_name), ['GET']) ?>
<?php if ($use_openapi) : ?>
    #[GetList(description: '<?= $entity_class_name ?> list', class: <?= $entity_class_name ?>::class)]
<?php endif; ?>
<?php if (isset($repository_full_class_name)): ?>
    public function index(<?= $repository_class_name ?> $<?= $repository_var ?>)
    {
        return $<?= $repository_var ?>->createQueryBuilder('e');
    }
<?php else: ?>
    public function index(): Response
    {
        $<?= $entity_var_plural ?> = $this->manager
            ->getRepository(<?= $entity_class_name ?>::class)
            ->findAll();

        return $<?= $entity_var_plural ?>;
    }
<?php endif ?>

<?= $generator->generateRouteForControllerMethod('', sprintf('%s_new', $route_name), ['POST']) ?>
<?php if ($use_openapi) : ?>
    #[NewPost(description: 'Create <?= lcfirst($entity_class_name) ?>', class: <?= $form_class_name ?>::class)]
<?php endif; ?>
<?php if (isset($repository_full_class_name)) { ?>
    public function new(Request $request, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function new(Request $request)
<?php } ?>
    {
        $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>();
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>);
        $form->handleRequest($request);

<?php if (isset($repository_full_class_name)) { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $<?= $repository_var ?>->save($<?= $entity_var_singular ?>);
        }
<?php } else { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->persist($<?= $entity_var_singular ?>);

            return $this->created($<?= $entity_var_singular ?>);
        }

        return $form;
<?php } ?>
    }
<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_show', $route_name), ['GET']) ?>
<?php if ($use_openapi) : ?>
    #[GetShow(description: '<?= $entity_class_name ?>', class: <?= $entity_class_name ?>::class)]
<?php endif; ?>
    public function show(<?= $entity_class_name ?> $<?= $entity_var_singular ?>)
    {
        return $<?= $entity_var_singular ?>;
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_edit', $route_name), ['PATCH']) ?>
<?php if ($use_openapi) : ?>
    #[EditPatch(description: 'Edit <?= lcfirst($entity_class_name) ?>', class: <?= $form_class_name ?>::class)]
<?php endif; ?>
<?php if (isset($repository_full_class_name)) { ?>
    public function edit(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function edit(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>)
<?php } ?>
    {
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>, ['method' => 'PATCH']);
        $form->handleRequest($request);

<?php if (isset($repository_full_class_name)) { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $<?= $repository_var ?>->save($<?= $entity_var_singular ?>);
        }
<?php } else { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->persist($form->getData());
        }

        return $form;
<?php } ?>
    }
<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_delete', $route_name), ['DELETE']) ?>
<?php if ($use_openapi) : ?>
    #[Delete(description: 'Delete <?= lcfirst($entity_class_name) ?>', responses: [new ResponseSuccess()])]
<?php endif; ?>
<?php if (isset($repository_full_class_name)) { ?>
    public function delete(<?= $entity_class_name ?> $<?= $entity_var_singular ?>, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function delete(<?= $entity_class_name ?> $<?= $entity_var_singular ?>)
<?php } ?>
    {
<?php if (isset($repository_full_class_name)) { ?>
        $<?= $repository_var ?>->remove($<?= $entity_var_singular ?>);
<?php } else { ?>
        $this->manager->remove($<?= $entity_var_singular ?>);
<?php } ?>

        return [];
    }
}
