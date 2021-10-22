<?php
/**
 * Created by SlickLabs - Wefabric.
 * User: nathanjansen <nathan@wefabric.nl>
 * Date: 26-03-18
 * Time: 13:11
 */

namespace Wefabric\Cart;

class CartFactory
{
    /**
     * @var array[]
     */
    protected $items;

    public function __construct(array $config)
    {
        $this->items = $config;
    }

    /**
     * @param array $config
     * @return CheckoutManager
     */
    public static function create(): CartManager
    {
        return new CartManager();
    }
}