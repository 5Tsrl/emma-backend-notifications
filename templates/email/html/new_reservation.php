<?php

use Cake\Routing\Router;
?>

Gentile <?= $user->first_name; ?>,
abbiamo ricevuto la tua richiesta di prenotazione.


Ti chiediamo di verificare che sia tutto corretto, o altrimenti di segnalarci subito eventuali errori.


Ecco i dettagli della tua richiesta:
Nome: <b><?= $user->first_name ?></b>
Cognome: <b><?= $user->last_name ?></b>
Origine: <b><?= $dt->origin->address ?>, <?= $dt->origin->city ?></b>

Fai click sul link che segue per confermare la tua richiesta.

<center>
  <a href="<?= "{$referer}/reservations/confirm/{$dt->id}?token={$token}" ?>" class="btn btn-primary">Conferma</a>
</center>

<b>Attenzione</b>
a) Appena confermi, la tua richiesta sarà presa in carico e sarai avvisato subito, per dirti se siamo in grado di accettarla o meno.
b) <b>Se non confermi la tua richiesta sarà ignorata.</b>


Saluti,
Lo Staff di COMPANY.