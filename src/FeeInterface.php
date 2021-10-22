<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 06/05/2018
 * Time: 15:34
 */

namespace Wefabric\Cart;


interface FeeInterface
{

    /**
     * @return mixed
     */
    public function getRowId(): string;

    /**
     * @return mixed
     */
    public function getType(): string;

    public function isType(string $type): bool;

    /**
     * @return mixed
     */
    public function getAmount(): float;

    public function calculate($price = null);

    public function tax($price = null);
    /**
     * @return string
     */
    public function getLabel(): string;

    /**
     * @return string
     */
    public function getDescription(): string;

    /**
     * @return bool
     */
    public function hasTaxRate(): bool;

    /**
     * @return int
     */
    public function getTaxRate(): int;
}