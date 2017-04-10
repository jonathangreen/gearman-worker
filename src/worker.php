#!/usr/bin/env php
<?php
require __DIR__ . '/../vendor/autoload.php';

use \Commando\Command;
use \React\EventLoop\Factory as EventFactory;
use \React\ChildProcess\Process;
use \React\Stream\Stream;

// Parse command line options.
$cmd = new Command();
$cmd->setHelp('Script for starting Islandora Gearman workers.');
$cmd->option('root')
  ->describedAs('drupal root for execing drush command')
  ->required();
$cmd->option('drush')
  ->describedAs('path to drush binary')
  ->required();
$cmd->parse();

$loop = EventFactory::create();
$stdout = new Stream(STDOUT, $loop);
$stderr = new Stream(STDERR, $loop);

// Get input data
$input_data = stream_get_contents(STDIN);
$input_data_decoded = json_decode($input_data, TRUE);

// Make sure script arguements are set correctly
if (isset($input_data_decoded['uid'])) {
  $uid = $input_data_decoded['uid'];
}
else {
  $uid = 1;
  $stderr->write("Warning: UID not set\n");
}

if (isset($input_data_decoded['site'])) {
  $site = "--uri=${input_data_decoded['site']}";
} 
else {
  $stderr->write("Warning: site not set\n");
  $site = '';
}

// Run drush process
$drush = new Process("${cmd['drush']} --root=${cmd['root']} $site -u $uid islandora-job-router");
$drush->start($loop);
$drush->stdout->pipe($stdout);
$drush->stderr->pipe($stderr);
$drush->stdin->write($input_data);
$drush->stdin->end();
$loop->run();
