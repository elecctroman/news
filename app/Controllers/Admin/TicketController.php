<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Core\Csrf;
use App\Core\Validator;
use App\Models\Ticket;
use App\Models\TicketAttachment;
use App\Models\TicketMacro;
use App\Models\TicketTag;

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
        $status = $_GET['status'] ?? null;
        $allowedStatuses = ['open', 'waiting_admin', 'waiting_customer', 'dispute', 'resolved', 'closed'];
        if ($status !== null && !in_array($status, $allowedStatuses, true)) {
            $status = null;
        }
        $tickets = Ticket::all($this->pdo);
        if ($status) {
            $tickets = array_values(array_filter($tickets, static fn(array $ticket): bool => $ticket['status'] === $status));
        }
        $this->view->render('admin/tickets', [
            'title' => 'Destek Biletleri',
            'tickets' => $tickets,
            'csrf' => $this->csrf,
            'status' => $status,
        ], 'admin');
    }

    public function show(int $id): void
    {
        $ticket = Ticket::find($this->pdo, $id);
        if (!$ticket) {
            http_response_code(404);
            $this->view->render('admin/errors/404', ['title' => 'Bulunamadı'], 'admin');
            return;
        }

        $errors = [];
        $success = false;
        $macros = TicketMacro::allActive($this->pdo);
        $allTags = TicketTag::all($this->pdo);
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
                $validator = new Validator();
                $action = $_POST['action'] ?? 'reply';
                if ($action === 'status') {
                    $newStatus = $_POST['status'] ?? 'open';
                    Ticket::changeStatus($this->pdo, $ticket['id'], $newStatus);
                    $success = true;
                } elseif ($action === 'priority') {
                    $priority = $_POST['priority'] ?? 'normal';
                    Ticket::updatePriority($this->pdo, $ticket['id'], $priority);
                    $success = true;
                } elseif ($action === 'tags') {
                    $tagIds = isset($_POST['tags']) ? array_map('intval', (array) $_POST['tags']) : [];
                    Ticket::syncTags($this->pdo, $ticket['id'], $tagIds);
                    $success = true;
                } elseif ($action === 'category') {
                    $category = $_POST['category'] ?? 'support';
                    Ticket::updateCategory($this->pdo, $ticket['id'], $category);
                    $success = true;
                } else {
                    $message = trim($_POST['message'] ?? '');
                    if ($message === '' && isset($_POST['macro_id'])) {
                        $macroId = (int) $_POST['macro_id'];
                        $macro = TicketMacro::find($this->pdo, $macroId);
                        if ($macro) {
                            $message = $macro['body'];
                        }
                    }

                    if ($message === '' || !$validator->minLength($message, 3)) {
                        $errors[] = 'Yanıt en az 3 karakter olmalıdır.';
                    }

                    $attachments = $this->processUploads('attachments', 4, 4 * 1024 * 1024, $errors);

                    if (empty($errors)) {
                        $adminId = $this->auth?->user()['id'] ?? null;
                        Ticket::addMessage($this->pdo, $ticket['id'], $adminId ? (int) $adminId : null, 'admin', $message, $attachments);
                        $success = true;
                        $_POST['message'] = '';
                    }
                }
            }
            $ticket = Ticket::find($this->pdo, $id);
        }

        $this->view->render('admin/ticket-show', [
            'title' => '#' . $ticket['id'] . ' Destek Bileti',
            'ticket' => $ticket,
            'errors' => $errors,
            'success' => $success,
            'csrf' => $this->csrf,
            'macros' => $macros,
            'allTags' => $allTags,
            'categories' => $categories,
        ], 'admin');
    }

    public function downloadAttachment(int $id): void
    {
        $attachment = TicketAttachment::find($this->pdo, $id);
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
                $errors[] = 'Dosya boyutu sınırını aşıyor.';
                continue;
            }
            $extension = strtolower(pathinfo((string) $names[$i], PATHINFO_EXTENSION));
            if (!in_array($extension, $allowedExtensions, true)) {
                $errors[] = 'İzin verilmeyen dosya uzantısı.';
                continue;
            }
            if (preg_match('/php|phar|phtml|exe|js/i', $extension)) {
                $errors[] = 'Güvenlik nedeniyle dosya reddedildi.';
                continue;
            }
            $mimeType = $finfo->file($tmpNames[$i]) ?: 'application/octet-stream';
            if (!in_array($mimeType, $allowedMime, true)) {
                $errors[] = 'Dosya türü doğrulanamadı.';
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
