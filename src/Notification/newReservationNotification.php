<?php

declare(strict_types=1);

namespace Notifications\Notification;

class newReservationNotification extends baseNotification
{
  public function __construct($toUser, $dt, $token, $referer)
  {
    parent::__construct();
    $s = parse_url($referer, PHP_URL_SCHEME);
    $h = parse_url($referer, PHP_URL_HOST);
    $p = parse_url($referer, PHP_URL_PORT);
    $referer = "$s://$h:$p";
    $this->to = $toUser->email;
    $this->subject = 'Example 🚍 - New Request Received';
    $this->vars = ['user' => $toUser, 'dt' => $dt, 'token' => $token, 'referer' => $referer];
  }
}
