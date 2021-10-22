<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 05/05/2018
 * Time: 15:28
 */

namespace Wefabric\Cart;


use Wefabric\Cart\Exceptions\CartException;
use Illuminate\Support\Collection;

class CartManager
{

    /**
     * @var Collection
     */
    protected $cartCollection;


    public function __construct(array $carts = [])
    {
        $this->cartCollection = new CartCollection();

        if ($carts) {
            foreach ($carts as $hash => $cart) {
                $cart->setRowId($hash);
                $this->add($hash, $cart);
            }
        }

    }

    public function add(string $hash, Cart $cart)
    {
        if ($this->exists($hash)) {
            throw new CartException(sprintf('Cart with hash %s already exists', $hash));
        }

        $cart->setRowId($hash);
        $this->cartCollection->put($hash, $cart);
    }

    public function exists(string $hash)
    {
        return $this->cartCollection->has($hash);
    }

    public function get($hash)
    {
        $result = null;
        if ($this->exists($hash)) {
            $result = $this->cartCollection->get($hash);
        }
        return $result;
    }

    public function update($hash, $cart)
    {
        $this->cartCollection->put($hash, $cart);
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param string $rowId
     * @return void
     */
    public function remove(string $hash)
    {
        $this->cartCollection->pull($hash);
    }

    public function toArray()
    {
        return $this->cartCollection->toArray();
    }

    public function __invoke()
    {
        return $this->cartCollection;
    }

}