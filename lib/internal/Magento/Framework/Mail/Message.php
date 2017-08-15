<?php
/**
 * Mail Message
 *
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Framework\Mail;

class Message extends \Zend_Mail implements MessageInterface
{
    /**
     * @param string $charset
     */
    public function __construct($charset = 'utf-8')
    {
        parent::__construct($charset);
    }

    /**
     * Message type
     *
     * @var string
     */
    protected $messageType = self::TYPE_TEXT;

    /**
     * Set message body
     *
     * @param string $body
     * @return $this
     */
    public function setBody($body)
    {
        return $this->messageType == self::TYPE_TEXT ? $this->setBodyText($body) : $this->setBodyHtml($body);
    }
    
    /**
     * Set from address
     *
     * @param string|array $email
     * @return $this
     */
    public function setFrom($email)
    {
        $name = null;
        if (is_array($email)) {
            $name = array_keys($email)[0];
            $name = !is_int($name) ? $name : null;
            $email = array_values($email)[0];
        }
        return parent::setFrom($email, $name);
    }

    /**
     * Set message body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->messageType == self::TYPE_TEXT ? $this->getBodyText() : $this->getBodyHtml();
    }

    /**
     * Set message type
     *
     * @param string $type
     * @return $this
     */
    public function setMessageType($type)
    {
        $this->messageType = $type;
        return $this;
    }
}
