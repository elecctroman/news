<?php
namespace App\Controllers\Front;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Validator;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\TicketAttachment;

class TicketController extends Controller
{
    private Csrf $csrf;

    public function __construct()
    {
        parent::__construct();
        $this->csrf = $GLOBALS['csrf'] ?? new Csrf();
    }

    public function index(): void
    {
        $user = $this->auth?->user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $tickets = Ticket::listForUser($this->pdo, (int) $user['id']);
        $this->view->render('front/tickets/index', [
            'title' => 'Destek Taleplerim',
            'tickets' => $tickets,
            'csrf' => $this->csrf,
        ]);
    }

    public function create(): void
    {
        $user = $this->auth?->user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $errors = [];
        $success = false;
        $orders = Order::listForUser($this->pdo, (int) $user['id']);
        $categories = [
            'support' => 'Genel Destek',
            'order' => 'Sipariş Problemi',
            'payment' => 'Ödeme Sorunu',
            'dispute' => 'Uyuşmazlık / İtiraz',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrf->validateToken($_POST['_csrf'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik anahtarı.';
            } else {
                $subject = trim($_POST['subject'] ?? '');
                $message = trim($_POST['message'] ?? '');
                $orderId = isset($_POST['order_id']) && $_POST['order_id'] !== '' ? (int) $_POST['order_id'] : null;
                $priority = $_POST['priority'] ?? 'normal';
                $category = $_POST['category'] ?? 'support';

                $validator = new Validator();
                if ($subject === '' || !$validator->minLength($subject, 4)) {
                    $errors[] = 'Konu en az 4 karakter olmalıdır.';
                }
                if ($message === '' || !$validator->minLength($message, 10)) {
                    $errors[] = 'Mesaj en az 10 karakter olmalıdır.';
                }

                $attachments = $this->processUploads('attachments', 3, 2 * 1024 * 1024, $errors);

                if (empty($errors)) {
                    Ticket::create($this->pdo, (int) $user['id'], $subject, $message, $orderId, $priority, $attachments, $category);
                    $success = true;
                    $_POST = [];
                }
            }
        }

        $this->view->render('front/tickets/create', [
            'title' => 'Yeni Destek Bileti',
            'errors' => $errors,
            'success' => $success,
            'orders' => $orders,
            'categories' => $categories,
            'csrf' => $this->csrf,
        ]);
    }

    public function show(int $id): void
    {
        $user = $this->auth?->user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $ticket = Ticket::find($this->pdo, $id, (int) $user['id']);
        if (!$ticket) {
            http_response_code(404);
            $this->view->render('front/errors/404', ['title' => 'Bulunamadı']);
            return;
        }

        $errors = [];
        $success = false;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!$this->csrf->validateToken($_POST['_csrf'] ?? '')) {
                $errors[] = 'Geçersiz güvenlik anahtarı.';
            } elseif (isset($_POST['action']) && $_POST['action'] === 'close') {
                Ticket::changeStatus($this->pdo, $ticket['id'], 'closed');
                $ticket = Ticket::find($this->pdo, $id, (int) $user['id']);
                $success = true;
            } else {
                $message = trim($_POST['message'] ?? '');
                $validator = new Validator();
                if ($message === '' || !$validator->minLength($message, 3)) {
                    $errors[] = 'Lütfen mesajınızı detaylandırın.';
                }

                $attachments = $this->processUploads('attachments', 2, 2 * 1024 * 1024, $errors);

                if (empty($errors)) {
                    if ($ticket['status'] === 'closed') {
                        Ticket::reopen($this->pdo, $ticket['id']);
                    }
                    Ticket::addMessage($this->pdo, $ticket['id'], (int) $user['id'], 'customer', $message, $attachments);
                    $ticket = Ticket::find($this->pdo, $id, (int) $user['id']);
                    $success = true;
                    $_POST['message'] = '';
                }
            }
        }

