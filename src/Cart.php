<?php

namespace Wefabric\Cart;

use Wefabric\Countries\Countries\CountryInterface;
use Wefabric\Countries\Address;
use Wefabric\Cart\Exceptions\CartException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Contracts\Support\Jsonable;
use Wefabric\Cart\Exceptions\InvalidRowIDException;
use Wefabric\ShippingRate\ShippingRate;
use Wefabric\ShippingRate\ShippingRateException;

class Cart implements Arrayable, Jsonable
{

    protected $rowId;

    protected $cartItemsCollection;

    protected $feeManager;

    /**
     * @var Discount
     */
    protected $discount;

    protected $customer;

    protected $itemsDiscount;

    protected $country;

    /**
     * @var ShippingRate
     */
    protected $shippingRate;

    protected $applyTax = true;

    protected $userId = null;

    protected $ipAddress = '';

    protected $userAgent = '';

    public function __construct($rowId = '')
    {
        $this->setRowId($rowId);
    }

    /**
     * Add an item to the cart.
     *
     * @param mixed $id
     * @param mixed $name
     * @param int|float $qty
     * @param float $price
     * @param array $options
     * @return CartItem
     */
    public function add($id, $name = null, $qty = null, $regularPrice = null, $specialPrice = null, $disposal = null, array $options = [], array $meta = [])
    {
        if ($this->isMulti($id)) {
            return array_map(function ($item) {
                return $this->add($item);
            }, $id);
        }
        $cartItem = $this->createCartItem($id, $name, $qty, $regularPrice, $specialPrice, $disposal, $options, $meta);
        $items = $this->items();
        if ($items->has($cartItem->getRowId())) {
            $item = $items->get($cartItem->getRowId());
            $cartItem->setQty($cartItem->getQty() + $item->getQty());
        }

        $items->put($cartItem->getRowId(), $cartItem);

        return $cartItem;
    }

    /**
     * Check if the item is a multidimensional array or an array of Buyables.
     *
     * @param mixed $item
     * @return bool
     */
    private function isMulti($item)
    {
        if (!is_array($item)) return false;
        return is_array(head($item));
    }

    /**
     * Create a new CartItem from the supplied attributes.
     *
     * @param mixed $id
     * @param mixed $name
     * @param int|float $qty
     * @param float $price
     * @param array $options
     * @return CartItem
     */
    private function createCartItem($id, $name, $qty, $regularPrice, $specialPrice, $disposal = null, array $options, array $meta)
    {
        if (is_array($id)) {
            $cartItem = CartItem::fromArray($id);
            $cartItem->setQuantity($id['qty']);
        } else {
            $cartItem = new CartItem($id, $name, $regularPrice, $specialPrice, $disposal, $options, $meta);
            $cartItem->setQuantity($qty);
        }
        $cartItem->setTaxRate($this->getTaxRate());
        return $cartItem;
    }

    public function getTaxRate()
    {
        return $this->getCountry()->getTaxRate();
    }

    private function getCountry(): CountryInterface
    {
        if (!$this->country) {
            $this->setCountry(\Countries::get('NL'));
        }

        return $this->country;
    }

    /**
     * Get the content of the cart.
     *
     * @return CartItemsCollection
     */
    public function items()
    {
        if (!$this->cartItemsCollection) {
            $this->cartItemsCollection = new CartItemsCollection();
        }

        return $this->cartItemsCollection;
    }

    /**
     * Update the cart item with the given rowId.
     *
     * @param string $rowId
     * @param mixed $qty
     * @return void
     */
    public function update($rowId, int $qty)
    {
        $cartItem = $this->getCartItem($rowId);
        if (is_array($qty)) {
            $cartItem->updateFromArray($qty);
        } else {
            $cartItem->setQty($qty);
        }

        $items = $this->items();
        if ($rowId !== $cartItem->getRowId()) {
            $items->pull($rowId);
            if ($items->has($cartItem->getRowId())) {
                $existingCartItem = $this->getCartItem($cartItem->getRowId());
                $cartItem->setQuantity($existingCartItem->getQty() + $cartItem->getQty());
            }
        }
        if ($cartItem->getQty() <= 0) {
            $this->remove($cartItem->getRowId());
            return;
        } else {
            $items->put($cartItem->getRowId(), $cartItem);
        }
    }

    /**
     * Get a cart item from the cart by its rowId.
     *
     * @param string $rowId
     * @return CartItem
     */
    public function getCartItem($rowId)
    {
        $items = $this->items();
        if (!$items->has($rowId))
            throw new InvalidRowIDException("The cart does not contain rowId {$rowId}.");
        return $items->get($rowId);
    }

    /**
     * Remove the cart item with the given rowId from the cart.
     *
     * @param string $rowId
     * @return void
     */
    public function remove($rowId)
    {
        if (!$this->items()->has($rowId)) {
            throw new CartException("The cart does not contain rowId {$rowId}.");
        }

        $this->items()->pull($rowId);
    }

    public function addQty($rowId, int $qty)
    {
        if (!$cartItem = $this->getCartItem($rowId)) {
            throw new CartException(sprintf('Cart item with row id %s does not exist.', $rowId));
        }

        $cartItem->setQuantity($cartItem->getQty() + $qty);
    }

