<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Events\Plugin\Model;

use Magento\Quote\Model\Quote\Item;

/**
 * Class Quote
 *
 * @package Tigren\Events\Plugin\Model
 */
class Quote
{
    /**
     * @param \Magento\Quote\Model\Quote $subject
     * @param  $result
     * @return bool
     */
    public function afterIsVirtual(\Magento\Quote\Model\Quote $subject, $result)
    {
        if ($result == false) {
            $isEvents = true;
            $countItems = 0;
            foreach ($subject->getItemsCollection() as $_item) {
                /* @var $_item Item */
                if ($_item->isDeleted() || $_item->getParentItemId()) {
                    continue;
                }
                $countItems++;
                if ($_item->getProduct()->getTypeId() != 'event') {
                    $isEvents = false;
                    break;
                }
            }
            return $countItems == 0 ? false : $isEvents;
        }

        return $result;
    }
}
