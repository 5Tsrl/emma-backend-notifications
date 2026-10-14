<?php

declare(strict_types=1);

namespace Notifications\Notification;

use Cake\Core\Configure;
use Cake\Mailer\Mailer;
use Cake\Routing\Asset;
use Cake\Utility\Inflector;
use Composer\Config;
use Notifications\Model\Table\NotificationsTable;

class baseNotification
{
  protected $template;
  protected $subject;
  protected $from;
  protected $to;
  protected $cc;
  protected $mailer;
  protected $logo;
  protected $vars = [];
  protected $plugin = null;

  public function __construct($logo = null)
  {
    $className = (new \ReflectionClass($this))->getShortName();
    $theme = Configure::read('theme');
    if (!empty($theme)) {
      $theme .= '.';
      $this->plugin = null;
    };
    $className =  strstr($className, 'Notification', true);
    $className = Inflector::underscore($className);
    $this->template = "$theme/email/html/$className";
    $this->subject = 'Notifica';
    $this->from = Configure::read('MailAdmin');
    $this->to = Configure::read('MailAdmin');
    // $this->cc = Configure::read('MailAdmin');
    $this->mailer = 'default';
    $this->logo = $logo;
  }

  public function setFrom($email, $name = null)
  {
    if (!empty($name)) {
      $this->from = [$email = $name];
    } else {
      $this->from = $email;
    }
  }


  /**
   * Protected helper to build header and footer attachments.
   * Returns an array of attachments ready to pass to setAttachments().
   * Also returns the footer CID if available.
   *
   * @return array ['attachments' => [...], 'footerLogoCid' => null|'footer-logo']
   */
  protected function buildEmailAttachments()
  {
    $attachments = [];
    $footerLogoCid = null;

    // Header logo
    $headerFile = null;
    if (!empty($this->logo)) {
      $headerFile = WWW_ROOT . $this->logo;
    } elseif (Configure::read('MailLogo')) {
      $headerFile = WWW_ROOT . Configure::read('MailLogo');
    }

    if ($headerFile && file_exists($headerFile)) {
      $attachments['logo.png'] = [
        'file' => $headerFile,
        'mimetype' => 'image/png',
        'contentId' => '12345',
      ];
    }

    // Footer logo (only when flag is on and file exists)
    if (Configure::read('MailFooterInlineLogo')) {
      $footerPath = Configure::read('VUE_APP_FOOTER');
      if ($footerPath) {
        $footerFile = WWW_ROOT . ltrim($footerPath, '/');
        if (file_exists($footerFile)) {
          $ext = pathinfo($footerFile, PATHINFO_EXTENSION);
          $mimeType = mime_content_type($footerFile);
          $attachments['footer-logo.' . $ext] = [
            'file' => $footerFile,
            'mimetype' => $mimeType,
            'contentId' => 'footer-logo',
          ];
          $footerLogoCid = 'footer-logo';
        }
      }
    }

    return [
      'attachments' => $attachments,
      'footerLogoCid' => $footerLogoCid,
    ];
  }

  public function toMail($mailer  = null)
  {
    //Gestisco la configurazione email
    if (!empty($mailer)) {
      $this->mailer = $mailer;
    }

    $mailer = new Mailer($this->mailer);

    // Gli allegati (e quindi il cid del logo footer) vanno determinati PRIMA di setVars():
    // setVars() copia l'array per valore, un `$this->vars[...]` aggiunto dopo non arriva al template.
    $attachmentData = $this->buildEmailAttachments();
    if ($attachmentData['footerLogoCid']) {
      $this->vars['footerLogoCid'] = $attachmentData['footerLogoCid'];
    }

    if ($this->from) {
      $mailer->setFrom($this->from)
        ->setTo($this->to)
        ->setEmailFormat('html')
        ->setSubject($this->subject)
        ->viewBuilder()
        ->setTemplate($this->template)
        ->setVars($this->vars);

        if ($this->plugin !== null) {
          $mailer->viewBuilder()->setPlugin($this->plugin);
        }

      if (!empty($this->cc)) {
        $mailer->setCC($this->cc);
      }

      if (!empty($attachmentData['attachments'])) {
        $mailer->setAttachments($attachmentData['attachments']);
      }

      $mailer->deliver();
    }
  }

  /**
   * Write notification to database.
   *
   * @return void
   */
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