    public function setQty($rowId, int $qty)
    {
        if (!$cartItem = $this->getCartItem($rowId)) {
            throw new CartException(sprintf('Cart item with row id %s does not exist.', $rowId));
        }

        $cartItem->setQuantity($qty);
    }

    public function empty()
    {
        $this->cartItemsCollection = new CartItemsCollection();
        $this->shippingRate = null;
        $this->removeDiscount();
    }

    /**
     * Search the cart content for a cart item matching the given search closure.
     *
     * @param \Closure $search
     * @return \Illuminate\Support\Collection
     */
    public function search(Closure $search)
    {
        $items = $this->items();
        return $items->filter($search);
    }

    /**
     * Set the tax rate for the cart item with the given rowId.
     *
     * @param string $rowId
     * @param int|float $taxRate
     * @return void
     */
    public function setTax($rowId, $taxRate)
    {
        $cartItem = $this->getCartItem($rowId);
        $cartItem->setTaxRate($taxRate);
        $items = $this->items();
        $items->put($cartItem->getRowId(), $cartItem);
    }

    /**
     * Magic method to make accessing the total, tax and subtotal properties possible.
     *
     * @param string $attribute
     * @return float|null
     */
    public function __get($attribute)
    {
        if ($attribute === 'total') {
            return $this->total();
        }
        if ($attribute === 'tax') {
            return $this->tax();
        }
        if ($attribute === 'subtotal') {
            return $this->subtotal();
        }
        return null;
    }

    /**
     * Get the total price of the items in the cart.
     *
     * @param int $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return string
     */
    public function total()
    {
        $feesTotal = $this->fees($withTax = true) + $this->fees($withTax = false);

        $total = $this->subtotal() + $this->shipping() + $this->disposal() + $feesTotal + $this->discount() + $this->tax();

        return $total > 0 ? $total : 0;
    }

    /**
     * Get the subtotal (total - tax) of the items in the cart.
     *
     * @param int $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return float
     */
    public function subtotal()
    {
        $items = $this->items();
        $subTotal = $items->reduce(function ($subTotal, CartItem $cartItem) {
            return $subTotal + ($cartItem->getQty() * $cartItem->getPrice());
        }, 0);
        return $subTotal;
    }

    public function shipping()
    {
        $shipping = $this->getShipping();
        $shipping->getFreeShippingThreshold();

        $subtotal = $this->subtotal() + $this->discount();

        if ($shipping->hasFreeShippingThreshold()) {
            if ($subtotal >= $shipping->getFreeShippingThreshold()) {
                return 0.0;
            }
        }

        return $shipping->getRate();
    }

    /**
     * @return \Wefabric\ShippingRate\ShippingRateInterface
     * @throws ShippingRateException
     */
    public function getShipping()
    {
        if(!$this->shippingRate) {
            if(!$shippingRates = \ShippingRateManager::get($this->getCountry()->getIso())) {
                throw new ShippingRateException(sprintf(__('Shipping rates for country with iso "%s" not set'), $this->getCountry()->getIso()));
            }

            $this->shippingRate = $shippingRates->first();
        }

        return $this->shippingRate;
    }

    /**
     * @param $rate
     * @throws ShippingRateException
     */
    public function setShippingRate($rate)
    {
        if($rate instanceof ShippingRate) {
            $this->shippingRate = $rate;
            return;
        }

        if(!$shippingRates = \ShippingRateManager::get($this->getCountry()->getIso())) {
            throw new ShippingRateException(sprintf(__('Shipping rates for country with iso "%s" not set'), $this->getCountry()->getIso()));
        }

        if(!$shippingRate = $shippingRates->get($rate)) {
            throw new ShippingRateException(sprintf(__('Shipping rate "%s" for country  "%s" does not exist'), $rate, $this->getCountry()->getIso()));
        }

        $this->shippingRate = $shippingRate;
    }

