<?php
namespace Dkd\PhpCmis\Test\Unit\Bindings;

use Dkd\PhpCmis\Bindings\BindingSessionInterface;
use Dkd\PhpCmis\Bindings\CmisBinding;
use Dkd\PhpCmis\Bindings\Session;
use Dkd\PhpCmis\SessionParameter;

/**
 * This file is part of php-cmis-client
 *
 * (c) Sascha Egerer <sascha.egerer@dkd.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
class CmisBindingTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructorThrowsExceptionIfNoSessionParametersGiven()
    {
        $this->setExpectedException(
            '\\Dkd\\PhpCmis\\Exception\\CmisRuntimeException',
            'Session parameters must be set!'
        );
        new CmisBinding(new Session(), array());
    }

    public function testConstructorThrowsExceptionIfNoSessionParameterBindingClassIsGiven()
    {
        $this->setExpectedException(
            '\\Dkd\\PhpCmis\\Exception\\CmisInvalidArgumentException',
            'Session parameters do not contain a binding class name!'
        );
        new CmisBinding(new Session(), array('foo' => 'bar'));
    }


    public function testConstructorPutsSessionParametersToSession()
    {
        /** @var BindingSessionInterface|\PHPUnit_Framework_MockObject_MockObject $session */
        $session = $this->getMockBuilder('\\Dkd\\PhpCmis\\Bindings\\BindingSessionInterface')->setMethods(
            array('put')
        )->getMockForAbstractClass();
        $session->expects($this->exactly(2))->method('put');
        new CmisBinding($session, array(SessionParameter::BINDING_CLASS => 'foo'));

        $session = $this->getMockBuilder('\\Dkd\\PhpCmis\\Bindings\\BindingSessionInterface')->setMethods(
            array('put')
        )->getMockForAbstractClass();
        $session->expects($this->exactly(4))->method('put');
        new CmisBinding($session, array(SessionParameter::BINDING_CLASS => 'foo', 1, 2));
    }

    public function testConstructorCreatesRepositoryServiceInstance()
    {
        $binding = new CmisBinding(new Session(), array(SessionParameter::BINDING_CLASS => 'foo'));
        $this->assertAttributeInstanceOf(
            '\\Dkd\\PhpCmis\\Bindings\\Browser\\RepositoryService',
            'repositoryService',
            $binding
        );
    }

    public function testGetCmisBindingsHelperReturnsCmisBindingsHelper()
    {
        $binding = new CmisBinding(new Session(), array(SessionParameter::BINDING_CLASS => 'foo'));
        $this->assertInstanceOf('\\Dkd\\PhpCmis\\Bindings\\CmisBindingsHelper', $binding->getCmisBindingsHelper());
    }

    public function testGetAuthenticationProviderReturnsNullAuthenticationProviderIfNoneIsDefined()
    {
        $cmisBinding = new CmisBinding(new Session(), array(SessionParameter::BINDING_CLASS => 'foo'));

        $this->assertInstanceOf(
            '\\Dkd\\PhpCmis\\Bindings\\Authentication\\NullAuthenticationProvider',
            $cmisBinding->getAuthenticationProvider()
        );
    }

    public function testGetAuthenticationProviderReturnsAuthenticationProviderGivenInConstructor()
    {
        $authenticationProvider = $this->getMockForAbstractClass(
            '\\Dkd\\PhpCmis\\Bindings\\Authentication\\AuthenticationProviderInterface'
        );
        $cmisBinding = new CmisBinding(
            new Session(),
            array(SessionParameter::BINDING_CLASS => 'foo'),
            $authenticationProvider
        );

        $this->assertSame($authenticationProvider, $cmisBinding->getAuthenticationProvider());
    }

    public function testGetAuthenticationProviderReturnsCreatedAuthenticationProviderBasedOnSessionParameter()
    {
        // just create a mock to create a dummy class
        $this->getMockForAbstractClass(
            '\\Dkd\\PhpCmis\\Bindings\\Authentication\\AuthenticationProviderInterface',
            array(),
            'FakeAuthProvider'
        );

        $cmisBinding = new CmisBinding(
            new Session(),
            array(
                SessionParameter::BINDING_CLASS => 'foo',
                SessionParameter::AUTHENTICATION_PROVIDER_CLASS => 'FakeAuthProvider'
            )
        );

        $this->assertInstanceOf(
            'FakeAuthProvider',
            $cmisBinding->getAuthenticationProvider()
        );
    }

    public function testConstructorThrowsExceptionIfGivenAuthenticationProviderClassNameDoesNotImplementExpectedInterface()
    {
        $this->setExpectedException(
            '\\InvalidArgumentException',
            'Authentication provider does not implement AuthenticationProviderInterface!',
            1412787758
        );

        new CmisBinding(
            new Session(),
            array(
                SessionParameter::BINDING_CLASS => 'foo',
                SessionParameter::AUTHENTICATION_PROVIDER_CLASS => '\\DateTime'
            )
        );
    }

    public function testConstructorThrowsExceptionIfGivenAuthenticationProviderClassCouldNotBeInstantiated()
    {
        $this->setExpectedException(
            '\\InvalidArgumentException',
            'Could not load authentication provider: \\Dkd\\PhpCmis\\Test\\Fixtures\\ConstructorThrowsException',
            1412787752
        );

        new CmisBinding(
            new Session(),
            array(
                SessionParameter::BINDING_CLASS => 'foo',
                SessionParameter::AUTHENTICATION_PROVIDER_CLASS
                => '\\Dkd\\PhpCmis\\Test\\Fixtures\\ConstructorThrowsException'
            )
        );
    }

    public function testGetObjectServiceReturnsObjectService()
    {
        // the subject will be mocked because we have to mock getCmisBindingsHelper
        /** @var CmisBinding|\PHPUnit_Framework_MockObject_MockObject $binding */
        $binding = $this->getMockBuilder('\\Dkd\\PhpCmis\\Bindings\\CmisBinding')->setConstructorArgs(
            array(
                new Session(),
                array(SessionParameter::BINDING_CLASS => 'foo')
            )
        )->setMethods(array('getCmisBindingsHelper'))->getMock();

        $cmisBindingsHelperMock = $this->getMockBuilder(
            '\\Dkd\\PhpCmis\\Bindings\\CmisBindingsHelper'
        )->getMock();

        $cmisBindingSessionInterfaceMock = $this->getMockBuilder(
            '\\Dkd\\PhpCmis\\Bindings\\BindingSessionInterface'
        )->setMethods(array('getObjectService'))->getMockForAbstractClass();
        $cmisBindingSessionInterfaceMock->expects($this->any())->method('getObjectService')->willReturn(
            $this->getMockForAbstractClass('\\Dkd\\PhpCmis\\ObjectServiceInterface')
        );

        $cmisBindingsHelperMock->expects($this->any())->method('getSpi')->willReturn($cmisBindingSessionInterfaceMock);

        $binding->expects($this->any())->method('getCmisBindingsHelper')->willReturn(
            $cmisBindingsHelperMock
        );

        $this->assertInstanceOf('\\Dkd\\PhpCmis\\ObjectServiceInterface', $binding->getObjectService());
    }

    public function testGetRepositoryServiceReturnsInstanceOfRepositoryService()
    {
        $binding = new CmisBinding(new Session(), array(SessionParameter::BINDING_CLASS => 'foo'));
        $this->assertInstanceOf('\\Dkd\\PhpCmis\\RepositoryServiceInterface', $binding->getRepositoryService());
    }
}