<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 03/05/2018
 * Time: 15:54
 */

namespace Wefabric\Cart;

use Wefabric\Cart\Exceptions\CartItemException;
use Illuminate\Contracts\Support\Arrayable;
use Gloudemans\Shoppingcart\Contracts\Buyable;
use Illuminate\Support\Collection;

class CartItem implements Arrayable
{
    /**
     * The rowID of the cart item.
     *
     * @var string
     */
    public $rowId = '';

    /**
     * The ID of the cart item.
     *
     * @var int|string
     */
    public $id = 0;

    /**
     * The quantity for this cart item.
     *
     * @var int|float
     */
    public $qty = 0;

    /**
     * The name of the cart item.
     *
     * @var string
     */
    public $name = '';

    /**
     * The price without TAX of the cart item.
     *
     * @var float
     */
    public $regularPrice = 0.0;

    public $specialPrice = 0.0;

    /**
     * The disposal without TAX of the cart item.
     *
     * @var float
     */
    public $disposal = 0.0;


    /**
     * The options for this cart item.
     *
     * @var CartItemOptions
     */
    protected $options = null;

    /**
     * The options for this cart item.
     *
     * @var CartItemOptions
     */
    protected $meta = null;

    /**
     * The FQN of the associated model.
     *
     * @var string|null
     */
    private $associatedModel = null;

    /**
     * The tax rate for the cart item.
     *
     * @var int|float
     */
    private $taxRate = 0;

    /**
     * CartItem constructor.
     *
     * @param int|string $id
     * @param string     $name
     * @param float      $price
     * @param array      $options
     */
    public function __construct($id, $name, $regularPrice, $specialPrice, $disposal = null, array $options = [], array $meta = [])
    {
        if(empty($id)) {
            throw new \InvalidArgumentException('Please supply a valid identifier.');
        }
        if(empty($name)) {
            throw new \InvalidArgumentException('Please supply a valid name.');
        }

        $this->setId((int)$id);
        $this->setName((string)$name);
        $this->setRegularPrice(floatval($regularPrice));
        $this->setSpecialPrice(floatval($specialPrice));
        $this->setDisposal(floatval($disposal));
        $this->setOptions(new CartItemOptions($options));
        $this->setMeta($meta);
        $this->setRowId($this->generateRowId());
    }

    /**
     * @return string
     */
    public function getRowId(): string
    {
        return $this->rowId;
    }

