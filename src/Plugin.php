<?php

namespace Notifications;

use Cake\Core\BasePlugin;
use Cake\Core\PluginApplicationInterface;

class Plugin extends BasePlugin
{
  public function bootstrap(PluginApplicationInterface $app): void
  {
    // Add constants, load configuration defaults.
    // By default will load `config/bootstrap.php` in the plugin.
    parent::bootstrap($app);
  }
}
