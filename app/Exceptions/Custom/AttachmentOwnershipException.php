<?php

namespace App\Exceptions\Custom;

use Exception;

/**
 * 添付ファイル所有権違反例外
 */
class AttachmentOwnershipException extends Exception
{
    public function __construct(
        public readonly int $userId,
        public readonly int $attachmentId,
        public readonly ?int $attachmentOwnerId = null,
        string $message = "添付ファイルへのアクセス権限がありません。",
        int $code = 403
    ) {
        parent::__construct($message, $code);
    }

    public function getUserMessage(): string
    {
        return "この添付ファイルを操作する権限がありません。";
    }

    public function getContext(): array
    {
        return [
            'user_id' => $this->userId,
            'attachment_id' => $this->attachmentId,
            'attachment_owner_id' => $this->attachmentOwnerId,
        ];
    }
}