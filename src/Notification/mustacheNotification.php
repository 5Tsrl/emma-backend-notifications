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
  
    $options = [
      'subject' => $this->subject,
      'layout' => 'default',
      'template' => 'mustache',
      'config' => 'default',
      'send_at' => new FrozenTime('now'),
      'format' => 'html',

      'campaign_id' => $this->mustacheVars['id'],
    ];

    if (is_array($this->from)){
      $options['from_name'] = array_key_first($this->from); 
      $options['from_email'] = array_pop($this->from); 
    } else {
      $options['from_email'] = $this->from; 
    }

    if (Configure::read('MailLogo')) {
      $file = WWW_ROOT . Configure::read('MailLogo');
      if (file_exists($file)) {
        $logoAttachment = [
          'logo.png' => [
            'file' => $file,
            'mimetype' => 'image/png',
            'contentId' => '12345'
          ]
        ];
      }
      $options['attachments'] = $logoAttachment;
    }

    EmailQueue::enqueue($this->to, $data, $options);
  }
}
