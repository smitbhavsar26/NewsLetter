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
use Shopware\Core\Checkout\Cart\CartProcessorInterface;
use Shopware\Core\Checkout\Cart\LineItem\CartDataCollection;
use Shopware\Core\Checkout\Cart\LineItem\LineItem;
use Shopware\Core\Checkout\Cart\LineItem\LineItemCollection;
use Shopware\Core\Checkout\Cart\Price\QuantityPriceCalculator;
use Shopware\Core\Checkout\Cart\Price\Struct\QuantityPriceDefinition;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class OverwritePriceCollector implements CartDataCollectorInterface
{

    /**
     * @var QuantityPriceCalculator
     */
    private $calculator;

    /**
     * @var SystemConfigService
     */
    private $systemConfigService;

    private EntityRepositoryInterface $newsletterRecipientRepository;

    private CartService $cartService;

    private EntityRepositoryInterface $promotionRepository;

    private PromotionItemBuilder $promotionItemBuilder;

    private SessionInterface $session;


    public function __construct(
        QuantityPriceCalculator $calculator,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $newsletterRecipientRepository,
        CartService $cartService,
        PromotionItemBuilder $promotionItemBuilder,
        EntityRepositoryInterface $promotionRepository,
        SessionInterface $session
    ) {
        $this->calculator                    = $calculator;
        $this->systemConfigService           = $systemConfigService;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->cartService                   = $cartService;
        $this->promotionItemBuilder          = $promotionItemBuilder;
        $this->promotionRepository           = $promotionRepository;
        $this->session = $session;
    }


    public function collect(CartDataCollection $data, Cart $original, SalesChannelContext $context, CartBehavior $behavior): void
    {
        $newsletterClick = $this->session->get('newsletterClick');
        $customer        = $context->getCustomer();
        if ($customer) {
            $loginEmail         = $context->getCustomer()->getEmail();
            $checkSubscribeUser = $this->checkSubscribeUser($loginEmail, $context);
            if ($newsletterClick == 'cart' && $checkSubscribeUser == 0) {
                $promoId  = $this->systemConfigService->get('newsletter.settings.promoCode', $context->getSalesChannelId());
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
    
    public function checkSubscribeUser($loginEmail, $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $loginEmail));
        $criteria->addFilter(new ContainsFilter('customFields', 'newsletterDiscountApply'));
        return $this->newsletterRecipientRepository->search($criteria, $context->getContext())->getTotal();
    }


    private function filterAlreadyFetchedPrices(array $productIds, CartDataCollection $data): array
    {
        $filtered = [];

        foreach ($productIds as $id) {
            $key = $this->buildKey($id);

            // already fetched from database?
            if ($data->has($key)) {
                continue;
            }

            $filtered[] = $id;
        }
        return $filtered;
    }

    private function buildKey(string $id): string
    {
        return 'price-overwrite-'.$id;
    }
}
