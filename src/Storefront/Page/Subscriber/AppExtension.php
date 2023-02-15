<?php

declare(strict_types=1);

namespace ICTECHNewsletterSubscriptionDiscount\Storefront\Page\Subscriber;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{

    /**
     * @var EntityRepositoryInterface
     */
    private $promotionDiscountRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $currencyRepository;
    /**
     * @var EntityRepositoryInterface
     */
    private $newsletterRecipientRepository;

    public function __construct(
        EntityRepositoryInterface $promotionDiscountRepository,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $newsletterRecipientRepository
    ) {
        $this->promotionDiscountRepository = $promotionDiscountRepository;
        $this->currencyRepository          = $currencyRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
    }

    /**
     * @return TwigFunction[]
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getPromoDiscount', [$this, 'getPromoDiscount']),
            new TwigFunction('checkSubscribeUser', [$this, 'checkSubscribeUser']),
        ];
    }

    public function getPromoDiscount($context, $promoId): ?string
    {
        $promotion = $this->getPromotion($promoId, Context::createDefaultContext());
        if ($promotion) {
            $discountType = $promotion->getType();
            if ($discountType == 'percentage') {
                return $promotion->getValue().'%';
            }
            if ($discountType == 'absolute') {
                return $this->getSymbol($context).$promotion->getValue();
            }
        }

        return null;
    }

    public function checkSubscribeUser($email): ?int
    {
        if ($email) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('email', $email));
            $criteria->addFilter(new ContainsFilter('customFields', 'newsletterDiscountApply'));
            return $this->newsletterRecipientRepository->search($criteria, Context::createDefaultContext())->getTotal();
        }
        return null;
    }

    public function getPromotion($promoId, $context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('promotionId', $promoId));
        return $this->promotionDiscountRepository->search($criteria, $context)->first();
    }

    public function getSymbol($context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $context->getCurrencyId()));
        $currencyObject = $this->currencyRepository->search($criteria, Context::createDefaultContext())->first();
        return $currencyObject->getSymbol();
    }
}
