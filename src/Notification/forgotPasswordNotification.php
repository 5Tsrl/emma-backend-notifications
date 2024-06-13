<?php

declare(strict_types=1);

namespace Notifications\Notification;

use Cake\Core\Configure;
use Cake\Mailer\Mailer;

class forgotPasswordNotification extends baseNotification
{

  public function __construct($toUser, $token, $referer)
  {
    parent::__construct();
    $s = parse_url($referer, PHP_URL_SCHEME);
    $h = parse_url($referer, PHP_URL_HOST);
    $p = parse_url($referer, PHP_URL_PORT);
    $referer = "$s://$h:$p";
    $this->vars =  ['user' => $toUser, 'token' => $token, 'referer' => $referer];
    $this->subject = 'Password Reset / Attivazione Account';
    $this->to = $toUser->email;
  }
}
