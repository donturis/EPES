<?php
/**
 * Hyvä Themes - https://hyva.io
 * Copyright © Hyvä Themes. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Hyva\OrderCancellationWebapi\Api;

use Hyva\OrderCancellationWebapi\Api\Data\OrderCancellationSuccessInterface;

interface OrderCancellationInterface
{
    /**
     * @param int $customerId
     * @param int $orderId
     * @param string $reason
     * @return \Hyva\OrderCancellationWebapi\Api\Data\OrderCancellationSuccessInterface
     */
    public function cancelById(int $customerId, int $orderId, string $reason): OrderCancellationSuccessInterface;
}
