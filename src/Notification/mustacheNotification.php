<?php

declare(strict_types=1);

namespace Notifications\Notification;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Mailer\Mailer;
use Cake\Routing\Asset;
use EmailQueue\EmailQueue;

class mustacheNotification extends baseNotification
{
  protected $mustacheTemplate;
  protected $mustacheVars;

  public function __construct($toUser, $subject, $mustacheTemplate, $mustacheVars)
  {
    parent::__construct();
    $this->mustacheTemplate = $mustacheTemplate;
    $this->mustacheVars = $mustacheVars;
    $this->to = $toUser->email;
    $this->subject = $subject;
  }

  public function toMail()
  {
    $mailer = new Mailer('default');

    $m = new \Mustache_Engine(['entity_flags' => ENT_QUOTES]);
    $rendered_message = $m->render($this->mustacheTemplate, $this->mustacheVars);

    // valida sempre to prima di costruire il messaggio, per via dei namespace (il "root" namespace
    // non accessibile senza \ iniziale) l'errore interno non è intercettabile con catch e (log a parte) 
    // non potrei memorizzare cosa è andato storto nell'invio della notifica
    if (empty($this->to) || !filter_var($this->to, FILTER_VALIDATE_EMAIL)) {
      throw new \Exception("L'email {$this->to} è vuota o non valida");
    }

    $mailer->setFrom($this->from)
      ->setTo($this->to)
      ->setBcc($this->from)
      ->setEmailFormat('html')
      ->setSubject($this->subject)
      ->viewBuilder()
      ->setTemplate('default')
      ->setVars(['content' => $rendered_message]);

    return $mailer->deliver();
  }

  public function toEmailQueue()
  {
    $m = new \Mustache_Engine(['entity_flags' => ENT_QUOTES]);
    $rendered_message = $m->render($this->mustacheTemplate, $this->mustacheVars);

    $data = ['content' => $rendered_message];
    $k = array_key_first($this->from);
    $options = [
      'subject' => $this->subject,
      'layout' => 'default',
      'template' => 'mustache',
      'config' => 'default',
      'send_at' => new FrozenTime('now'),
      'format' => 'html',
      'from_name' => $this->from[$k],
      'from_email' => $k,
      'campaign_id' => $this->mustacheVars['id'],
    ];

    if (Configure::read('MailLogo')) {
      $logoAttachment = [
        'logo.png' => [
          'file' => WWW_ROOT .  Asset::imageUrl(Configure::read('MailLogo')),
          'mimetype' => 'image/png',
          'contentId' => '12345'
        ]
      ];
      $options['attachments'] = $logoAttachment;
    }

    EmailQueue::enqueue($this->to, $data, $options);
  }
}
