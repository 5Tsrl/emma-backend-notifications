Buongiorno <?= $user->first_name ?>

abbiamo ricevuto la tua richiesta di reset della password.

Per cambiare la password puoi fare click su questo link ed inserire una nuova password

<a href="<?= "{$referer}/users/change-password/{$user->id}?token={$token}" ?>">
  Cambia Password
</a>

Saluti, LanguageAid.