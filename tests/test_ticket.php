<?php
require __DIR__ . '/../app/Models/Ticket.php';
require __DIR__ . '/../app/Models/TicketMessage.php';
require __DIR__ . '/../app/Models/TicketAttachment.php';
require __DIR__ . '/../app/Models/TicketTag.php';

use App\Models\Ticket;
use App\Models\TicketAttachment;

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY AUTOINCREMENT, email TEXT)');
$pdo->exec("CREATE TABLE tickets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER DEFAULT NULL,
    order_id INTEGER DEFAULT NULL,
    subject TEXT NOT NULL,
    status TEXT DEFAULT 'open',
    priority TEXT DEFAULT 'normal',
    category TEXT DEFAULT 'support',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE ticket_messages (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    ticket_id INTEGER NOT NULL,
    user_id INTEGER DEFAULT NULL,
    sender_type TEXT NOT NULL,
    message TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE ticket_attachments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    message_id INTEGER NOT NULL,
    path TEXT NOT NULL,
    original_name TEXT NOT NULL,
    mime_type TEXT NOT NULL,
    size INTEGER NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)");
$pdo->exec("CREATE TABLE ticket_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    slug TEXT NOT NULL,
    color TEXT DEFAULT '#2563eb'
)");
$pdo->exec("CREATE TABLE ticket_tag_ticket (
    ticket_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL
)");

$pdo->exec("INSERT INTO users(email) VALUES('demo@example.com')");
$userId = (int) $pdo->lastInsertId();
$pdo->exec("INSERT INTO ticket_tags(name, slug, color) VALUES('Öncelikli','priority','#dc2626')");

$ticketId = Ticket::create($pdo, $userId, 'Ödeme sorunu', 'Siparişim düşmedi, yardımcı olur musunuz?', null, 'urgent', [
    ['path' => 'storage/uploads/tickets/example.txt', 'original_name' => 'example.txt', 'mime_type' => 'text/plain', 'size' => 20],
], 'support');
assert($ticketId === 1, 'Bilet oluşturulmalı');

Ticket::addMessage($pdo, $ticketId, null, 'admin', 'Talebiniz inceleniyor.', [
    ['path' => 'storage/uploads/tickets/admin.txt', 'original_name' => 'admin.txt', 'mime_type' => 'text/plain', 'size' => 10],
]);
Ticket::syncTags($pdo, $ticketId, [1]);
$ticket = Ticket::find($pdo, $ticketId, $userId);
assert($ticket !== null, 'Bilet bulunmalı');
assert(count($ticket['messages']) === 2, 'İki mesaj olmalı');
assert(count($ticket['messages'][0]['attachments']) === 1, 'İlk mesajın eki olmalı');
assert($ticket['status'] === 'waiting_customer', 'Durum waiting_customer olmalı');
assert($ticket['tags'][0]['name'] === 'Öncelikli', 'Etiket ilişkilendirilmeli');

$list = Ticket::listForUser($pdo, $userId);
assert(count($list) === 1, 'Liste tek bileti dönmeli');

$attachmentId = (int) $pdo->query('SELECT id FROM ticket_attachments LIMIT 1')->fetchColumn();
$pdo->exec("INSERT INTO users(email) VALUES('intruder@example.com')");
$intruderId = (int) $pdo->lastInsertId();
assert($attachmentId > 0, 'Ek kaydedilmiş olmalı');
assert(TicketAttachment::findForUser($pdo, $attachmentId, $intruderId) === null, 'Yetkisiz kullanıcı eki indirmemeli');
assert(TicketAttachment::findForUser($pdo, $attachmentId, $userId) !== null, 'Sahip eki indirebilmeli');

echo "ticket testleri başarılı\n";