        $this->view->render('front/tickets/show', [
            'title' => '#' . $ticket['id'] . ' Destek Bileti',
            'ticket' => $ticket,
            'errors' => $errors,
            'success' => $success,
            'csrf' => $this->csrf,
        ]);
    }

    public function downloadAttachment(int $id): void
    {
        $user = $this->auth?->user();
        if (!$user) {
            header('Location: /login');
            exit;
        }
        $attachment = TicketAttachment::findForUser($this->pdo, $id, (int) $user['id']);
        if (!$attachment) {
            http_response_code(404);
            echo 'Dosya bulunamadı.';
            return;
        }

        $rootPath = realpath(dirname(__DIR__, 3));
        $storagePath = $rootPath ? realpath($rootPath . '/storage/uploads/tickets') : false;
        $relativePath = ltrim((string) $attachment['path'], '/');
        $absolutePath = $rootPath ? realpath($rootPath . '/' . $relativePath) : false;

        if (!$rootPath || !$storagePath || !$absolutePath) {
            http_response_code(404);
            echo 'Dosya erişilemiyor.';
            return;
        }

        $storagePath = rtrim($storagePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strncmp($absolutePath, $storagePath, strlen($storagePath)) !== 0 || !is_file($absolutePath) || !is_readable($absolutePath)) {
            http_response_code(404);
            echo 'Dosya erişilemiyor.';
            return;
        }

        header('Content-Type: ' . $attachment['mime_type']);
        header('Content-Length: ' . (string) $attachment['size']);
        header('Content-Disposition: attachment; filename="' . basename($attachment['original_name']) . '"');
        readfile($absolutePath);
        exit;
    }

    /**
     * @return array<int, array{path:string,original_name:string,mime_type:string,size:int}>
     */
    private function processUploads(string $field, int $maxFiles, int $maxSize, array &$errors): array
    {
        if (empty($_FILES[$field]) || empty($_FILES[$field]['name'])) {
            return [];
        }
        $files = $_FILES[$field];
        $names = (array) $files['name'];
        $tmpNames = (array) $files['tmp_name'];
        $sizes = (array) $files['size'];
        $errorsList = (array) $files['error'];
        $attachments = [];
        $allowedExtensions = ['png', 'jpg', 'jpeg', 'pdf', 'txt', 'zip'];
        $allowedMime = ['image/png', 'image/jpeg', 'application/pdf', 'text/plain', 'application/zip'];

        $total = min(count($names), $maxFiles);
        $targetDir = dirname(__DIR__, 3) . '/storage/uploads/tickets';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0770, true);
        }
        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        for ($i = 0; $i < $total; $i++) {
            if (($errorsList[$i] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            if (($errorsList[$i] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $errors[] = 'Ek dosya yüklenemedi.';
                continue;
            }
            if (($sizes[$i] ?? 0) > $maxSize) {
                $errors[] = 'Ek dosya boyutu sınırı aşıldı.';
                continue;
            }
            $extension = strtolower(pathinfo((string) $names[$i], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'İzin verilmeyen dosya uzantısı.';
                continue;
            }
            $mimeType = $finfo->file($tmpNames[$i]) ?: 'application/octet-stream';
            if (!in_array($mimeType, $allowedMime, true)) {
                $errors[] = 'Dosya türü doğrulanamadı.';
                continue;
            }
            if (preg_match('/php|phar|phtml|exe|js/i', $extension)) {
                $errors[] = 'Güvenlik nedeniyle dosya reddedildi.';
                continue;
            }

            $basename = bin2hex(random_bytes(16)) . '.' . $extension;
            $path = $targetDir . '/' . $basename;
            if (!move_uploaded_file($tmpNames[$i], $path)) {
                $errors[] = 'Dosya kaydedilemedi.';
                continue;
            }

            $attachments[] = [
                'path' => 'storage/uploads/tickets/' . $basename,
                'original_name' => (string) $names[$i],
                'mime_type' => $mimeType,
                'size' => (int) $sizes[$i],
            ];
        }

        return $attachments;
    }
}
