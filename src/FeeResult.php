<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 06/05/2018
 * Time: 14:17
 */

namespace Wefabric\Cart;

use Illuminate\Contracts\Support\Arrayable;

class FeeResult implements Arrayable
{

    /**
     * @var float
     */
    protected $result = 0.00;

    /**
     * @var float
     */
    protected $taxResult = 0.00;

    /**
     * @return float
     */
    public function getResult(): float
    {
        return $this->result;
    }

    /**
     * @param float $result
     */
    public function setResult(float $result): void
    {
        $this->result = $result;
    }

    /**
     * @return float
     */
    public function getTaxResult(): float
    {
        return $this->taxResult;
    }

    /**
     * @param float $taxResult
     */
    public function setTaxResult(float $taxResult): void
    {
        $this->taxResult = $taxResult;
    }




    public function toArray()
    {
        return [
            'result' => $this->getResult(),
            'taxResult' => $this->getTaxResult()
        ];
    }
}