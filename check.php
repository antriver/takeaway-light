<?php

use Antriver\EnergenieMihomeApi\MihomeApi;
use PhpImap\Exceptions\ConnectionException;
use PhpImap\Mailbox;
use Telegram\Bot\Api;

require_once __DIR__.'/vendor/autoload.php';

$config = json_decode(file_get_contents(__DIR__.'/config.json'));

if (file_exists(__DIR__.'/data.json')) {
    $data = json_decode(file_get_contents(__DIR__.'/data.json'), true);
} else {
    $data = [];
}

$now = new DateTime('now');
$since = new DateTime($data['lastChecked'] ?? '-1 YEAR');
$imapDateFormat = 'd-F-Y H:i:s P';

echo "Checking for new mail since {$since->format($imapDateFormat)}".PHP_EOL;

// Create PhpImap\Mailbox instance for all further actions
$mailbox = new Mailbox(
    $config->imap->path,
    $config->imap->username,
    $config->imap->password
);

try {
    // Get all emails
    // PHP.net imap_search criteria: http://php.net/manual/en/function.imap-search.php
    // These can't use single quotes because Gmail seems to have a bug with searches with single quotes.

    // This says "your food's coming", but we can't use the single quote.
    $justEatIds = $mailbox->searchMailbox(
        'SUBJECT "order confirmed" FROM "just-eat.co.uk" SINCE "'.$since->format($imapDateFormat).'"'
    );
    $deliverooIds = $mailbox->searchMailbox(
        'SUBJECT "in the kitchen" FROM "deliveroo" SINCE "'.$since->format($imapDateFormat).'"'
    );
    $uberEatsIds = $mailbox->searchMailbox(
        'SUBJECT "order with Uber Eats" FROM "uber.com" SINCE "'.$since->format($imapDateFormat).'"'
    );
    $papaJohnsIds = $mailbox->searchMailbox(
        'SUBJECT "is confirmed" FROM "papajohns" SINCE "'.$since->format($imapDateFormat).'"'
    );
    $dominosIds = $mailbox->searchMailbox(
        'SUBJECT "order confirmation" FROM "dominos" SINCE "'.$since->format($imapDateFormat).'"'
    );
    $pizzaHutIds = $mailbox->searchMailbox(
        'SUBJECT "Your order is in" SINCE "'.$since->format($imapDateFormat).'"'
    );

    $mailIds = array_merge(
        $justEatIds,
        $deliverooIds,
        $uberEatsIds,
        $papaJohnsIds,
        $dominosIds,
        $pizzaHutIds
    );
} catch (ConnectionException $ex) {
    die("IMAP connection failed: ".$ex);
}

$data['lastChecked'] = $now->format(DateTime::ATOM);
file_put_contents(__DIR__.'/data.json', json_encode($data));

if (count($mailIds) < 1) {
    exit();
}

var_dump($mailIds);

$mailStr = '';
foreach ($mailIds as $mailId) {
    $mail = $mailbox->getMail($mailId);
    $mailStr = "{$mail->id}\t{$mail->date}\t{$mail->fromAddress}\t{$mail->subject}".PHP_EOL;
    echo $mailStr;
}

$energenie = new MihomeApi($config->energenie->email, $config->energenie->password);
foreach ($config->energenie->subdeviceIds as $subdeviceId) {
    var_dump($energenie->powerOnSubdevice($subdeviceId));
}

$telegram = new Api($config->telegram->botToken);
foreach ($config->telegram->chatIds as $chatId) {
    $telegram->sendMessage(
        [
            'chat_id' => $chatId,
            'text' => "Light has been turned on because this email was found:\n{$mailStr}",
        ]
    );
}

passthru('node '.__DIR__.'/google-home-talker/speak.js');
