<?php

declare(strict_types=1);

namespace App\Content;

use App\Core\Locale;

/**
 * Homepage-specific layout surgery, applied to the legacy WPBakery markup
 * after ContentExtractor has pulled #main out of the exported HTML.
 *
 * Every transform here is keyed off the markup it needs rather than off the
 * request path, matching how the rest of the extractor works: the homepage
 * layout is also reused verbatim by a couple of other exported pages and by
 * the Chinese mirror, and they should all get the same treatment.
 *
 * These transforms only ever move, wrap or re-parent existing nodes. No
 * transform rewrites copy: headings, paragraphs, category names, counts and
 * link targets are carried across as the original DOM nodes, and the two
 * places a sentence is split in half (the "if YES" answer and the "what's
 * inside" lead-in) split on the punctuation the sentence already has. The
 * only strings this class authors are page furniture that never existed in
 * the source - the two small eyebrow labels, the placeholder caption and
 * the "N guides" phrasing - and those are held in COPY below, in both
 * languages, the way PostLayout holds its own.
 *
 * The page's own headline, its opening paragraph and the eight-category
 * strip are handed back to the caller rather than left in the body, so the
 * shared header can paint them into the hero band - the same channel
 * PostLayout uses for an article's title.
 */
final class HomepageLayout
{
    /**
     * Literal Revolution Slider shortcodes left in the exported HTML. The
     * source page stores the alias in curly quotes ([rev_slider alias=”…”]),
     * which WordPress' shortcode regex never matched, so do_shortcode() left
     * the raw text in place and the export captured it verbatim. There is no
     * WordPress, no plugin and no slider definition in this tree, so the
     * token is stripped rather than expanded.
     */
    private const SHORTCODE_PATTERN = '/\[rev_slider\b[^\]]*\]/u';

    /** Page furniture authored here, in the language of the tree. */
    private const COPY = [
        Locale::ENGLISH => [
            'short_answer' => 'The short answer',
            'whats_inside' => "What's inside",
            'media_label' => 'Badminton Basics',
            'guides_one' => '%d guide',
            'guides_many' => '%d guides',
            'strip_label' => 'Category quick navigation',
        ],
        Locale::CHINESE => [
            'short_answer' => '简短回答',
            'whats_inside' => '内容一览',
            'media_label' => '羽毛球基础',
            'guides_one' => '%d 篇教程',
            'guides_many' => '%d 篇教程',
            'strip_label' => '分类快速导航',
        ],
    ];

    /**
     * A paragraph that is really a sub-heading: short, and all of its text
     * sits inside a single <strong>/<b>. The homepage marks "What's the
     * secret?" that way, and nothing else in the intro is shaped like it.
     */
    private const HEADING_MAX_LENGTH = 90;

    /** Where the two split sentences are cut, in the order they are tried. */
    private const CLAUSE_DELIMITERS = [',', '，', '、'];
    private const SENTENCE_DELIMITERS = ['. ', '。', '! ', '！'];

    /** The id given to the category directory so the hero can jump to it. */
    private const DIRECTORY_ID = 'all-categories';

    /**
     * @return array{layout: string, hero: array<string, mixed>}|null
     *         null when the page is not built from the homepage layout, in
     *         which case nothing beyond the shortcode strip was touched.
     */
    public function apply(\DOMDocument $dom, \DOMXPath $xpath, string $lang = Locale::ENGLISH): ?array
    {
        // Safe everywhere: the token is literal text wherever it appears.
        $this->removeSliderShortcode($xpath);

        if (!$this->isHomepageLayout($xpath)) {
            return null;
        }

        $copy = self::COPY[$lang] ?? self::COPY[Locale::ENGLISH];

        $this->frameCategoryDirectory($dom, $xpath);

        $sections = $this->identifyCategorySections($xpath);

        $strip = $this->collectStrip($xpath, $sections, $copy);
        $this->dressCategoryCards($dom, $xpath, $sections, $copy);
        $hero = $this->reflowIntro($dom, $xpath, $copy);
        $this->splitDisplayHeadings($xpath);

        if ($hero === null) {
            return null;
        }

        $hero['variant'] = 'home';
        $hero['strip'] = $strip;
        $hero['strip_label'] = $copy['strip_label'];
        $hero['jump'] = '#' . self::DIRECTORY_ID;

        return ['layout' => 'home', 'hero' => $hero];
    }

