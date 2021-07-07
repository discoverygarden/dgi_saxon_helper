<?php

namespace Drupal\Tests\dgi_saxon_helper\Unit;

use Drupal\Core\Extension\Extension;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Tests\UnitTestCase;

use Drupal\dgi_saxon_helper\Transformer;

/**
 * Transformer service test(s).
 */
class TransformerTest extends UnitTestCase {

  /**
   * Our transformer service to test.
   *
   * @var \Drupal\dgi_saxon_helper\Transformer
   */
  protected $service;

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $file_system_mock = $this->getMockBuilder(FileSystemInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $file_system_mock->expects($this->any())
      ->method('realpath')
      ->with('dummy_xslt')
      ->will($this->returnValue(__DIR__ . '/../../fixtures/dummy.xslt'));

    $extension_mock = $this->getMockBuilder(Extension::class)
      ->disableOriginalConstructor()
      ->getMock();
    $extension_mock->expects($this->any())
      ->method('getPath')
      ->will($this->returnValue(__DIR__ . '/../../..'));

    $module_handler_mock = $this->getMockBuilder(ModuleHandlerInterface::class)
      ->disableOriginalConstructor()
      ->getMock();
    $module_handler_mock->expects($this->any())
      ->method('getModule')
      ->with('dgi_saxon_helper')
      ->willReturn($extension_mock);

    $factory_mock = $this->getConfigFactoryStub([
      'dgi_saxon_helper.settings' => [
        'saxon_executable' => '/usr/bin/saxonb-xslt',
        'bash_executable' => '/bin/bash',
      ],
    ]);

    $this->service = new Transformer(
      $file_system_mock,
      $module_handler_mock,
      $factory_mock
    );
  }

  /**
   * Test that we can run a basic transform with some parameters.
   */
  public function testBasicTransform() {
    $output = $this->service->transformStringToString('dummy_xslt', '<alpha/>', [
      'one' => 'un',
      'two' => 'deux',
      'three' => 'trois',
    ]);

    $this->assertXmlStringEqualsXmlString('<bravo one="un" two="deux" three="trois"/>', $output, 'Transformed output matches.');
  }

}
