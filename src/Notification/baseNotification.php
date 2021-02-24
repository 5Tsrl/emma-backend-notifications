<?php

declare(strict_types=1);

namespace Notifications\Notification;

use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\Routing\Asset;
use Composer\Config;
use Notifications\Model\Table\NotificationsTable;

class baseNotification
{
  protected $template;
  protected $subject;
  protected $from;
  protected $to;
  protected $cc;
  protected $vars = [];

  public function __construct()
  {
    $className = (new \ReflectionClass($this))->getShortName();
    $theme = Configure::read('theme');
    if (!empty($theme)) {
      $theme .= '.';
    };
    $this->template = $theme . strstr($className, 'Notification', true);
    $this->subject = 'Notifica';
    $this->from = Configure::read('MailAdmin');
    $this->to = Configure::read('MailAdmin');
    $this->cc = Configure::read('MailAdmin');
  }

  public function toMail()
  {
    $mailer = new Mailer('default');

    $mailer->setFrom($this->from)
      ->setTo($this->to)
      ->setEmailFormat('html')
      ->setSubject($this->subject)
      ->viewBuilder()
      ->setTemplate($this->template)
      ->setVars($this->vars);

    if (!empty($this->cc)) {
      $mailer->setCC($this->cc);
    }

    if (Configure::read('MailLogo')) {
      $file = WWW_ROOT . Configure::read('MailLogo');
      if (file_exists($file)) {
        $mailer->setAttachments([
          'logo.png' => [
            'file' => $file,
            'mimetype' => 'image/png',
            'contentId' => '12345'
          ]
        ]);
      }
    }

    $mailer->deliver();
  }

  //devo scrivere la notifica a db
  public function toDB()
  {
    $notifications = new NotificationsTable();
    $notifications->store([
      'channel' => 'email',
      'type' => 'new-timetable',
      'from_mail' => json_encode($this->from),
      'to_mail' => json_encode($this->to),
      'subject' => $this->subject,
      'payload' => json_encode($this->vars),
      'template' => $this->template,
    ]);
  }
}
