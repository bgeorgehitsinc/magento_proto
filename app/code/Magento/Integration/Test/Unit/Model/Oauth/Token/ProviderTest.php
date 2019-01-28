<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Integration\Test\Unit\Model\Oauth\Token;

use Magento\Authorization\Model\UserContextInterface;
use Magento\Integration\Model\Oauth\Token;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;

/**
 * Unit test for \Magento\Integration\Model\Oauth\Token\Provider
 */
class ProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var \Magento\Integration\Model\Oauth\Token\Provider */
    protected $tokenProvider;

    /** @var \Magento\Integration\Model\Oauth\ConsumerFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $consumerFactoryMock;

    /** @var \Magento\Integration\Model\Oauth\TokenFactory|\PHPUnit_Framework_MockObject_MockObject */
    protected $tokenFactoryMock;

    /** @var \Psr\Log\LoggerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $loggerMock;

    /** @var \Magento\Framework\Oauth\ConsumerInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $consumerMock;

    /** @var \Magento\Integration\Model\Oauth\Token|\PHPUnit_Framework_MockObject_MockObject */
    protected $requestTokenMock;

    /** @var \Magento\Integration\Model\Oauth\Token|\PHPUnit_Framework_MockObject_MockObject */
    protected $accessTokenMock;

    protected function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->consumerFactoryMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\ConsumerFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenFactoryMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\TokenFactory::class)
            ->setMethods(['create'])
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(\Psr\Log\LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->consumerMock = $this->getMockBuilder(\Magento\Framework\Oauth\ConsumerInterface::class)
            ->setMethods(
                [
                    'load',
                    'loadByKey',
                    'validate',
                    'getId',
                    'getKey',
                    'getSecret',
                    'getCallbackUrl',
                    'getCreatedAt',
                    'isValidForTokenExchange'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestTokenMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\Token::class)
            ->setMethods(
                [
                    'loadByConsumerIdAndUserType',
                    'load',
                    'getId',
                    'getConsumerId',
                    'getType',
                    'getSecret',
                    'getToken',
                    'getVerifier',
                    'createRequestToken',
                    'convertToAccess'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->accessTokenMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\Token::class)
            ->setMethods(
                [
                    'getToken',
                    'getSecret',
                    'load',
                    'getId',
                    'getConsumerId',
                    'getType',
                    'getRevoked'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenProvider = $objectManagerHelper->getObject(
            \Magento\Integration\Model\Oauth\Token\Provider::class,
            [
                'consumerFactory' => $this->consumerFactoryMock,
                'tokenFactory' => $this->tokenFactoryMock,
                'logger' => $this->loggerMock,
            ]
        );
    }

    public function testValidateConsumer()
    {
        $this->consumerMock->expects($this->once())->method('isValidForTokenExchange')->willReturn(true);
        $this->assertEquals(true, $this->tokenProvider->validateConsumer($this->consumerMock));
    }

    /**
     */
    public function testValidateConsumerException()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Consumer key has expired');

        $this->consumerMock->expects($this->once())->method('isValidForTokenExchange')->willReturn(false);
        $this->tokenProvider->validateConsumer($this->consumerMock);
    }

    public function testGetIntegrationTokenByConsumerId()
    {
        $consumerId = 1;
        $tokenId = 1;

        $this->requestTokenMock->expects($this->once())
            ->method('loadByConsumerIdAndUserType')
            ->with($consumerId, UserContextInterface::USER_TYPE_INTEGRATION);
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);

        $this->requestTokenMock->expects($this->once())->method('getId')->willReturn($tokenId);

        $actualToken = $this->tokenProvider->getIntegrationTokenByConsumerId($consumerId);
        $this->assertEquals($this->requestTokenMock, $actualToken);
    }

    /**
     */
    public function testGetIntegrationTokenByConsumerIdException()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'A token with consumer ID 1 does not exist');

        $consumerId = 1;
        $tokenId = false;

        $this->requestTokenMock->expects($this->once())
            ->method('loadByConsumerIdAndUserType')
            ->with($consumerId, UserContextInterface::USER_TYPE_INTEGRATION);
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);

        $this->requestTokenMock->expects($this->once())->method('getId')->willReturn($tokenId);

        $this->tokenProvider->getIntegrationTokenByConsumerId($consumerId);
    }

    public function testCreateRequestToken()
    {
        $consumerId = 1;
        $tokenId = 1;
        $tokenString = '12345678901234567890123456789012';
        $secret = 'secret';

        $tokenMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\Token::class)
            ->setMethods(
                [
                    'loadByConsumerIdAndUserType',
                    'getId',
                    'getType',
                    'createRequestToken'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $tokenMock->expects($this->once())
            ->method('loadByConsumerIdAndUserType')
            ->with($consumerId, UserContextInterface::USER_TYPE_INTEGRATION);
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($tokenMock);

        $tokenMock->expects($this->any())->method('getId')->willReturn($tokenId);
        $tokenMock->expects($this->once())->method('createRequestToken')->willReturn(
            $this->requestTokenMock
        );
        $tokenMock->expects($this->any())->method('getType')->willReturn(Token::TYPE_VERIFIER);

        $this->consumerMock->expects($this->once())->method('getId')->willReturn($consumerId);
        $this->consumerMock->expects($this->once())->method('getCallbackUrl');

        $this->requestTokenMock->expects($this->any())->method('getToken')->willReturn($tokenString);
        $this->requestTokenMock->expects($this->any())->method('getSecret')->willReturn($secret);
        $response = $this->tokenProvider->createRequestToken($this->consumerMock);

        $this->assertArrayHasKey('oauth_token', $response);
        $this->assertArrayHasKey('oauth_token_secret', $response);
        $this->assertEquals($tokenString, $response['oauth_token']);
        $this->assertEquals($secret, $response['oauth_token_secret']);
    }

    /**
     */
    public function testCreateRequestTokenIncorrectType()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Cannot create request token because consumer token is not a verifier token');

        $consumerId = 1;
        $tokenId = 1;

        $tokenMock = $this->getMockBuilder(\Magento\Integration\Model\Oauth\Token::class)
            ->setMethods(
                [
                    'loadByConsumerIdAndUserType',
                    'getId',
                    'getType',
                    'createRequestToken'
                ]
            )
            ->disableOriginalConstructor()
            ->getMock();

        $tokenMock->expects($this->once())
            ->method('loadByConsumerIdAndUserType')
            ->with($consumerId, UserContextInterface::USER_TYPE_INTEGRATION);
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($tokenMock);

        $tokenMock->expects($this->any())->method('getId')->willReturn($tokenId);
        $tokenMock->expects($this->any())->method('getType')->willReturn('incorrectType');

        $this->consumerMock->expects($this->once())->method('getId')->willReturn($consumerId);

        $this->tokenProvider->createRequestToken($this->consumerMock);
    }

    public function testGetAccessToken()
    {
        $consumerId = 1;
        $tokenId = 1;
        $tokenString = '12345678901234567890123456789012';
        $secret = 'secret';

        $this->consumerMock->expects($this->once())->method('getId')->willReturn($consumerId);

        $this->requestTokenMock->expects($this->once())
            ->method('loadByConsumerIdAndUserType')
            ->with($consumerId, UserContextInterface::USER_TYPE_INTEGRATION);
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);

        $this->requestTokenMock->expects($this->once())->method('getId')->willReturn($tokenId);
        $this->requestTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_REQUEST);
        $this->requestTokenMock->expects($this->once())->method('convertToAccess')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->once())->method('getToken')->willReturn($tokenString);
        $this->accessTokenMock->expects($this->once())->method('getSecret')->willReturn($secret);

        $response = $this->tokenProvider->getAccessToken($this->consumerMock);
        $this->assertArrayHasKey('oauth_token', $response);
        $this->assertArrayHasKey('oauth_token_secret', $response);
        $this->assertEquals($tokenString, $response['oauth_token']);
        $this->assertEquals($secret, $response['oauth_token_secret']);
    }

    /**
     */
    public function testGetAccessTokenIsNotRequestToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Cannot get access token because consumer token is not a request token');

        $consumerId = 1;
        $tokenId = 1;

        $this->consumerMock->expects($this->once())->method('getId')->willReturn($consumerId);

        $this->requestTokenMock->expects($this->once())
            ->method('loadByConsumerIdAndUserType')
            ->with($consumerId, UserContextInterface::USER_TYPE_INTEGRATION);
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);

        $this->requestTokenMock->expects($this->once())->method('getId')->willReturn($tokenId);
        $this->requestTokenMock->expects($this->once())->method('getType')->willReturn('isNotRequestToken');

        $this->tokenProvider->getAccessToken($this->consumerMock);
    }

    public function testValidateRequestToken()
    {
        $requestTokenString = '12345678901234567890123456789012';
        $oauthVerifier = '12345678901234567890123456789012';
        $consumerId = 1;
        $tokenId = 1;
        $secret = 'secret';

        $this->requestTokenMock->expects($this->once())
            ->method('load')
            ->with($requestTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);
        $this->requestTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->requestTokenMock->expects($this->once())->method('getConsumerId')->willReturn($consumerId);

        $this->requestTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_REQUEST);
        $this->requestTokenMock->expects($this->once())->method('getSecret')->willReturn($secret);
        $this->requestTokenMock->expects($this->once())->method('getVerifier')->willReturn($oauthVerifier);

        $this->assertEquals(
            $secret,
            $this->tokenProvider->validateRequestToken($requestTokenString, $this->consumerMock, $oauthVerifier)
        );
    }

    /**
     */
    public function testValidateRequestTokenNotExistentToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Specified token does not exist');

        $requestTokenString = '12345678901234567890123456789012';
        $oauthVerifier = '12345678901234567890123456789012';

        $this->requestTokenMock->expects($this->once())
            ->method('load')
            ->with($requestTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);
        $this->requestTokenMock->expects($this->any())->method('getId')->willReturn(0);

        $this->tokenProvider->validateRequestToken($requestTokenString, $this->consumerMock, $oauthVerifier);
    }

    /**
     */
    public function testValidateRequestTokenIncorrectLengthToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'The token length is invalid. Check the length and try again.');

        $requestTokenString = '123';
        $oauthVerifier = '12345678901234567890123456789012';

        $this->tokenProvider->validateRequestToken($requestTokenString, $this->consumerMock, $oauthVerifier);
    }

    /**
     */
    public function testValidateRequestTokenInvalidVerifier()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Verifier is invalid');

        $requestTokenString = '12345678901234567890123456789012';
        $oauthVerifier = 1;
        $consumerId = 1;
        $tokenId = 1;

        $this->requestTokenMock->expects($this->once())
            ->method('load')
            ->with($requestTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);
        $this->requestTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->requestTokenMock->expects($this->once())->method('getConsumerId')->willReturn($consumerId);

        $this->requestTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_REQUEST);
        $this->requestTokenMock->expects($this->once())->method('getVerifier')->willReturn($oauthVerifier);

        $this->tokenProvider->validateRequestToken($requestTokenString, $this->consumerMock, $oauthVerifier);
    }

    /**
     */
    public function testValidateRequestTokenIncorrectLengthVerifier()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Verifier is not the correct length');

        $requestTokenString = '12345678901234567890123456789012';
        $oauthVerifier = '123';
        $consumerId = 1;
        $tokenId = 1;

        $this->requestTokenMock->expects($this->once())
            ->method('load')
            ->with($requestTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);
        $this->requestTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->requestTokenMock->expects($this->once())->method('getConsumerId')->willReturn($consumerId);

        $this->requestTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_REQUEST);
        $this->requestTokenMock->expects($this->once())->method('getVerifier')->willReturn($oauthVerifier);

        $this->tokenProvider->validateRequestToken($requestTokenString, $this->consumerMock, $oauthVerifier);
    }

    /**
     */
    public function testValidateRequestTokenNotMatchedVerifier()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Token verifier and verifier token do not match');

        $requestTokenString = '12345678901234567890123456789012';
        $oauthVerifier = '12345678901234567890123456789012';
        $notMatchedVerifier = '123';
        $consumerId = 1;
        $tokenId = 1;

        $this->requestTokenMock->expects($this->once())
            ->method('load')
            ->with($requestTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);
        $this->requestTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->requestTokenMock->expects($this->once())->method('getConsumerId')->willReturn($consumerId);

        $this->requestTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_REQUEST);
        $this->requestTokenMock->expects($this->once())->method('getVerifier')->willReturn($notMatchedVerifier);

        $this->tokenProvider->validateRequestToken($requestTokenString, $this->consumerMock, $oauthVerifier);
    }

    /**
     */
    public function testValidateRequestTokenNotAssociatedToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Request token is not associated with the specified consumer');

        $requestTokenString = '12345678901234567890123456789012';
        $oauthVerifier = '12345678901234567890123456789012';
        $consumerId = 1;
        $notCustomerId = 2;
        $tokenId = 1;

        $this->requestTokenMock->expects($this->once())
            ->method('load')
            ->with($requestTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);
        $this->requestTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->requestTokenMock->expects($this->once())->method('getConsumerId')->willReturn($notCustomerId);

        $this->tokenProvider->validateRequestToken($requestTokenString, $this->consumerMock, $oauthVerifier);
    }

    /**
     */
    public function testValidateRequestTokenAlreadyUsedToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Token is already being used');

        $requestTokenString = '12345678901234567890123456789012';
        $oauthVerifier = '12345678901234567890123456789012';
        $consumerId = 1;
        $tokenId = 1;

        $this->requestTokenMock->expects($this->once())
            ->method('load')
            ->with($requestTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->requestTokenMock);
        $this->requestTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->requestTokenMock->expects($this->once())->method('getConsumerId')->willReturn($consumerId);

        $this->requestTokenMock->expects($this->once())->method('getType')->willReturn('alreadyUsedToken');

        $this->tokenProvider->validateRequestToken($requestTokenString, $this->consumerMock, $oauthVerifier);
    }

    public function testValidateAccessTokenRequest()
    {
        $accessTokenString = '12345678901234567890123456789012';
        $tokenId = 1;
        $consumerId = 1;
        $secret = 'secret';

        $this->accessTokenMock->expects($this->once())
            ->method('load')
            ->with($accessTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->accessTokenMock->expects($this->once())->method('getConsumerId')->willReturn($consumerId);

        $this->accessTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_ACCESS);
        $this->accessTokenMock->expects($this->once())->method('getRevoked')->willReturn(0);

        $this->accessTokenMock->expects($this->once())->method('getSecret')->willReturn($secret);

        $this->assertEquals(
            $secret,
            $this->tokenProvider->validateAccessTokenRequest($accessTokenString, $this->consumerMock)
        );
    }

    /**
     */
    public function testValidateAccessTokenRequestNotAssociatedToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Token is not associated with the specified consumer');

        $accessTokenString = '12345678901234567890123456789012';
        $tokenId = 1;
        $consumerId = 1;
        $notCustomerId = 2;

        $this->accessTokenMock->expects($this->once())
            ->method('load')
            ->with($accessTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->accessTokenMock->expects($this->once())->method('getConsumerId')->willReturn($notCustomerId);

        $this->tokenProvider->validateAccessTokenRequest($accessTokenString, $this->consumerMock);
    }

    /**
     */
    public function testValidateAccessTokenRequestNotAccessToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Token is not an access token');

        $accessTokenString = '12345678901234567890123456789012';
        $tokenId = 1;
        $consumerId = 1;

        $this->accessTokenMock->expects($this->once())
            ->method('load')
            ->with($accessTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->accessTokenMock->expects($this->once())->method('getConsumerId')->willReturn($consumerId);

        $this->accessTokenMock->expects($this->once())->method('getType')->willReturn('notAccessToken');

        $this->tokenProvider->validateAccessTokenRequest($accessTokenString, $this->consumerMock);
    }

    /**
     */
    public function testValidateAccessTokenRequestRevokedToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Access token has been revoked');

        $accessTokenString = '12345678901234567890123456789012';
        $tokenId = 1;
        $consumerId = 1;

        $this->accessTokenMock->expects($this->once())
            ->method('load')
            ->with($accessTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);
        $this->accessTokenMock->expects($this->once())->method('getConsumerId')->willReturn($consumerId);

        $this->accessTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_ACCESS);
        $this->accessTokenMock->expects($this->once())->method('getRevoked')->willReturn(1);

        $this->tokenProvider->validateAccessTokenRequest($accessTokenString, $this->consumerMock);
    }

    public function testValidateAccessToken()
    {
        $accessTokenString = '12345678901234567890123456789012';
        $tokenId = 1;
        $consumerId = 1;

        $this->accessTokenMock->expects($this->once())
            ->method('load')
            ->with($accessTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->accessTokenMock->expects($this->any())->method('getConsumerId')->willReturn($consumerId);

        $this->consumerFactoryMock->expects($this->any())->method('create')->willReturn($this->consumerMock);
        $this->consumerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);

        $this->accessTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_ACCESS);
        $this->accessTokenMock->expects($this->once())->method('getRevoked')->willReturn(0);
        $this->assertEquals(
            $consumerId,
            $this->tokenProvider->validateAccessToken($accessTokenString)
        );
    }

    /**
     */
    public function testValidateAccessTokenNotExistentConsumer()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'A consumer with the ID 1 does not exist');

        $accessTokenString = '12345678901234567890123456789012';
        $tokenId = 1;
        $consumerId = 1;

        $this->accessTokenMock->expects($this->once())
            ->method('load')
            ->with($accessTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->accessTokenMock->expects($this->any())->method('getConsumerId')->willReturn($consumerId);

        $this->consumerFactoryMock->expects($this->any())->method('create')->willReturn($this->consumerMock);
        $this->consumerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->consumerMock->expects($this->any())->method('getId')->willReturn(0);

        $this->tokenProvider->validateAccessToken($accessTokenString);
    }

    /**
     */
    public function testValidateAccessTokenNotAccessToken()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Token is not an access token');

        $accessTokenString = '12345678901234567890123456789012';
        $tokenId = 1;
        $consumerId = 1;

        $this->accessTokenMock->expects($this->once())
            ->method('load')
            ->with($accessTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->accessTokenMock->expects($this->any())->method('getConsumerId')->willReturn($consumerId);

        $this->consumerFactoryMock->expects($this->any())->method('create')->willReturn($this->consumerMock);
        $this->consumerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);

        $this->accessTokenMock->expects($this->once())->method('getType')->willReturn('notAccessToken');
        $this->tokenProvider->validateAccessToken($accessTokenString);
    }

    /**
     */
    public function testValidateAccessTokenRevoked()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Access token has been revoked');

        $accessTokenString = '12345678901234567890123456789012';
        $tokenId = 1;
        $consumerId = 1;

        $this->accessTokenMock->expects($this->once())
            ->method('load')
            ->with($accessTokenString, 'token')
            ->willReturnSelf();
        $this->tokenFactoryMock->expects($this->once())->method('create')->willReturn($this->accessTokenMock);
        $this->accessTokenMock->expects($this->any())->method('getId')->willReturn($tokenId);

        $this->accessTokenMock->expects($this->any())->method('getConsumerId')->willReturn($consumerId);

        $this->consumerFactoryMock->expects($this->any())->method('create')->willReturn($this->consumerMock);
        $this->consumerMock->expects($this->any())->method('load')->willReturnSelf();
        $this->consumerMock->expects($this->any())->method('getId')->willReturn($consumerId);

        $this->accessTokenMock->expects($this->once())->method('getType')->willReturn(Token::TYPE_ACCESS);
        $this->accessTokenMock->expects($this->once())->method('getRevoked')->willReturn(1);

        $this->tokenProvider->validateAccessToken($accessTokenString);
    }

    public function testValidateOauthToken()
    {
        $tokenString = '12345678901234567890123456789012';
        $this->assertTrue($this->tokenProvider->validateOauthToken($tokenString));
    }

    public function testGetConsumerByKey()
    {
        $consumerKeyString = '12345678901234567890123456789012';
        $consumerId = 1;

        $this->consumerFactoryMock->expects($this->once())->method('create')->willReturn($this->consumerMock);
        $this->consumerMock->expects($this->once())->method('loadByKey')->with($consumerKeyString)->willReturnSelf();
        $this->consumerMock->expects($this->once())->method('getId')->willReturn($consumerId);

        $this->assertEquals($this->consumerMock, $this->tokenProvider->getConsumerByKey($consumerKeyString));
    }

    /**
     */
    public function testGetConsumerByKeyWrongConsumerKey()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'Consumer key is not the correct length');

        $consumerKeyString = '123';
        $this->tokenProvider->getConsumerByKey($consumerKeyString);
    }

    /**
     */
    public function testGetConsumerByKeyNonExistentConsumer()
    {
        $this->setExpectedException(\Magento\Framework\Oauth\Exception::class, 'A consumer having the specified key does not exist');

        $consumerKeyString = '12345678901234567890123456789012';
        $consumerId = null;

        $this->consumerFactoryMock->expects($this->once())->method('create')->willReturn($this->consumerMock);
        $this->consumerMock->expects($this->once())->method('loadByKey')->with($consumerKeyString)->willReturnSelf();
        $this->consumerMock->expects($this->once())->method('getId')->willReturn($consumerId);

        $this->tokenProvider->getConsumerByKey($consumerKeyString);
    }
}