    /**
     * Wrap the "All Categories" heading's words in two spans so the
     * stylesheet can render the last word in ink against the rest in grey.
     *
     * Only the markup changes: the words, their order and the space between
     * them are the heading's own, so the rendered text is byte-for-byte what
     * it was. Headings that already contain markup (a link, an emphasis) are
     * left alone rather than flattened.
     *
     * The page's own headline gets the same treatment in the hero band,
     * from the lead/key pair reflowIntro() hands up; the section headings
     * inside the panels are set in one tone, so they are left out of this.
     */
    private function splitDisplayHeadings(\DOMXPath $xpath): void
    {
        $headings = $xpath->query(
            '//*[contains(concat(" ", normalize-space(@class), " "), " cat-section-title ")]',
        );

        foreach (iterator_to_array($headings) as $heading) {
            if (!$heading instanceof \DOMElement) {
                continue;
            }

            // A single text child is the only shape that is safe to re-wrap.
            if ($heading->childNodes->length !== 1 || !$heading->firstChild instanceof \DOMText) {
                continue;
            }

            $text = $heading->firstChild->nodeValue ?? '';
            $words = preg_split('/\s+/u', trim($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];

            if (count($words) < 2) {
                continue;
            }

            $key = array_pop($words);
            $dom = $heading->ownerDocument;

            $heading->removeChild($heading->firstChild);

            $lead = $dom->createElement('span');
            $lead->setAttribute('class', 'v2-head-lead');
            $lead->textContent = implode(' ', $words);
            $heading->appendChild($lead);

            $heading->appendChild($dom->createTextNode(' '));

            $keySpan = $dom->createElement('span');
            $keySpan->setAttribute('class', 'v2-head-key');
            $keySpan->textContent = $key;
            $heading->appendChild($keySpan);
        }
    }

    /**
     * The eight-icon strip (ul#thct) only exists on pages built from the
     * homepage layout, which makes it a reliable marker for the rest of the
     * transforms. It matters: the WPBakery text column that the reflow
     * targets also appears on well over a thousand article pages, and those
     * pages are out of scope here.
     */
    private function isHomepageLayout(\DOMXPath $xpath): bool
    {
        return $xpath->query('//ul[@id="thct"]')->length > 0;
    }

    /**
     * Drop the unprocessed [rev_slider …] token so it stops rendering as
     * literal text. Only the shortcode itself is removed; any surrounding
     * text in the same node is preserved.
     */
    private function removeSliderShortcode(\DOMXPath $xpath): void
    {
        foreach ($xpath->query('//text()[contains(., "[rev_slider")]') as $text) {
            $replaced = preg_replace(self::SHORTCODE_PATTERN, '', $text->nodeValue ?? '');

            if ($replaced !== null) {
                $text->nodeValue = $replaced;
            }
        }
    }

    /**
     * Wrap the "All Categories" heading, subtitle and controls in their own
     * header band so the block reads as a directory/browse panel rather than
     * a second copy of the icon strip. The cards themselves are untouched.
     */
    private function frameCategoryDirectory(\DOMDocument $dom, \DOMXPath $xpath): void
    {
        $section = $this->firstByClass($xpath, 'cat-section');

        if ($section === null || $this->hasClass($section, 'cat-directory')) {
            return;
        }

        $section->setAttribute('class', $section->getAttribute('class') . ' cat-directory');

        // The hero's primary call to action jumps here.
        if ($section->getAttribute('id') === '') {
            $section->setAttribute('id', self::DIRECTORY_ID);
        }

        $head = $dom->createElement('div');
        $head->setAttribute('class', 'cat-directory-head');
        $section->insertBefore($head, $section->firstChild);

        foreach (['cat-section-title', 'cat-section-subtitle', 'cat-section-controls'] as $class) {
            $node = $this->firstByClass($xpath, $class, $section);

            if ($node !== null) {
                $head->appendChild($node);
            }
        }
    }

    /**
     * Give every category card a stable id so the hero strip has something
     * to jump to, and hand back the ids with each card's guide count, in
     * document order.
     *
     * @return list<array{id: string, count: int}>
     */
    private function identifyCategorySections(\DOMXPath $xpath): array
    {
        $sections = [];
        $used = [];

        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " cat-accordion ")]') as $index => $card) {
            if (!$card instanceof \DOMElement) {
                continue;
            }

            $title = $this->firstByClass($xpath, 'cat-accordion-title', $card);
            $slug = $this->slug($title?->textContent ?? '');

