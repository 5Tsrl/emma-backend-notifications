# Notifications Plugin per CakePHP4

## Installation
- Create a Notification folder in your src folder
- Add these keys to your configuration file (app.php / app_local.php / settings.php)

  'MailAdmin' => ['info@nomail.com' => 'DisplayName'],

## Usage
Every new notification requires 2 files:

### Notification Object
The notification objects implements baseNotification.php
Notification Objects live in the Notification folder

see example in uploadCompleteNotification.php

### Notificaion Template
This is the HTML template for the email, it can include variables

templates live in the folder
/templates/email/html/

and have the same name of the Notificaion Object, eg:
	
	file: /templates/email/html/upload_complete.php

	Gentile  <?= $user->first_name; ?>, 
	il revisore ha caricato la traduzione del documento 

	<b><?= $document->name; ?></b>

	Puoi scaricarla accedendo alla tua area riservata.
	Saluti, 
	Lo Staff di LanguageAid

### Invoking
In your **controller** you should add on top

	use App\Notification\uploadCompleteNotification;

then in the method where you need to send the notification, just call the NotificationObject
and pass the required variables

	public function save()
	{
		... your business logic 

		//Send a notification to the user
		$n = new uploadCompleteNotification($document, $toUser);
    	$n->toMail();
    }
    
   