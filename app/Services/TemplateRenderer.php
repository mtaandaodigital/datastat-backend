<?php

namespace App\Services;

use App\Models\EmailTemplate;
use App\Enums\EmailTemplateType;
use Carbon\Carbon;
use TijsVerkoyen\CssToInlineStyles\CssToInlineStyles;

class TemplateRenderer
{
    /**
     * Render a template with registrant data.
     */
    public static function render(EmailTemplateType $type, array $data): ?array
    {
        $template = EmailTemplate::getByType($type);

        if (!$template) {
            return null;
        }

    $subject = self::replacePlaceholders($template->subject, $data);
    $body = self::replacePlaceholders($template->body_html, $data);
    $body = self::inlineCss($body);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Render from an EmailTemplate record (subject/body) by replacing placeholders.
     */
    public static function renderFromRecord(\App\Models\EmailTemplate $template, array $data): array
    {
        $subject = self::replacePlaceholders($template->subject, $data);
        $body = self::replacePlaceholders($template->body_html, $data);
        $body = self::inlineCss($body);

        return [
            'subject' => $subject,
            'body' => $body,
        ];
    }

    /**
     * Replace placeholders in template text.
     * Supported placeholders:
     * {{participant_name}}, {{training_date}}, {{training_location}}, {{start_date}}, {{end_date}}, {{course_title}}, {{coordinator_name}}
     */
    private static function replacePlaceholders(string $text, array $data): string
    {
        $placeholders = [
            '{{participant_name}}' => $data['participant_name'] ?? '[Participant Name]',
            '{{training_date}}' => $data['training_date'] ?? '[Training Date]',
            '{{training_location}}' => $data['training_location'] ?? '[Location]',
            '{{start_date}}' => $data['start_date'] ?? '[Start Date]',
            '{{end_date}}' => $data['end_date'] ?? '[End Date]',
            '{{course_title}}' => $data['course_title'] ?? '[Course Title]',
            '{{coordinator_name}}' => $data['coordinator_name'] ?? 'Sammy Gathuru',
        ];

        return str_replace(
            array_keys($placeholders),
            array_values($placeholders),
            $text
        );
    }

    /**
     * Inline CSS styles into HTML so email clients render more consistently.
     */
    private static function inlineCss(string $html): string
    {
        try {
            $inliner = new CssToInlineStyles();
            // Passing empty CSS allows the library to extract <style> tags in the HTML
            return $inliner->convert($html, '');
        } catch (\Throwable $e) {
            // On any failure, return the original HTML so we don't break sending
            return $html;
        }
    }
}
