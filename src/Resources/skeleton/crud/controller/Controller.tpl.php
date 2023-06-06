<?= "<?php\n" ?>

namespace <?= $namespace ?>;

<?= $use_statements; ?>
use App\Controller\AbstractRestController;
<?php
if ($use_openapi) : ?>
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes\Get;
use OpenApi\Attributes\Items;
use OpenApi\Attributes\JsonContent;
use OpenApi\Attributes\Property;
use OpenApi\Attributes\RequestBody;
use OpenApi\Attributes\Response as OpenapiResponse;
use OpenApi\Attributes\Tag;
<?php
endif; ?>

#[Route('<?= $route_path ?>')]
<?php if ($use_openapi) : ?>
#[Tag('<?= $openapi_tag ?>')]
<?php endif; ?>
class <?= $class_name ?> extends AbstractRestController
{
<?= $generator->generateRouteForControllerMethod('', sprintf('%s_index', $route_name), ['GET']) ?>
<?php if ($use_openapi) : ?>
    #[Get(description: '<?= $entity_class_name ?> list', responses: [new OpenapiResponse(response: 200, description: '<?= $entity_class_name ?> list', content: new JsonContent(properties: [new Property('data', type: 'array', items: new Items(ref: new Model(type: <?= $entity_class_name ?>::class, groups: ['default']))), new Property('page', type: 'integer'), new Property('per-page', type: 'integer'), new Property('count', type: 'integer')], type: 'object'))])]
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
    #[RequestBody(required: true, attachables: [new Model(type: <?= $form_class_name ?>::class)])]
<?php endif; ?>
<?php if (isset($repository_full_class_name) && $generator->repositoryHasSaveAndRemoveMethods($repository_full_class_name)) { ?>
    public function new(Request $request, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function new(Request $request)
<?php } ?>
    {
        $<?= $entity_var_singular ?> = new <?= $entity_class_name ?>();
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>);
        $form->handleRequest($request);

<?php if (isset($repository_full_class_name) && $generator->repositoryHasSaveAndRemoveMethods($repository_full_class_name)) { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $<?= $repository_var ?>->save($<?= $entity_var_singular ?>);
        }
<?php } else { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->persist($<?= $entity_var_singular ?>);

            return $this->created($<?= $entity_var_singular ?>);
        }
<?php } ?>

        return $form;
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_show', $route_name), ['GET']) ?>
<?php if ($use_openapi) : ?>
    #[Get(description: '<?= $entity_class_name ?>', responses: [new OpenapiResponse(response: 200, description: '<?= $entity_class_name ?>', content: new JsonContent(ref: new Model(type: <?= $entity_class_name ?>::class, groups: ['default'])))])]
<?php endif; ?>
    public function show(<?= $entity_class_name ?> $<?= $entity_var_singular ?>)
    {
        return $<?= $entity_var_singular ?>;
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_edit', $route_name), ['PATCH']) ?>
<?php if ($use_openapi) : ?>
    #[RequestBody(required: true, attachables: [new Model(type: <?= $form_class_name ?>::class)])]
<?php endif; ?>
<?php if (isset($repository_full_class_name) && $generator->repositoryHasSaveAndRemoveMethods($repository_full_class_name)) { ?>
    public function edit(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function edit(Request $request, <?= $entity_class_name ?> $<?= $entity_var_singular ?>)
<?php } ?>
    {
        $form = $this->createForm(<?= $form_class_name ?>::class, $<?= $entity_var_singular ?>, ['method' => 'PATCH']);
        $form->handleRequest($request);

<?php if (isset($repository_full_class_name) && $generator->repositoryHasSaveAndRemoveMethods($repository_full_class_name)) { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $<?= $repository_var ?>->save($<?= $entity_var_singular ?>);
        }
<?php } else { ?>
        if ($form->isSubmitted() && $form->isValid()) {
            $this->manager->persist($form->getData());
        }
<?php } ?>

        return $form;
    }

<?= $generator->generateRouteForControllerMethod(sprintf('/{%s}', $entity_identifier), sprintf('%s_delete', $route_name), ['DELETE']) ?>
<?php if (isset($repository_full_class_name) && $generator->repositoryHasSaveAndRemoveMethods($repository_full_class_name)) { ?>
    public function delete(<?= $entity_class_name ?> $<?= $entity_var_singular ?>, <?= $repository_class_name ?> $<?= $repository_var ?>)
<?php } else { ?>
    public function delete(<?= $entity_class_name ?> $<?= $entity_var_singular ?>)
<?php } ?>
    {
<?php if (isset($repository_full_class_name) && $generator->repositoryHasSaveAndRemoveMethods($repository_full_class_name)) { ?>
        $<?= $repository_var ?>->remove($<?= $entity_var_singular ?>);
<?php } else { ?>
        $this->manager->remove($<?= $entity_var_singular ?>);
<?php } ?>

        return [];
    }
}
