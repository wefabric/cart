<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 05/05/2018
 * Time: 15:28
 */

namespace Wefabric\Cart;


use Wefabric\Cart\Exceptions\FeeException;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class FeeManager implements Arrayable
{

    /**
     * @var Collection
     */
    protected $feeCollection;


    public function __construct(array $fees = [])
    {
        $this->feeCollection = new FeeCollection();

        if($fees) {
            foreach ($fees as $fee) {
                $this->add($fee);
            }
        }

    }

    public function get($rowId = '')
    {
        $result = null;

        if(!$rowId) {
            $result = $this->feeCollection;
        }

        if($rowId && $this->exists($rowId)) {
            $result = $this->feeCollection->get($rowId);
        }
        return $result;
    }

    public function getWithTax()
    {
        return $this->feeCollection->where('tax_rate', '!=', 0);
    }

    public function getWithoutTax()
    {
        return $this->feeCollection->where('tax_rate', '=', 0);
    }

    public function calculate($price, $withTax = false): float
    {
        $total = 0;

        if($withTax){
            $feeItems = $this->getWithTax();
        } else {
            $feeItems = $this->getWithoutTax();
        }

        foreach ($feeItems as $fee) {
            $total += $fee->calculate($price);
        }
        return $total;
    }

    public function tax($price, $withTax = false): float
    {
        $total = 0;

        if($withTax){
            $feeItems = $this->getWithTax();
        } else {
            $feeItems = $this->getWithoutTax();
        }

        foreach ($feeItems as $fee) {
            $total += $fee->tax($price);
        }
        return $total;
    }

    public function add(FeeInterface $fee)
    {
        $this->feeCollection->put($fee->getRowId(), $fee);
    }

    public function update($rowId, $fee)
    {
        $this->feeCollection->put($rowId, $fee);
    }

    public function exists(string $rowId)
    {
        return $this->feeCollection->has($rowId);
    }

    public function remove(string $rowId)
    {
        $this->feeCollection->pull($rowId);
    }

    public function toArray()
    {
        return $this->feeCollection->toArray();
    }

    public function __invoke()
    {
       return $this->feeCollection;
    }

}
