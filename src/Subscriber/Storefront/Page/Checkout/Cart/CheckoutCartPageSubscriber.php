<?php
/**
 * icreativetechnologies
 *
 * @category  icreativetechnologies
 * @package   Shopware\Plugins\ICTECHNewsletterSubscriptionDiscount
 * @copyright 2023 Squiz Pty Ltd (ABN 77 084 670 600)
 */

namespace ICTECHNewsletterSubscriptionDiscount\Subscriber\Storefront\Page\Checkout\Cart;

use Shopware\Core\Checkout\Cart\Cart;
use Shopware\Core\Checkout\Cart\SalesChannel\CartService;
use Shopware\Core\Checkout\Customer\Event\CustomerLogoutEvent;
use Shopware\Core\Checkout\Promotion\Cart\PromotionItemBuilder;
use Shopware\Core\Content\Newsletter\Event\NewsletterRegisterEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SalesChannel\Entity\SalesChannelRepositoryInterface;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Salutation\SalutationCollection;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Confirm\CheckoutConfirmPageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Finish\CheckoutFinishPageLoadedEvent;
use Shopware\Storefront\Page\PageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

class CheckoutCartPageSubscriber implements EventSubscriberInterface
{

    private CartService $cartService;

    private PromotionItemBuilder $promotionItemBuilder;

    /**
     * @var SystemConfigService
     */
    protected $systemConfigService;

    private EventDispatcherInterface $eventDispatcher;

    private EntityRepositoryInterface $salutationRepository;

    private SalesChannelRepositoryInterface $newsletterRecipientRepository;

    private EntityRepositoryInterface $newsletterRepository;
    private SessionInterface $session;


    public function __construct(
        CartService $cartService,
        PromotionItemBuilder $promotionItemBuilder,
        EventDispatcherInterface $eventDispatcher,
        SystemConfigService $systemConfigService,
        EntityRepositoryInterface $salutationRepository,
        SalesChannelRepositoryInterface $newsletterRecipientRepository,
        EntityRepositoryInterface $newsletterRepository,
        SessionInterface $session
    ) {
        $this->cartService                   = $cartService;
        $this->promotionItemBuilder          = $promotionItemBuilder;
        $this->eventDispatcher               = $eventDispatcher;
        $this->systemConfigService           = $systemConfigService;
        $this->salutationRepository          = $salutationRepository;
        $this->newsletterRecipientRepository = $newsletterRecipientRepository;
        $this->newsletterRepository          = $newsletterRepository;
        $this->session          = $session;
    }


    /**
     * @return string[]
     */
    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutCartPageLoadedEvent::class    => 'onCheckoutPageLoaded',
            CheckoutConfirmPageLoadedEvent::class => 'onCheckoutConfirmPageLoaded',
            CheckoutFinishPageLoadedEvent::class  => 'onFinishPageLoaded',
            NewsletterRegisterEvent::class        => 'onNewsLetterRegister',
            CustomerLogoutEvent::class => 'onCustomerLogout'
        ];
    }


    /**
     * Calls onCheckoutPageLoaded for handling stuff in all cart page
     *
     * @param PageLoadedEvent $event
     */
    public function onCheckoutPageLoaded(PageLoadedEvent $event): void
    {
        $this->onSummaryPageData($event);
    }


    public function onNewsLetterRegister(NewsletterRegisterEvent $event):void
    {

        $email        = $event->getNewsletterRecipient()->getEmail();
        $cookie_name  = 'newsletterCookie';
        $cookie_value = $email;
        setcookie($cookie_name, $cookie_value, (time() + (120)), '/');
    }

    public function onCustomerLogout(CustomerLogoutEvent $event)
    {
        $this->session->remove('newsletterClick');
    }
    /**
     * Calls onCheckoutConfirmPageLoaded for handling stuff in all checkout pages
     *
     * @param CheckoutConfirmPageLoadedEvent $event
     */
    public function onCheckoutConfirmPageLoaded(PageLoadedEvent $event): void
    {
        $this->onSummaryPageData($event);
    }


    /**
     * Calls onFinishPageLoaded for handling stuff in thank you page
     *
     * @param CheckoutFinishPageLoadedEvent $event
     */
    public function onFinishPageLoaded($event): void
    {
        // Start Newsletter Discount CustomFields Data
        $email = $event->getPage()->getOrder()->getOrderCustomer()->getEmail();

        $orderID = $event->getPage()->getOrder()->getId();

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $email));
        $newsletterEmail = $this->newsletterRecipientRepository->search($criteria, $event->getSalesChannelContext());
        if ($newsletterEmail->getTotal() >= 1) {
            $firstElement = $newsletterEmail->first()->getId();
            $writeData    = [
                'id'           => $firstElement,
                'customFields' => [
                    'newsletterDiscountApply' => 1,
                    'discountOrderId'         => $orderID,
                ],
            ];
            $this->newsletterRepository->upsert([$writeData], Context::createDefaultContext());
        }
        // End Newsletter Discount CustomFields Data
        $this->onSummaryPageData($event);
    }


    /**
     * Handles the stuff, that happens in all summary pages
     */
    private function onSummaryPageData($event)
    {
        // @var SalutationCollection $salutations
        $salutations = $this->salutationRepository->search(new Criteria(), $event->getContext())->getEntities();
        $page        = $event->getPage();
        $page->addExtension('smnSalutation', $salutations);

        // Get Current Customer Email
        $customerEmail = '';
        $status        = '';
        $customer      = $event->getSalesChannelContext()->getCustomer();

        if ($customer != null) {
            $customerEmail = $event->getSalesChannelContext()->getCustomer()->getEmail();
        }

        // Get All Newsletter Data
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('email', $customerEmail));

        $allNewsLatterObject = $this->newsletterRecipientRepository->search($criteria, $event->getSalesChannelContext());
        $allNewsLatterData   = $allNewsLatterObject->getTotal();
        if ($allNewsLatterData > 0) {
            $customFields = $allNewsLatterObject->first()->getcustomfields();
            $status       = $allNewsLatterObject->first()->getStatus();
        }

        if ($allNewsLatterData >= 1) {
            $customerEmail = null;
        } else {
            if ($customer != null) {
                $customerEmail = $event->getSalesChannelContext()->getCustomer()->getEmail();
            }
        }

        // Data assign to page
        $event->getPage()->assign(
            [
                'newsletterDiscount'                                => ($customFields['newsletterDiscountApply'] ?? 0),
                'discountOrderId'                                   => ($customFields['discountOrderId'] ?? 0),
                'IctechNewsletterCustomerEmail'                     => $customerEmail,
                'IctechAllNewsLatterData'                           => $allNewsLatterData,
                'newsletterStatus'                                  => $status,
                'ICTECHNewsletterSubscriptionDiscountConfiguration' => $this->systemConfigService->get('ICTECHNewsletterSubscriptionDiscount.config', $event->getSalesChannelContext()->getSalesChannel()->getId()),
            ]
        );
    }
}
