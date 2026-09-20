<?php
namespace FMGlobal\Repositories;
final class GmailMigration
{
    public static function apply(\mysqli $db): void
    {
        if (!$db->query("SHOW COLUMNS FROM gmail_tokens LIKE 'token_valido'")->num_rows) {
            $db->query('ALTER TABLE gmail_tokens ADD COLUMN token_valido TINYINT(1) NOT NULL DEFAULT 1');
        }
        if (!$db->query("SHOW COLUMNS FROM gmail_tokens LIKE 'activa'")->num_rows) {
            $db->query('ALTER TABLE gmail_tokens ADD COLUMN activa TINYINT(1) NOT NULL DEFAULT 1');
        }
    }
}