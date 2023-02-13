<?php declare(strict_types=1);

/**
 * icreativetechnologies
 *
 * @category  icreativetechnologies
 * @package   Shopware\Plugins\ICTECHNewsletterSubscriptionDiscount
 * @copyright (c) 2022 icreativetechnologies
 */

namespace ICTECHNewsletterSubscriptionDiscount;

use Doctrine\DBAL\Connection;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;

class ICTECHNewsletterSubscriptionDiscount extends Plugin
{
    public function uninstall(UninstallContext $context): void
    {
        parent::uninstall($context);
        if ($context->keepUserData()) {
            return;
        }
        $connection = $this->container->get(Connection::class);
        //$connection->executeUpdate('DROP TABLE IF EXISTS `newsletter_subscription_discount`');
    }
}
