<?php declare(strict_types=1);

/*
 * icreativetechnologies
 *
 * @category  icreativetechnologies
 * @package   Shopware\Plugins\ICTECHNewsletterSubscriptionDiscount
 * @copyright (c) 2022 icreativetechnologies
 */

namespace ICTECHNewsletterSubscriptionDiscount\Core\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\CartBehavior;
use Shopware\Core\Checkout\Cart\CartDataCollectorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OverwritePriceCollector implements CartDataCollectorInterface
{

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    /**
     * @var EntityRepositoryInterface
     */
    private $newsletterRecipientRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $promotionRepository;
    /**
     * @var PromotionItemBuilder
     */
    private $promotionItemBuilder;
    /**
     * @var SessionInterface
     */
    private $session;

    public function __construct(
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $newsletterRecipientRepository,
        PromotionItemBuilder $promotionItemBuilder,
        EntityRepositoryInterface $promotionRepository,
        SessionInterface $session
    ) {
        $this->systemConfigService           = $systemConfigService;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->promotionItemBuilder          = $promotionItemBuilder;
        $this->promotionRepository           = $promotionRepository;
        $this->session = $session;
    }

    public function collect(
        CartDataCollection $data,
        Cart $original,
        SalesChannelContext $context,
        CartBehavior $behavior
    ): void {
        $newsletterClick = $this->session->get('newsletterClick');
        $customer        = $context->getCustomer();
        if ($customer) {
            $loginEmail         = $customer->getEmail();
            $checkSubscribeUser = $this->checkSubscribeUser($loginEmail, $context);
            if ($newsletterClick == 'cart' && $checkSubscribeUser == 0) {
                $promoId  = $this->systemConfigService->get(
                    'newsletter.settings.promoCode',
                    $context->getSalesChannelId()
                );
                $criteria = new Criteria();
                $criteria->addFilter(new EqualsFilter('id', $promoId));
                $promoObj = $this->promotionRepository->search($criteria, $context->getContext())->first();
                if ($promoObj) {
                    $lineItem = $this->promotionItemBuilder->buildPlaceholderItem($promoObj->getCode());
                    $original->addLineItems(new LineItemCollection([$lineItem]));
                }
            }
        }
    }

    public function checkSubscribeUser($loginEmail, $context): int
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $loginEmail));
        $criteria->addFilter(new ContainsFilter('customFields', 'newsletterDiscountApply'));
        return $this->newsletterRecipientRepository->search($criteria, $context->getContext())->getTotal();
    }
}
