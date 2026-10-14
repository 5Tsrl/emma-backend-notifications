<?php

declare(strict_types=1);

namespace Notifications\Notification;

use Cake\Core\Configure;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\Mailer\Mailer;
use Cake\Routing\Asset;
use EmailQueue\EmailQueue;
use Exception;

class mustacheNotification extends baseNotification
{
  protected $mustacheTemplate;
  protected $mustacheVars;

  public function __construct($toUser, $subject, $mustacheTemplate, $mustacheVars, $mailer = 'default', $logo = null)
  {
    parent::__construct();
    $this->mustacheTemplate = $mustacheTemplate;
    $this->mustacheVars = $mustacheVars;
    $this->to = $toUser->email;
    $this->subject = $subject;
    $this->mailer = $mailer;
    $this->logo = $logo;
  }

  public function toMail($mailer = null)
  {
    //Gestisco la configurazione email
    if (!empty($mailer)) {
      $this->mailer = $mailer;
    }
    $mailer = new Mailer();
    $mailer->setProfile(['transport' => $this->mailer]);

    $m = new \Mustache_Engine(['entity_flags' => ENT_QUOTES]);
    $rendered_message = $m->render($this->mustacheTemplate, $this->mustacheVars);

    // valida sempre to prima di costruire il messaggio, per via dei namespace (il "root" namespace
    // non accessibile senza \ iniziale) l'errore interno non è intercettabile con catch e (log a parte)
    // non potrei memorizzare cosa è andato storto nell'invio della notifica
    if (empty($this->to) || !filter_var($this->to, FILTER_VALIDATE_EMAIL)) {
      throw new \Exception("L'email {$this->to} è vuota o non valida");
    }

    $attachmentData = $this->buildEmailAttachments();

    $viewVars = ['content' => $rendered_message];
    if ($attachmentData['footerLogoCid']) {
      $viewVars['footerLogoCid'] = $attachmentData['footerLogoCid'];
    }

    $mailer->setFrom($this->from)
      ->setTo($this->to)
      ->setBcc($this->from)
      ->setEmailFormat('html')
      ->setSubject($this->subject)
      ->viewBuilder()
      ->setTemplate('default')
      ->setVars($viewVars);

    if (!empty($attachmentData['attachments'])) {
      $mailer->setAttachments($attachmentData['attachments']);
    }

    return $mailer->deliver();
  }

  public function toEmailQueue($mailer = null)
  {
    //Gestisco la configurazione email
    if (!empty($mailer)) {
      $this->mailer = $mailer;
    }
    Log::write('debug', "definito il mailer $mailer");

    $m = new \Mustache_Engine(['entity_flags' => ENT_QUOTES]);
    $rendered_message = $m->render($this->mustacheTemplate, $this->mustacheVars);

    $data = ['content' => $rendered_message, 'id' => $this->mustacheVars['id'] ];

    $options = [
      'subject' => $this->subject,
      'layout' => 'default',
      'template' => 'mustache',
      'config' => $this->mailer,
      'send_at' => new FrozenTime('now'),
      'format' => 'html',
      'campaign_id' => $this->mustacheVars['id'],
    ];

    Log::write('debug', "preparate le options");

    if (is_array($this->from)){
      $options['from_name'] = array_key_first($this->from);
      $options['from_email'] = array_pop($this->from);
    } else {
      $options['from_email'] = $this->from;
      $options['from_name'] = $this->from;
    }

    Log::write('debug', "from name: ". $options['from_name']);
    Log::write('debug', "from email: " .  $options['from_email']);

    $attachmentData = $this->buildEmailAttachments();
    if (!empty($attachmentData['attachments'])) {
      $options['attachments'] = $attachmentData['attachments'];
    }
    if ($attachmentData['footerLogoCid']) {
      $data['footerLogoCid'] = $attachmentData['footerLogoCid'];
    }

    Log::write('debug', "inserito il logo");
    try {
      EmailQueue::enqueue($this->to, $data, $options);
    } catch (Exception $e) {
      $err = $e->getMessage();
      Log::write('debug', "errore nell'emissione di  {$err}");
    }
    Log::write('debug', "accodata la mail {$this->to}");
  }
}
