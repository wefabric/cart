<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 03/05/2018
 * Time: 15:11
 */

namespace Wefabric\Cart;

class Discount extends Fee
{

    public $code;

    public function __construct($code, $type, $amount, $label = '', $description = '', $taxRate = 0)
    {
        $this->setCode($code);

        if('' == $label) {
            $label = $code;
        }

        parent::__construct($type, $amount, $label, $description, $taxRate);
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
        return $this->getCode();
    }

    /**
     * @return mixed
     */
    public function getCode(): string
    {
        return $this->code;
    }

    /**
     * @param mixed $code
     */
    public function setCode(string $code)
    {
        $this->code = $code;
    }

    public function calculate($price = null): float
    {
        $price = parent::calculate($price);

        // TODO Hotfix for not yet usable fees

        if ($price > 0) {
            $price = 0 - $price;
        } else {
            $price = $price;
        }

        return $price;
    }


    public function toArray()
    {
        $result = parent::toArray();
        $result['code'] = $this->getCode();
        return $result;
    }

}