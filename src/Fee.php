<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 06/05/2018
 * Time: 14:17
 */

namespace Wefabric\Cart;

use Illuminate\Support\Collection;
use Wefabric\Cart\Exceptions\FeeException;
use Illuminate\Contracts\Support\Arrayable;

class Fee implements Arrayable, FeeInterface
{
    const FEE_TYPE_AMOUNT = 'amount';

    const FEE_TYPE_PERCENTAGE = 'percentage';

    public $rowId;

    public $amount;

    public $type;

    public $title = '';

    public $description = '';

    public $taxRate = 0;

    /**
     * @var Collection
     */
    public $meta = null;

    /**
     * @var FeeResult
     */
    public $result;

    protected $types = [
        'amount' => true,
        'percentage' => true
    ];

    public function __construct($type, $amount, $title = '', $description = '', $taxRate = 0, $meta = [])
    {
        $this->setType($type);
        $this->setAmount($amount);
        $this->setTitle($title);
        $this->setDescription($description);
        $this->setTaxRate($taxRate);
        $this->setRowId($this->generateRowId());
        $this->setMeta(collect($meta));
    }

    /**
     * Generate a unique id for the cart item.
     *
     * @param string $id
     * @param array $options
     * @return string
     */
    protected function generateRowId()
    {
        return md5($this->getType() . $this->getTitle() . $this->getAmount());
    }

    /**
     * @return mixed
     */
    public function getRowId(): string
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
     * @return mixed
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType(string $type)
    {
        if (!$this->isType($type)) {
            throw new FeeException(sprintf(
                'Fee type %s does not exist; available types are %s',
                $type,
                implode(', ', array_flip($this->types))
            ));
        }
        $this->type = $type;
    }

    public function isType(string $type): bool
    {
        return isset($this->types[$type]);
    }

    /**
     * @return mixed
     */
    public function getAmount(): float
    {
        return $this->amount;
    }

    /**
     * @param mixed $amount
     */
    public function setAmount(float $amount)
    {
        $this->amount = floatval($amount);
    }

    public function calculate($price = null): float
    {
        $amount = 0.0;
        if ($this->getType() === 'amount') {
            $amount = $this->getAmount();
        } elseif ($this->getType() === 'percentage') {

            if (null === $price) {
                throw new FeeException('To allow the percentage calculation, a price has to be provided');
            }

            $amount = ($this->getAmount() / 100) * $price;
        }

        $this->getResult()->setResult($amount);

        return $amount;
    }

    public function tax($price = null): float
    {
        $amount = ($this->calculate($price) * ($this->getTaxRate() / 100));
        $this->getResult()->setTaxResult($amount);
        return $amount;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title)
    {
        $this->title = $title;
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription(string $description)
    {
        $this->description = $description;
    }

    /**
     * @return bool
     */
    public function hasTaxRate(): bool
    {
        return ($this->getTaxRate() !== 0);
    }

    /**
     * @return int
     */
    public function getTaxRate(): int
    {
        return $this->taxRate;
    }

    /**
     * @param int $taxRate
     */
    public function setTaxRate(int $taxRate): void
    {
        $this->taxRate = $taxRate;
    }

    /**
     * @return FeeResult
     */
    public function getResult(): FeeResult
    {
        if(!$this->result) {
            $this->result = new FeeResult();
        }

        return $this->result;
    }

    /**
     * @param FeeResult $result
     */
    public function setResult(FeeResult $result): void
    {
        $this->result = $result;
    }

    /**
     * @return Collection
     */
    public function getMeta(): Collection
    {
        if(!$this->meta) {
            $this->meta = new Collection();
        }

        return $this->meta;
    }

    /**
     * @param Collection $meta
     */
    public function setMeta(Collection $meta): void
    {
        $this->meta = $meta;
    }

    public function toArray()
    {
        return [
            'type' => $this->getType(),
            'amount' => $this->getAmount(),
            'title' => $this->getTitle(),
            'description' => $this->getDescription(),
            'taxRate' => $this->getTaxRate(),
            'result' => $this->getResult()->toArray(),
            'meta' => $this->getMeta()->toArray()
        ];
    }
}
