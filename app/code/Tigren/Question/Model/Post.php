<?php
/*
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2022 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Question\Model;

use Magento\Framework\Model\AbstractModel;

/**
 * Class Post
 * @package Tigren\Question\Model
 */
class Post extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init('Tigren\Question\Model\ResourceModel\Post');
    }
}
