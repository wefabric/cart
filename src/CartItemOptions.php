<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 03/05/2018
 * Time: 15:59
 */

namespace Wefabric\Cart;
use Illuminate\Support\Collection;

class CartItemOptions extends Collection
{
    /**
     * Get the option by the given key.
     *
     * @param string $key
     * @return mixed
     */
    public function __get($key)
    {
        return $this->get($key);
    }
}