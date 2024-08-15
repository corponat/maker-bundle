<?= "<?php\n"; ?>

namespace <?= $namespace; ?>;

<?= $use_statements; ?>
use App\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<<?= $entity_class_name; ?>>
 */
class <?= $class_name; ?> extends ServiceEntityRepository<?= $with_password_upgrade ? " implements PasswordUpgraderInterface\n" : "\n" ?>
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, <?= $entity_class_name; ?>::class);
    }
<?php if ($with_password_upgrade): ?>
    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(<?= sprintf('%s ', $password_upgrade_user_interface->getShortName()); ?>$user, string $newHashedPassword): void
    {
        if (!$user instanceof <?= $entity_class_name ?>) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

<?php endif ?>
}
