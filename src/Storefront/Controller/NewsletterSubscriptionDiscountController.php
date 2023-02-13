<?php

/**
 * icreativetechnologies
 *
 * @category  icreativetechnologies
 * @package   Shopware\Plugins\ICTECHNewsletterSubscriptionDiscount
 * @copyright (c) 2022 icreativetechnologies
 */

namespace ICTECHNewsletterSubscriptionDiscount\Storefront\Controller;

use Shopware\Core\Framework\Routing\Annotation\RouteScope;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(defaults={"_routeScope"={"storefront"}})
 */

class NewsletterSubscriptionDiscountController extends StorefrontController
{

    /**
     * @Route("/newsletter/subscription", name="frontend.newsletter.subscription", defaults={"XmlHttpRequest"=true,"csrf_protected"=false}, methods={"POST"})
     */

    public function showExample(Request $request): JsonResponse
    {
        $request->getSession()->set('newsletterClick', 'cart');
        return new JsonResponse(['timestamp' => (new \DateTime())->format(\DateTimeInterface::W3C)]);
    }
}
