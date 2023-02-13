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

    private EntityRepositoryInterface $newsletterRecipientRepository;

    public function __construct(
        EntityRepositoryInterface $promotionDiscountRepository,
        EntityRepositoryInterface $currencyRepository,
        EntityRepositoryInterface $newsletterRecipientRepository
    ) {
        $this->promotionDiscountRepository = $promotionDiscountRepository;
        $this->currencyRepository          = $currencyRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;

    }//end __construct()


    /**
     * @return TwigFunction[]
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('getPromoDiscount', [$this, 'getPromoDiscount']),
            new TwigFunction('checkSubscribeUser', [$this, 'checkSubscribeUser']),
        ];

    }//end getFunctions()


    /**
     * @param  $promoId
     * @return null
     */
    public function getPromoDiscount($context, $promoId)
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

    }//end getPromoDiscount()

    public function checkSubscribeUser($email)
    {
        if($email){
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
    }//end getPromotion()


    public function getSymbol($context)
    {
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('id', $context->getCurrencyId()));
        $currencyObject = $this->currencyRepository->search($criteria, Context::createDefaultContext())->first();
        return $currencyObject->getSymbol();
    }
}
