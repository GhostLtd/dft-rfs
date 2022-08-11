# Upgrades notes

## State of composer.json

### Symfony 5.0

* doctrine/dbal is explicitly locked to ^2.0 because 3.x is not compatible with some legacy UUID-related features we're using.

  (N.B. If this gets resolved, then this line can be removed, as dbal is included by doctrine-bundle).

* ~~symfony/doctrine-bundle is locked to 2.2.x because 2.3.0 introduces a new faster caching mechanism [which breaks horribly on staging/prod when there are multiple connections](https://github.com/doctrine/DoctrineBundle/issues/1310#issuecomment-863239602) (and we have multiple connections due to AuditLog).~~

### Symfony 5.1

* Ran into an issue whereby tests were complaining that [the messenger_messages table already existed](https://github.com/liip/LiipTestFixturesBundle/issues/103).
  * This was resolved by adding a packages/tests/liip.yaml with configuration to [keep_database_and_scheme](https://github.com/liip/LiipTestFixturesBundle/issues/103#issuecomment-790022387)
  * However, that issue also pointed at a [Symfony issue](https://github.com/symfony/symfony/pull/40336) whereby they were advocating the removal of "magical" auto-creation, and that had a patch that got merged for 5.2, so this may well need to be reverted.
* There were also similar issues with the test database's doctrine migrations table, and this was resolved with a small code edit in DoctrineMigrationFixtures.php that disabled explicit table creation, and added explicit table clearing. Again, this may need to be reverted come 5.2.

### Symfony 5.2

* Symfony 5.2 allowed us to upgrade to newer version of symfony/doctrine-bundle that didn't have the bug of 2.3.x

### Symfony 5.3

* To get Symfony 5.3 to work required an update to security.yaml - namely "users: []" (rather than ~)