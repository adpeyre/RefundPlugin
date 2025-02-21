<?php

/*
 * This file is part of the Sylius package.
 *
 * (c) Paweł Jędrzejewski
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Sylius\RefundPlugin\StateResolver;

use Doctrine\Persistence\ObjectManager;
use SM\Factory\FactoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Core\OrderPaymentStates;
use Sylius\Component\Core\OrderPaymentTransitions;
use Sylius\Component\Core\Repository\OrderRepositoryInterface;
use Sylius\RefundPlugin\Exception\OrderNotFound;

final class OrderPartiallyRefundedStateResolver implements OrderPartiallyRefundedStateResolverInterface
{
    private OrderRepositoryInterface $orderRepository;

    private FactoryInterface $stateMachineFactory;

    private ObjectManager $orderManager;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        FactoryInterface $stateMachineFactory,
        ObjectManager $orderManager
    ) {
        $this->orderRepository = $orderRepository;
        $this->stateMachineFactory = $stateMachineFactory;
        $this->orderManager = $orderManager;
    }

    public function resolve(string $orderNumber): void
    {
        /** @var OrderInterface|null $order */
        $order = $this->orderRepository->findOneByNumber($orderNumber);
        if ($order === null) {
            throw OrderNotFound::withNumber($orderNumber);
        }

        if ($order->getPaymentState() === OrderPaymentStates::STATE_PARTIALLY_REFUNDED) {
            return;
        }

        $stateMachine = $this->stateMachineFactory->get($order, OrderPaymentTransitions::GRAPH);
        $stateMachine->apply(OrderPaymentTransitions::TRANSITION_PARTIALLY_REFUND);

        $this->orderManager->flush();
    }
}
