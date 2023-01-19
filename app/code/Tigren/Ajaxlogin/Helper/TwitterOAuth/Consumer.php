<?php
/**
 * @author    Tigren Solutions <info@tigren.com>
 * @copyright Copyright (c) 2019 Tigren Solutions <https://www.tigren.com>. All rights reserved.
 * @license   Open Software License ("OSL") v. 3.0
 */

namespace Tigren\Ajaxlogin\Helper\TwitterOAuth;

/**
 * Class Consumer
 *
 * @package Tigren\Ajaxlogin\Helper\TwitterOAuth
 */
class Consumer
{
    /**
     *
     *
     * @var string
     */
    public $key;
    /**
     *
     *
     * @var string
     */
    public $secret;
    /**
     *
     *
     * @var string|null
     */
    public $callbackUrl;

    /**
     * @param string $key
     * @param string $secret
     * @param null   $callbackUrl
     */
    public function __construct($key, $secret, $callbackUrl = null)
    {
        $this->key = $key;
        $this->secret = $secret;
        $this->callbackUrl = $callbackUrl;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return "Consumer[key=$this->key,secret=$this->secret]";
    }
}
