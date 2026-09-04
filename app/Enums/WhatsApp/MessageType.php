<?php

namespace App\Enums\WhatsApp;

enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case Audio = 'audio';
    case Video = 'video';
    case Document = 'document';
    case Sticker = 'sticker';
    case Location = 'location';
    case Contacts = 'contacts';
    case Interactive = 'interactive';
    case Template = 'template';
    case Button = 'button';
    case List = 'list';
    case Reaction = 'reaction';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Text => 'Text',
            self::Image => 'Image',
            self::Audio => 'Audio',
            self::Video => 'Video',
            self::Document => 'Document',
            self::Sticker => 'Sticker',
            self::Location => 'Location',
            self::Contacts => 'Contacts',
            self::Interactive => 'Interactive',
            self::Template => 'Template',
            self::Button => 'Button',
            self::List => 'List',
            self::Reaction => 'Reaction',
            self::System => 'System',
        };
    }
}
