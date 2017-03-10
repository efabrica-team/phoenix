<?php

namespace Phoenix\Tests\Command\InitCommand;

use Phoenix\Command\InitCommand;
use Phoenix\Exception\ConfigException;
use Phoenix\Exception\WrongCommandException;
use Phoenix\Tests\Command\BaseCommandTest;
use Symfony\Component\Console\Output\OutputInterface;

abstract class InitCommandTest extends BaseCommandTest
{
    public function testDefaultName()
    {
        $command = new InitCommand();
        $this->assertEquals('init', $command->getName());
    }

    public function testCustomName()
    {
        $command = new InitCommand('my_init');
        $this->assertEquals('my_init', $command->getName());
    }

    public function testMissingDefaultConfig()
    {
        $command = new InitCommand();
        $this->setExpectedException(ConfigException::class, 'No configuration file exists. Create phoenix.php or phoenix.yml or phoenix.neon or phoenix.json in your project root or specify path to your existing config file with --config option');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFileNotFound()
    {
        $command = new InitCommand();
        $this->input->setOption('config', 'xyz.neon');
        $this->setExpectedException(ConfigException::class, 'Configuration file "xyz.neon" doesn\'t exist.');
        $command->run($this->input, $this->output);
    }

    public function testUserConfigFile()
    {
        $command = new InitCommand();
        $this->input->setOption('config', __DIR__ . '/../../../testing_migrations/config/phoenix.php');
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();

        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(5, $messages[0]);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
        $this->assertTrue(count($messages[OutputInterface::VERBOSITY_DEBUG]) > 0);
    }

    public function testSetCustomConfig()
    {
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $messages = $this->output->getMessages();
        $this->assertTrue(is_array($messages));
        $this->assertArrayHasKey(0, $messages);
        $this->assertCount(5, $messages[0]);
        $this->assertEquals('<info>Phoenix initialized</info>' . "\n", $messages[0][1]);
        $this->assertArrayHasKey(OutputInterface::VERBOSITY_DEBUG, $messages);
        $this->assertTrue(count($messages[OutputInterface::VERBOSITY_DEBUG]) > 0);
    }

    public function testMultipleInitialization()
    {
        $command = new InitCommand();
        $command->setConfig($this->configuration);
        $command->run($this->input, $this->output);

        $this->setExpectedException(WrongCommandException::class, 'Phoenix was already initialized, run migrate or rollback command now.');
        $command->run($this->input, $this->output);
    }
}