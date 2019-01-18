<?php declare(strict_types=1);

namespace Shopware\Storefront\Page\AccountOrder;

use Shopware\Core\Checkout\CheckoutContext;
use Shopware\Core\Framework\Routing\InternalRequest;
use Shopware\Storefront\Pagelet\AccountOrder\AccountOrderPageletLoader;
use Shopware\Storefront\Pagelet\Header\HeaderPageletLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class AccountOrderPageLoader
{
    /**
     * @var AccountOrderPageletLoader
     */
    private $accountOrderPageletLoader;

    /**
     * @var HeaderPageletLoader
     */
    private $headerPageletLoader;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        AccountOrderPageletLoader $accountOrderPageletLoader,
        HeaderPageletLoader $headerPageletLoader,
        EventDispatcherInterface $eventDispatcher
    ) {
        $this->accountOrderPageletLoader = $accountOrderPageletLoader;
        $this->headerPageletLoader = $headerPageletLoader;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function load(InternalRequest $request, CheckoutContext $context): AccountOrderPageStruct
    {
        $page = new AccountOrderPageStruct();
        $page->setAccountOrder(
            $this->accountOrderPageletLoader->load($request, $context)
        );

        $page->setHeader(
            $this->headerPageletLoader->load($request, $context)
        );

        $this->eventDispatcher->dispatch(
            AccountOrderPageLoadedEvent::NAME,
            new AccountOrderPageLoadedEvent($page, $context, $request)
        );

        return $page;
    }
}
