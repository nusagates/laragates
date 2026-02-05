<?php

namespace App\Support;

use App\Models\Template;

class TemplateLog
{
    /**
     * Build standardized meta payload for template-related system logs
     *
     * @param  Template $template
     * @param  string   $action       create|update|delete|approve|reject|sync|send|view|submit
     * @param  string   $description  Human readable description
     * @param  array    $extra        Additional context (risk, reason, provider, etc)
     * @return array
     */
    public static function meta(
        Template $template,
        string $action,
        string $description,
        array $extra = []
    ): array {
        return array_merge([
            // machine readable
            'action' => $action,
            'object' => 'template',

            // entity snapshot
            'template' => [
                'id'       => $template->id,
                'name'     => $template->name,
                'language' => $template->language,
                'category' => $template->category,
                'status'   => $template->status,
                'version'  => $template->version ?? null,
            ],

            // human readable (for UI / audit)
            'description' => $description,

            // context default
            'context' => [
                'source' => 'templates_module',
                'actor'  => auth()->user()->role ?? 'system',
            ],
        ], $extra);
    }

    /**
     * Shortcut for high-risk actions (delete, revoke, force update)
     */
    public static function highRisk(
        Template $template,
        string $action,
        string $description,
        array $extra = []
    ): array {
        return self::meta(
            $template,
            $action,
            $description,
            array_merge([
                'risk' => 'high',
                'alert' => true,
            ], $extra)
        );
    }

    /**
     * Shortcut for approval-related actions
     */
    public static function approval(
        Template $template,
        string $action,
        string $description,
        array $extra = []
    ): array {
        return self::meta(
            $template,
            $action,
            $description,
            array_merge([
                'approval' => [
                    'by' => auth()->user()->id ?? null,
                    'at' => now()->toDateTimeString(),
                ],
            ], $extra)
        );
    }

    /**
     * Shortcut for external sync actions (Meta, provider, etc)
     */
    public static function sync(
        Template $template,
        string $provider,
        string $description,
        array $extra = []
    ): array {
        return self::meta(
            $template,
            'sync',
            $description,
            array_merge([
                'provider' => $provider,
            ], $extra)
        );
    }
}
