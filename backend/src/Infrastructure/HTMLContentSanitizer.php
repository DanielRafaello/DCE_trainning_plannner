<?php

declare(strict_types=1);

namespace App\Infrastructure;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class HTMLContentSanitizer
{
    // same limit as the todo_lists.content CHECK constraint (100 kB)
    public const int MAX_INPUT_LENGTH = 102_400;

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig())
            // default is 20 000 bytes – longer input would be silently truncated
            ->withMaxInputLength(self::MAX_INPUT_LENGTH)

            // base
            ->allowElement('p')
            ->allowElement('br')
            ->allowElement('blockquote')
            ->allowElement('pre')
            ->allowElement('span') // Quill uses span for size and colors

            // text format
            ->allowElement('strong')
            ->allowElement('b')
            ->allowElement('em')
            ->allowElement('i')
            ->allowElement('u')
            ->allowElement('s')

            // headings
            ->allowElement('h1')
            ->allowElement('h2')
            ->allowElement('h3')
            ->allowElement('h4')

            // lists
            ->allowElement('ul')
            ->allowElement('ol')
            ->allowElement('li')
            // Quill 2 list type and checklist state: <li data-list="bullet|ordered|checked|unchecked">
            ->allowAttribute('data-list', ['li'])
            // Quill 1 checklist: <ul data-checked="true|false">
            ->allowAttribute('data-checked', ['ul'])

            // ref
            ->allowElement('a', ['href', 'title', 'target'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->forceAttribute('a', 'rel', 'noopener noreferrer') // security standart

            // attributes
            ->allowAttribute('class', [
                'p', 'span', 'h1', 'h2', 'h3', 'h4',
                'li', 'blockquote', 'pre', 'strong', 'em', 'u', 's'
            ])

            ->allowAttribute('style', ['span', 'p', 'li'])

            // tables
            ->allowElement('table')
            ->allowElement('thead')
            ->allowElement('tbody')
            ->allowElement('tr')
            ->allowElement('th')
            ->allowElement('td')
            ->allowAttribute('colspan', ['td', 'th'])
            ->allowAttribute('rowspan', ['td', 'th']);

        $this->sanitizer = new HtmlSanitizer($config);
    }

    public function sanitize(string $html): string
    {
        return $this->sanitizer->sanitize($html);
    }
}