            if ($slug === '') {
                $slug = 'section-' . ($index + 1);
            }

            $id = 'category-' . $slug;
            $suffix = 2;

            while (isset($used[$id])) {
                $id = 'category-' . $slug . '-' . $suffix++;
            }

            $used[$id] = true;
            $card->setAttribute('id', $id);

            $sections[] = [
                'id' => $id,
                'count' => $xpath->query('.//li', $card)->length,
            ];
        }

        return $sections;
    }

    /**
     * Read the eight-category strip (ul#thct) and hand its labels, counts
     * and jump targets to the caller for the hero band, then drop the
     * WPBakery row that carried it out of the body.
     *
     * Each <li> holds two links to the same target - one around the label,
     * one around the icon - so the label is read off the one without an
     * image inside it, the way CategoryDirectory reads the same strip. The
     * artwork is not carried across: the hero strip is set in type.
     *
     * @param list<array{id: string, count: int}> $sections jump targets, in strip order
     * @param array<string, string> $copy
     *
     * @return list<array{label: string, count: string, href: string}>
     */
    private function collectStrip(\DOMXPath $xpath, array $sections, array $copy): array
    {
        $list = $xpath->query('//ul[@id="thct"]')->item(0);

        if (!$list instanceof \DOMElement) {
            return [];
        }

        $strip = [];

        foreach ($xpath->query('./li', $list) as $index => $item) {
            if (!$item instanceof \DOMElement) {
                continue;
            }

            $labelled = $xpath->query('.//a[not(.//img)]', $item)->item(0);
            $anchor = $labelled instanceof \DOMElement ? $labelled : $xpath->query('.//a[@href]', $item)->item(0);

            if (!$anchor instanceof \DOMElement) {
                continue;
            }

            $label = trim((string) preg_replace('/\s+/u', ' ', $anchor->textContent));

            if ($label === '') {
                continue;
            }

            // Prefer the in-page card; fall back to the original link target
            // when this page has no matching category card.
            $strip[] = [
                'label' => $label,
                'count' => $this->plural($copy, $sections[$index]['count'] ?? 0),
                'href' => isset($sections[$index]) ? '#' . $sections[$index]['id'] : $anchor->getAttribute('href'),
            ];
        }

        // Remove the whole WPBakery row the strip sat in: the strip is part
        // of the hero band now, and the empty row would otherwise leave a
        // gap at the top of the white content panel.
        $row = $xpath->query(
            'ancestor::div[contains(concat(" ", normalize-space(@class), " "), " vc_row ")][1]',
            $list,
        )->item(0);

        $node = $row instanceof \DOMElement ? $row : $list;
        $node->parentNode?->removeChild($node);

        return $strip;
    }

    /**
     * Re-dress the category cards as the picture-card grid the design asks
     * for: the category name moves inside a tile of its own, the guide
     * count follows the tile in the language of the tree, and the chevron
     * goes - the open/closed marker is drawn by the stylesheet.
     *
     * Both the tile and the count stay inside the <summary>. A closed
     * <details> renders nothing but its summary, so a count placed after it
     * - which is where it reads in the markup - would vanish for exactly
     * the cards that are not open yet.
     *
     * @param list<array{id: string, count: int}> $sections
     * @param array<string, string> $copy
     */
    private function dressCategoryCards(\DOMDocument $dom, \DOMXPath $xpath, array $sections, array $copy): void
    {
        foreach ($xpath->query('//*[contains(concat(" ", normalize-space(@class), " "), " cat-accordion ")]') as $index => $card) {
            if (!$card instanceof \DOMElement) {
                continue;
            }

            $summary = $xpath->query('./summary', $card)->item(0);

            if (!$summary instanceof \DOMElement) {
                continue;
            }

            $title = $this->firstByClass($xpath, 'cat-accordion-title', $card);
            $meta = $this->firstByClass($xpath, 'cat-accordion-meta', $card);
            $badge = $this->firstByClass($xpath, 'cat-accordion-count', $card);
            $count = $sections[$index]['count'] ?? 0;

            if ($badge === null) {
                $badge = $dom->createElement('span');
                $badge->setAttribute('class', 'cat-accordion-count');
            }

            $badge->textContent = $this->plural($copy, $count);
            $badge->parentNode?->removeChild($badge);

            // The tile is the artwork the name sits on; the count sits under
            // it, outside the tile's rounded corners.
            $tile = $dom->createElement('span');
            $tile->setAttribute('class', 'cat-accordion-tile');
            $summary->insertBefore($tile, $summary->firstChild);

            if ($title !== null) {
                $tile->appendChild($title);
            }

            $summary->appendChild($badge);

            // The chevron lived inside the meta wrapper beside the count;
            // both are now redundant.
            $meta?->parentNode?->removeChild($meta);
        }
    }

    /**
     * Reflow the homepage intro into the sections the design is built from,
     * and hand the hero its copy.
     *
     * The split is positional - the paragraphs are taken in the order the
     * page already has them - and every step is guarded, so an export that
     * does not have this shape falls back to the plainer two-column reflow
     * rather than being rearranged into something it is not.
     *
     *   heading          -> the hero's headline
     *   first paragraph  -> the hero's blurb
     *   second           -> the "if YES" answer callout
     *   up to the        -> two-column prose
     *   sub-heading      -> the "what's the secret?" section, with the
     *   and past it         paragraphs that follow it as its body
     *   last paragraph   -> the "what's inside" heading above the card grid
     *   the list         -> the numbered card grid
     *
     * @param array<string, string> $copy
     *
     * @return array{lead: string, key: string, blurb: string}|null
     */
    private function reflowIntro(\DOMDocument $dom, \DOMXPath $xpath, array $copy): ?array
    {
        $wrapper = $xpath->query('//div[contains(concat(" ", normalize-space(@class), " "), " wpb_text_column ")]//div[contains(concat(" ", normalize-space(@class), " "), " wpb_wrapper ")][h2][ul]')->item(0);

        if (!$wrapper instanceof \DOMElement) {
            return null;
        }

        $column = $xpath->query(
            'ancestor::div[contains(concat(" ", normalize-space(@class), " "), " wpb_text_column ")][1]',
            $wrapper,
        )->item(0);

        if ($column instanceof \DOMElement) {
            $column->setAttribute('class', $column->getAttribute('class') . ' home-intro');
        }

        $heading = $xpath->query('./h2', $wrapper)->item(0);
        $list = $xpath->query('./ul', $wrapper)->item(0);
        $paragraphs = array_values(array_filter(
            iterator_to_array($xpath->query('./p', $wrapper)),
            static fn (\DOMNode $node): bool => $node instanceof \DOMElement,
        ));

        if (!$heading instanceof \DOMElement || !$list instanceof \DOMElement || count($paragraphs) < 4) {
            $this->plainReflow($dom, $wrapper, $paragraphs, $list);

            return null;
        }

        // The last paragraph before the list introduces it, wherever it sits.
        $insideHeading = array_pop($paragraphs);

        $blurb = $this->flatten($paragraphs[0]->textContent);
        $paragraphs[0]->parentNode?->removeChild($paragraphs[0]);
        $rest = array_slice($paragraphs, 1);

        $answer = array_shift($rest);

        // Split the intro's remaining paragraphs around its one sub-heading.
        $prose = $rest;
        $secretHeading = null;
        $secretBody = [];

        foreach ($rest as $index => $paragraph) {
            if ($this->isSubHeading($paragraph)) {
                $prose = array_slice($rest, 0, $index);
                $secretHeading = $paragraph;
                $secretBody = array_slice($rest, $index + 1);
                break;
            }
        }

        if ($answer instanceof \DOMElement) {
            $this->buildAnswer($dom, $wrapper, $answer);
        }

        if ($prose !== []) {
            $proseBlock = $dom->createElement('div');
            $proseBlock->setAttribute('class', 'home-intro-prose');
            $wrapper->insertBefore($proseBlock, $prose[0]);

            foreach ($prose as $paragraph) {
                $proseBlock->appendChild($paragraph);
            }
        }

        if ($secretHeading instanceof \DOMElement) {
            $this->buildSecret($dom, $wrapper, $secretHeading, $secretBody, $copy);
        }

        $this->buildInside($dom, $wrapper, $insideHeading, $list, $copy);

        // The headline leaves the body for the hero band. Its last word is
        // handed over separately so the hero can paint the two tones.
        $words = preg_split('/\s+/u', $this->flatten($heading->textContent), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $heading->parentNode?->removeChild($heading);

        if ($words === []) {
            return null;
        }

        $key = array_pop($words);

        return [
            'lead' => implode(' ', $words),
            'key' => $key,
            'blurb' => $blurb,
        ];
    }

    /**
     * The reflow this class did before the redesign, kept as the fallback
     * for an export whose intro is not shaped like the homepage's: prose in
     * two columns, the lead-in paragraph above the list.
     *
     * @param list<\DOMElement> $paragraphs
     */
    private function plainReflow(\DOMDocument $dom, \DOMElement $wrapper, array $paragraphs, ?\DOMNode $list): void
    {
        if (count($paragraphs) < 3 || !$list instanceof \DOMElement) {
            return;
        }

        $lead = array_pop($paragraphs);

        $prose = $dom->createElement('div');
        $prose->setAttribute('class', 'home-intro-prose');
        $wrapper->insertBefore($prose, $paragraphs[0]);

        foreach ($paragraphs as $paragraph) {
            $prose->appendChild($paragraph);
        }

        if ($lead instanceof \DOMElement) {
            $lead->setAttribute('class', trim($lead->getAttribute('class') . ' home-intro-lead'));
        }

        $list->setAttribute('class', trim($list->getAttribute('class') . ' home-intro-list'));
    }

    /**
     * The answer callout: a rule down the left, the opening clause set as
     * display type, the rest as body copy. The sentence is cut at its own
     * first comma; with no comma to cut at, the whole sentence is the
     * display line and nothing is lost.
     */
    private function buildAnswer(\DOMDocument $dom, \DOMElement $wrapper, \DOMElement $paragraph): void
    {
        [$lead, $tail] = $this->splitOnce($this->flatten($paragraph->textContent), self::CLAUSE_DELIMITERS, true);

        $block = $dom->createElement('div');
        $block->setAttribute('class', 'home-answer');
        $wrapper->insertBefore($block, $paragraph);

        $rule = $dom->createElement('span');
        $rule->setAttribute('class', 'home-answer-rule');
        $block->appendChild($rule);

        $copyBlock = $dom->createElement('p');
        $copyBlock->setAttribute('class', 'home-answer-copy');
        $block->appendChild($copyBlock);

        $leadSpan = $dom->createElement('span');
        $leadSpan->setAttribute('class', 'home-answer-lead');
        $leadSpan->textContent = $lead;
        $copyBlock->appendChild($leadSpan);

        if ($tail !== '') {
            $tailSpan = $dom->createElement('span');
            $tailSpan->setAttribute('class', 'home-answer-body');
            $tailSpan->textContent = $tail;
            $copyBlock->appendChild($tailSpan);
        }

        $paragraph->parentNode?->removeChild($paragraph);
    }

    /**
     * The "what's the secret?" section: an eyebrow and the sub-heading
     * promoted to a real <h2> beside its paragraphs, with a tinted panel in
     * the column the design gives to photography. The panel carries no
     * <img> - there is no artwork for it in this tree yet - so it is drawn
     * by the stylesheet and marked decorative.
     *
     * @param list<\DOMElement> $body
     * @param array<string, string> $copy
     */
    private function buildSecret(\DOMDocument $dom, \DOMElement $wrapper, \DOMElement $heading, array $body, array $copy): void
    {
        $section = $dom->createElement('div');
        $section->setAttribute('class', 'home-secret');
        $wrapper->insertBefore($section, $heading);

        $media = $dom->createElement('div');
        $media->setAttribute('class', 'home-secret-media');
        $media->setAttribute('aria-hidden', 'true');
        $section->appendChild($media);

        $label = $dom->createElement('span');
        $label->setAttribute('class', 'home-secret-media-label');
        $label->textContent = $copy['media_label'];
        $media->appendChild($label);

        $copyBlock = $dom->createElement('div');
        $copyBlock->setAttribute('class', 'home-secret-copy');
        $section->appendChild($copyBlock);

        $eyebrow = $dom->createElement('p');
        $eyebrow->setAttribute('class', 'home-eyebrow');
        $eyebrow->textContent = $copy['short_answer'];
        $copyBlock->appendChild($eyebrow);

        // The paragraph was a heading in everything but its tag; it becomes
        // one, carrying its own words across unchanged.
        $title = $dom->createElement('h2');
        $title->setAttribute('class', 'home-secret-title');
        $title->textContent = $this->flatten($heading->textContent);
        $copyBlock->appendChild($title);

        $heading->parentNode?->removeChild($heading);

        foreach ($body as $paragraph) {
            $copyBlock->appendChild($paragraph);
        }
    }

    /**
     * The "what's inside" block: an eyebrow, the lead-in paragraph promoted
     * to a heading, and the list dressed as the numbered card grid. The
     * lead-in is two sentences ("This is a FREE guide… Right here you will
     * find:"), so it is cut at its own full stop - the first sentence sets
     * the heading, the second the line under it.
     *
     * @param array<string, string> $copy
     */
    private function buildInside(\DOMDocument $dom, \DOMElement $wrapper, ?\DOMElement $heading, \DOMElement $list, array $copy): void
    {
        $block = $dom->createElement('div');
        $block->setAttribute('class', 'home-inside');
        $wrapper->insertBefore($block, $heading ?? $list);

        $eyebrow = $dom->createElement('p');
        $eyebrow->setAttribute('class', 'home-eyebrow');
        $eyebrow->textContent = $copy['whats_inside'];
        $block->appendChild($eyebrow);

        if ($heading instanceof \DOMElement) {
            [$lead, $tail] = $this->splitOnce($this->flatten($heading->textContent), self::SENTENCE_DELIMITERS, true);

            $title = $dom->createElement('h2');
            $title->setAttribute('class', 'home-inside-title');
            $title->textContent = $lead;
            $block->appendChild($title);

            if ($tail !== '') {
                $sub = $dom->createElement('p');
                $sub->setAttribute('class', 'home-inside-sub');
                $sub->textContent = $tail;
                $block->appendChild($sub);
            }

            $heading->parentNode?->removeChild($heading);
        }

        $list->setAttribute('class', trim($list->getAttribute('class') . ' home-intro-list'));
        $block->appendChild($list);
    }

    /**
     * Whether a paragraph is really a sub-heading: short, and with all of
     * its text inside a single <strong>/<b>.
     */
    private function isSubHeading(\DOMElement $paragraph): bool
    {
        $text = $this->flatten($paragraph->textContent);

        if ($text === '' || mb_strlen($text) > self::HEADING_MAX_LENGTH) {
            return false;
        }

        $emphasis = null;

        foreach ($paragraph->childNodes as $child) {
            if ($child instanceof \DOMText) {
                if (trim($child->nodeValue ?? '') !== '') {
                    return false;
                }

                continue;
            }

            if (!$child instanceof \DOMElement || !in_array(strtolower($child->nodeName), ['strong', 'b'], true)) {
                return false;
            }

            if ($emphasis !== null) {
                return false;
            }

            $emphasis = $child;
        }

        return $emphasis !== null && $this->flatten($emphasis->textContent) === $text;
    }

    /**
     * Cut a string once, at the first of the given delimiters that it
     * actually contains. The delimiter stays with the first half when
     * $keepDelimiter is set, because it is punctuation the sentence needs
     * ("If YES is your answer," reads as a clause, "If YES is your answer"
     * does not). No delimiter found means no cut: the whole string comes
     * back as the first half, so nothing is ever dropped.
     *
     * @param list<string> $delimiters
     *
     * @return array{0: string, 1: string}
     */
    private function splitOnce(string $text, array $delimiters, bool $keepDelimiter): array
    {
        $at = null;
        $length = 0;

        foreach ($delimiters as $delimiter) {
            $position = mb_strpos($text, $delimiter);

            if ($position === false) {
                continue;
            }

            if ($at === null || $position < $at) {
                $at = $position;
                $length = mb_strlen($delimiter);
            }
        }

        if ($at === null) {
            return [$text, ''];
        }

        $head = mb_substr($text, 0, $at + ($keepDelimiter ? $length : 0));
        $tail = mb_substr($text, $at + $length);

        return [trim($head), trim($tail)];
    }

    /** @param array<string, string> $copy */
    private function plural(array $copy, int $count): string
    {
        return sprintf($copy[$count === 1 ? 'guides_one' : 'guides_many'], $count);
    }

    private function firstByClass(\DOMXPath $xpath, string $class, ?\DOMElement $context = null): ?\DOMElement
    {
        $query = ($context === null ? '//' : './/')
            . '*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]';

        $node = $context === null ? $xpath->query($query)->item(0) : $xpath->query($query, $context)->item(0);

        return $node instanceof \DOMElement ? $node : null;
    }

    private function hasClass(\DOMElement $element, string $class): bool
    {
        return str_contains(' ' . $element->getAttribute('class') . ' ', ' ' . $class . ' ');
    }

    /** Collapse the export's line breaks inside a label to single spaces. */
    private function flatten(string $text): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    private function slug(string $text): string
    {
        $slug = strtolower(trim($text));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';

        return trim($slug, '-');
    }
}