    public function discount()
    {
        $result = 0.0;
        if ($discount = $this->getDiscount()) {

            $result = $discount->calculate($this->subtotal());

            if (strpos($discount->getCode(), 'Verzendk') !== false) {
                $result = 0 - $result;
            }
        }

        return $result;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function setDiscount($code, $type, $amount, $label = '', $description = '', $taxRate = 0)
    {
        $this->setRawDiscount(new Discount($code, $type, $amount, $label, $description, $taxRate));
    }

    public function setRawDiscount(Discount $discount)
    {
        $this->discount = $discount;
    }

    /**
     * Get the disposal of the items in the cart.
     *
     * @param int $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return float
     */
    public function disposal()
    {
        $items = $this->items();
        $disposal = $items->reduce(function ($disposal, CartItem $cartItem) {
            return $disposal + ($cartItem->getQty() * $cartItem->getDisposal());
        }, 0);
        return $disposal;
    }

    /**
     * @return mixed
     */
    public function getFees()
    {
        if (!$this->feeManager) {
            $this->feeManager = new FeeManager();
        }

        return $this->feeManager;
    }

    /**
     * @param bool $withTax
     * @return mixed
     */
    public function fees($withTax = true)
    {
        return $this->getFees()->calculate(($this->subtotal() + $this->discount()), $withTax);
    }

    /**
     * @param bool $withTax
     * @return mixed
     */
    public function feesTax($withTax = true)
    {
        return $this->getFees()->tax(($this->subtotal() + $this->discount()), $withTax);
    }

    public function applyTax()
    {
        return $this->applyTax;
    }

    public function setApplyTax(bool $applyTax)
    {
        $this->applyTax = $applyTax;
    }

    /**
     * Get the total tax of the items in the cart.
     *
     * @param int $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return float
     */
    public function tax()
    {
        if(false === $this->applyTax()) {
            return 0;
        }

        $items = $this->items();
        $tax = $items->reduce(function ($tax, CartItem $cartItem) {
            return $tax + (($cartItem->getQty() * $cartItem->getPrice()) * ($this->getTaxRate() / 100));
        }, 0);

        $tax += $this->feesTax($withTax = true);

        // Hotfix for discount as verzendk
        if (isset($this->discount)) {
            // Times 2 because it is earlier been subtracted
            $tax += (($this->discount() * ($this->getTaxRate() / 100)));
        }

        $tax += ($this->shipping() * ($this->getTaxRate() / 100));
        $tax += ($this->disposal() * ($this->getTaxRate() / 100));

        return $tax > 0 ? $tax : 0;
    }

    public function getCustomer()
    {
        return $this->customer;
    }

    public function setCustomer($name, $email, $userId = 0, $title = '', $message = '')
    {
        $this->customer = new CartCustomer($name, $email, $userId, $title, $message);
    }

    public function removeDiscount()
    {
        if ($discount = $this->getDiscount()) {
            $rowId = $discount->getCode();

            if ($this->getFees()->exists($rowId)) {
                $this->getFees()->remove($rowId);
            }

            $this->discount = null;
            $this->save();
        }
    }

    /**
     * @return null
     */
    public function getUserId()
    {
        return $this->userId;
    }

    /**
     * @param null $userId
     */
    public function setUserId($userId): void
    {
        $this->userId = $userId;
    }

    /**
     * @return string
     */
    public function getIpAddress(): string
    {
        if(!$this->ipAddress) {
            $this->setIpAddress(request()->ip());
        }
        return $this->ipAddress;
    }

    /**
     * @param string $ipAddress
     */
    public function setIpAddress(string $ipAddress): void
    {
        $this->ipAddress = $ipAddress;
    }

    /**
     * @return string
     */
    public function getUserAgent(): string
    {
        if(!$this->userAgent) {
            $this->setUserAgent(request()->userAgent());
        }

        return $this->userAgent;
    }

    /**
     * @param string $userAgent
     */
    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }

    public function toJson($options = 0)
    {
        return json_encode($this->toArray(), $options);
    }

    public function toArray()
    {
        $fees = $this->fees();
        $feesTax = $this->feesTax();

        return [
            'rowId' => $this->getRowId(),
            'count' => $this->count(),
            'items' => $this->items()->toArray(),
            'fees' => $this->getFees()->toArray(),
            'fees_amounts' => $fees,
            'fees_tax' => $feesTax,
            'subtotal' => $this->subtotal(),
            'discount' => $this->getDiscount() ? $this->getDiscount()->toArray() : [],
            'discount_amount' => $this->discount(),
            'disposal' => $this->disposal(),
            'shipping' => $this->shipping(),
            'shippingRate' => $this->getShipping()->toArray(),
            'tax' => $this->tax(),
            'tax_rate' => $this->getTaxRate(),
            'total' => $this->total(),
            'ipAddress' => $this->getIpAddress(),
            'userAgent' => $this->getUserAgent()
        ];
    }

    /**
     * @return mixed
     */
    public function getRowId()
    {
        return $this->rowId;
    }

    /**
     * @param mixed $rowId
     */
    public function setRowId($rowId)
    {
        $this->rowId = $rowId;
    }

    /**
     * Get the number of items in the cart.
     *
     * @return int|float
     */
    public function count()
    {
        return $this->items()->sum('qty');
    }

    public function setCountry(CountryInterface $country)
    {
        $this->country = $country;
    }

    public function renderTotals()
    {
        $output = '';
        $output .= 'Subtotal: ' . $this->subtotal() . PHP_EOL;
        if($this->getDiscount()) {
            $output .= 'Discount (' . $this->getDiscount()->getLabel() . '): ' . $this->discount() . PHP_EOL;
        }

        $output .= 'Shipping: ' . $this->shipping() . PHP_EOL;
        foreach ($this->getFees()->getWithTax() as $fee) {
            $output .= 'Fee (' . $fee->getLabel() . '): ' . $fee->calculate($this->subtotal()) . PHP_EOL;

        }
        $output .= 'Tax: ' . $this->tax() . PHP_EOL;
        foreach ($this->getFees()->getWithoutTax() as $fee) {
                $output .= 'Fee (' . $fee->getLabel() . '): ' . $fee->calculate($this->subtotal()) . PHP_EOL;
        }
        $output .= 'Total: ' . $this->total();

        return $output;

    }

}

