<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Theme\Controller\Result;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Serialize\Serializer\Json as SerializerJson;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Stdlib\Cookie\CookieSizeLimitReachedException;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Framework\Translate\Inline\ParserInterface;
use Magento\Framework\Translate\InlineInterface;
use Magento\Framework\Session\Config\ConfigInterface;
use Magento\Framework\View\Element\Message\InterpretationStrategyInterface;

/**
 * Plugin for putting messages to cookies
 *
 * @SuppressWarnings(PHPMD.CookieAndSessionMisuse)
 */
class MessagePlugin
{
    /**
     * Cookies name for messages
     */
    public const MESSAGES_COOKIES_NAME = 'mage-messages';

    /**
     * @param CookieManagerInterface $cookieManager
     * @param CookieMetadataFactory $cookieMetadataFactory
     * @param ManagerInterface $messageManager
     * @param InterpretationStrategyInterface $interpretationStrategy
     * @param SerializerJson $serializer
     * @param InlineInterface $inlineTranslate
     * @param ConfigInterface $sessionConfig
     */
    public function __construct(
        private readonly CookieManagerInterface $cookieManager,
        private readonly CookieMetadataFactory $cookieMetadataFactory,
        private readonly ManagerInterface $messageManager,
        private readonly InterpretationStrategyInterface $interpretationStrategy,
        private readonly SerializerJson $serializer,
        private readonly InlineInterface $inlineTranslate,
        protected readonly ConfigInterface $sessionConfig
    ) {
    }

    /**
     * Set 'mage-messages' cookie
     *
     * Checks the result that controller actions must return. If result is not JSON type, then
     * sets 'mage-messages' cookie.
     *
     * @param ResultInterface $subject
     * @param ResultInterface $result
     * @return ResultInterface
     */
    public function afterRenderResult(
        ResultInterface $subject,
        ResultInterface $result
    ) {
        if (!($subject instanceof Json)) {
            $newMessages = [];
            foreach ($this->messageManager->getMessages(true)->getItems() as $message) {
                $newMessages[] = [
                    'type' => $message->getType(),
                    'text' => $this->interpretationStrategy->interpret($message),
                ];
            }
            if (!empty($newMessages)) {
                $this->setMessages($this->getCookiesMessages(), $newMessages);
            }
        }
        return $result;
    }

    /**
     * Add new messages to already existing ones.
     *
     * In case if there are too many messages clear old messages.
     *
     * @param array $oldMessages
     * @param array $newMessages
     * @throws CookieSizeLimitReachedException
     */
    private function setMessages(array $oldMessages, array $newMessages): void
    {
        $messages = array_merge($oldMessages, $newMessages);
        try {
            $this->setCookie($messages);
        } catch (CookieSizeLimitReachedException $e) {
            if (empty($oldMessages)) {
                throw $e;
            }

            array_shift($oldMessages);
            $this->setMessages($oldMessages, $newMessages);
        }
    }

    /**
     * Set 'mage-messages' cookie with 'messages' array
     *
     * Checks the $messages argument. If $messages is not an empty array, then
     * sets 'mage-messages' public cookie:
     *
     *   Cookie Name: 'mage-messages';
     *   Cookie Duration: 1 year;
     *   Cookie Path: /;
     *   Cookie HTTP Only flag: FALSE. Cookie can be accessed by client-side APIs.
     *
     * The 'messages' list has format:
     * [
     *   [
     *     'type' => 'type_value',
     *     'text' => 'cookie_value',
     *   ],
     * ]
     *
     * @param array $messages List of Magento messages that must be set as 'mage-messages' cookie.
     * @return void
     */
    private function setCookie(array $messages)
    {
        if (!empty($messages)) {
            if ($this->inlineTranslate->isAllowed()) {
                foreach ($messages as &$message) {
                    $message['text'] = $this->convertMessageText($message['text']);
                }
            }

            $publicCookieMetadata = $this->cookieMetadataFactory->createPublicCookieMetadata();
            $publicCookieMetadata->setDurationOneYear();
            $publicCookieMetadata->setPath($this->sessionConfig->getCookiePath());
            $publicCookieMetadata->setHttpOnly(false);
            $publicCookieMetadata->setSameSite('Strict');

            $this->cookieManager->setPublicCookie(
                self::MESSAGES_COOKIES_NAME,
                $this->serializer->serialize($messages),
                $publicCookieMetadata
            );
        }
    }

    /**
     * Replace wrapping translation with html body.
     *
     * @param string $text
     * @return string
     */
    private function convertMessageText(string $text): string
    {
        if (preg_match('#' . ParserInterface::REGEXP_TOKEN . '#', $text, $matches)) {
            $text = $matches[1];
        }

        return $text;
    }

    /**
     * Return messages stored in cookies
     *
     * @return array
     */
    protected function getCookiesMessages()
    {
        $messages = $this->cookieManager->getCookie(self::MESSAGES_COOKIES_NAME);
        if (!$messages) {
            return [];
        }
        $messages = $this->serializer->unserialize($messages);
        if (!is_array($messages)) {
            $messages = [];
        }
        return $messages;
    }
}
