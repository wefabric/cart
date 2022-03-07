<?php
/**
 * Created by PhpStorm.
 * User: leoflapper
 * Date: 03/05/2018
 * Time: 18:33
 */

namespace Wefabric\Cart;


use Wefabric\Countries\Countries\CountryInterface;

class CartCustomer
{

    protected $userId = 0;

    protected $name = '';

    protected $email = '';

    protected $title = '';

    protected $message = '';

    protected $country = '';

    public function __construct($name, $email, $userId = 0, $title = '', $message = '')
    {
        $this->setName($name);
        $this->setEmail($email);
        $this->setUserId($userId);
        $this->setTitle($title);
        $this->setMessage($message);
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
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message)
    {
        $this->message = $message;
    }

    /**
     * @return int
     */
    public function getUserId(): int
    {
        return $this->userId;
    }

    /**
     * @param int $userId
     */
    public function setUserId(int $userId)
    {
        $this->userId = $userId;
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
     * @return string
     */
    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * @param string $email
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
    }

    /**
     * @return string
     */
    public function getCountry(): CountryInterface
    {
        return $this->country;
    }

    /**
     * @param string $country
     */
    public function setCountry(CountryInterface $country)
    {
        $this->country = $country;
    }
    
    public function toArray()
    {
        return [
            'name' => $this->getName(),
            'email' => $this->getEmail(),
            'user_id' => $this->getUserId(),
            'title' => $this->getTitle(),
            'message' => $this->getMessage()
        ];
    }


}