    /**
     * @param string $rowId
     */
    private function setRowId(string $rowId)
    {
        $this->rowId = $rowId;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @param int $id
     */
    public function setId(int $id)
    {
        $this->id = $id;
    }

    /**
     * @return float|int
     */
    public function getQty(): int
    {
        return $this->qty;
    }

    /**
     * @param int $qty
     */
    public function setQty(int $qty)
    {
        $this->qty = $qty;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;
    }

    /**
     * @return float
     */
    public function getRegularPrice(): float
    {
        return $this->regularPrice;
    }

    /**
     * @param float $regularPrice
     */
    public function setRegularPrice(float $regularPrice)
    {
        $this->regularPrice = $regularPrice;
    }

    /**
     * @return float
     */
    public function getSpecialPrice(): float
    {
        return $this->specialPrice;
    }

    /**
     * @param float $specialPrice
     */
    public function setSpecialPrice(float $specialPrice)
    {
        $this->specialPrice = $specialPrice;
    }

    /**
     * @param float $disposal
     */
    public function setDisposal(float $disposal)
    {
        $this->disposal = $disposal;
    }

    /**
     * @return array
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    /**
     * @param array $options
     */
    public function setOptions(Collection $options)
    {
        $this->options = $options;
    }

    /**
     * @return CartItemOptions
     */
    public function getMeta(): CartItemOptions
    {
        return $this->meta;
    }

    /**
     * @param array|CartItemOptions $meta
     */
    public function setMeta( $meta)
    {
        if(!$meta instanceof CartItemOptions) {
            if(is_array($meta)) {
                $meta = new CartItemOptions($meta);
            } else {
                throw new \CartItemException(sprintf(
                    '%s: expects a array or CartItemOptions argument; received "%s"',
                    __METHOD__,
                    (is_object($meta) ? get_class($meta) : gettype($meta))
                ));
            }
        }

        $this->meta = $meta;
    }

    public function addMeta($key, $value)
    {
        if(!$this->meta) {
            $this->meta = new CartItemOptions();
        }

        $this->meta->put($key, $value);
    }



    /**
     * Get an attribute from the cart item or get the associated model.
     *
     * @param string $attribute
     * @return mixed
     */
    public function __get($attribute)
    {
        if(property_exists($this, $attribute)) {
            return $this->{$attribute};
        }

        if($attribute === 'price') {
            return $this->getPrice();
        }

        if($attribute === 'qty') {
            return $this->getQty();
        }

        if($attribute === 'priceTax') {
            return $this->getPriceTax();
        }

        if($attribute === 'subtotal') {
            return $this->getSubtotal();
        }

        if($attribute === 'total') {
            return $this->getTotal();
        }
        if($attribute === 'tax') {
            return $this->getTax();
        }

        if($attribute === 'taxTotal') {
            return $this->getTax() * $this->getQty();
        }
        if($attribute === 'model' && isset($this->associatedModel)) {
            return with(new $this->associatedModel)->find($this->id);
        }
        return null;
    }

    /**
     * Returns the formatted price without TAX.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return string
     */
    public function getPrice($decimals = null, $decimalPoint = null, $thousandSeparator = null): float
    {
        if(!$price = $this->getSpecialPrice()) {
            $price = $this->getRegularPrice();
        }
        return $this->numberFormat($price, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted price with TAX.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return string
     */
    public function getPriceTax($decimals = null, $decimalPoint = null, $thousandSeparator = null): float
    {
        return $this->numberFormat($this->getPrice() + $this->getTax(), $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted price without TAX.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return string
     */
    public function getDisposal($decimals = null, $decimalPoint = null, $thousandSeparator = null): float
    {
        return $this->numberFormat($this->disposal, $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted subtotal.
     * Subtotal is price for whole CartItem without TAX
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return string
     */
    public function getSubtotal($decimals = null, $decimalPoint = null, $thousandSeparator = null)
    {
        return $this->numberFormat($this->getQty() * $this->getPrice(), $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted total.
     * Total is price for whole CartItem with TAX
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return string
     */
    public function getTotal($decimals = null, $decimalPoint = null, $thousandSeparator = null)
    {

        return $this->numberFormat($this->getQty() * $this->getPriceTax(), $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted tax.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return string
     */
    public function getTax($decimals = null, $decimalPoint = null, $thousandSeparator = null)
    {
        return $this->numberFormat($this->getPrice() * ($this->getTaxRate() / 100), $decimals, $decimalPoint, $thousandSeparator);
    }

    /**
     * Returns the formatted tax.
     *
     * @param int    $decimals
     * @param string $decimalPoint
     * @param string $thousandSeparator
     * @return string
     */
    public function taxTotal($decimals = null, $decimalPoint = null, $thousandSeparator = null)
    {
        return $this->numberFormat($this->getTax() * $this->getQty(), $decimals, $decimalPoint, $thousandSeparator);
    }


    public function getUnit()
    {
        $result = '';
        if(isset($this->options['unit'])) {
            $result = $this->options['unit'];
        }
        return $result;
    }

    public function getSku()
    {
        $result = '';
        if(isset($this->options['sku'])) {
            $result = $this->options['sku'];
        }
        return $result;
    }

    /**
     * Set the quantity for this cart item.
     *
     * @param int|float $qty
     */
    public function setQuantity(int $qty)
    {
        $this->qty = $qty;
    }

    /**
     * Update the cart item from an array.
     *
     * @param array $attributes
     * @return void
     */
    public function updateFromArray(array $attributes)
    {
        $this->id       = array_get($attributes, 'id', $this->id);
        $this->qty      = array_get($attributes, 'qty', $this->qty);
        $this->name     = array_get($attributes, 'name', $this->name);
        $this->price    = array_get($attributes, 'price', $this->price);
        $this->disposal = array_get($attributes, 'disposal', $this->disposal);
        $this->priceTax = $this->getPrice() + $this->getTax();
        $this->options  = new CartItemOptions(array_get($attributes, 'options', []));
        $this->meta  = new CartItemOptions(array_get($attributes, 'meta', []));

        $this->rowId = $this->generateRowId();
    }

    /**
     * Associate the cart item with the given model.
     *
     * @param mixed $model
     * @return void
     */
    public function associate($model)
    {
        $this->associatedModel = is_string($model) ? $model : get_class($model);
    }

    /**
     * @return float|int
     */
    public function getTaxRate(): int
    {
        return $this->taxRate;
    }

    /**
     * Set the tax rate.
     *
     * @param int|float $taxRate
     * @return void
     */
    public function setTaxRate(float $taxRate)
    {
        $this->taxRate = $taxRate;
    }

    /**
     * Create a new instance from the given array.
     *
     * @param array $attributes
     * @return CartItem
     */
    public static function fromArray(array $attributes)
    {
        $options = array_get($attributes, 'options', []);
        $meta = array_get($attributes, 'meta', []);

        return new self($attributes['id'],
            $attributes['name'],
            $attributes['price'],
            $attributes['specialPrice'] ?? $attributes['price'],
            $attributes['disposal'],
            $options,
            $meta
        );
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     * @param array  $options
     * @return string
     */
    protected function generateRowId()
    {
//        $options = $this->getOptions()->all();
//        ksort($options);
//
//        return md5($this->getId() . serialize($options));

        return $this->getId();
    }

    /**
     * Get the instance as an array.
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'rowId'         => $this->getRowId(),
            'id'            => $this->getId(),
            'name'          => $this->getName(),
            'qty'           => $this->getQty(),
            'price'         => $this->getPrice(),
            'regularPrice'  => $this->getRegularPrice(),
            'specialPrice'  => $this->getSpecialPrice(),
            'disposal'      => $this->getDisposal(),
            'tax'           => $this->getTax(),
            'subtotal'      => $this->getSubtotal(),
            'options'       => $this->getOptions()->toArray(),
            'meta'          => $this->getMeta()->toArray()
        ];
    }

    /**
     * Get the Formatted number
     *
     * @param $value
     * @param $decimals
     * @param $decimalPoint
     * @param $thousandSeparator
     * @return string
     */
    private function numberFormat($value, $decimals, $decimalPoint, $thousandSeparator)
    {
        $decimals = $decimals ?: config('cart.format.decimals') ?: 4;
        $decimalPoint = $decimalPoint ?: config('cart.format.decimal_point') ?: '.';
        $thousandSeparator = $thousandSeparator ?: config('cart.format.thousand_separator') ?: '';

        return number_format($value, $decimals, $decimalPoint, $thousandSeparator);
    }
}