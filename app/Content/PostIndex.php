<?php

declare(strict_types=1);

namespace App\Content;

/**
 * The WordPress post list, as the export's wp-json snapshot recorded it:
 * every post's id, URL, title, dates, excerpt and categories.
 *
 * Read for the things the exported HTML cannot answer on its own - which
 * posts share a category (for "related guides"), what the newest posts are
 * (for the RSS feed), and which post an old "?p=123" shortlink meant.
 */
final class PostIndex
{
    /**
     * Categories that say nothing about a post's topic: the catch-all blog
     * bucket, WordPress's default, and a "parent" grouping used by the old
     * theme's menus.
     */
    private const GENERIC_CATEGORIES = [1, 13, 55];

    /** @var array<int, array{id: int, path: string, title: string, published: string, modified: string, excerpt: string, categories: list<int>}>|null */
    private ?array $posts = null;

    /** @var array<int, array{name: string, path: string}>|null */
    private ?array $categories = null;

    public function __construct(private readonly string $baseDir)
    {
    }

    /**
     * @return array<int, array{id: int, path: string, title: string, published: string, modified: string, excerpt: string, categories: list<int>}>
     */
    public function posts(): array
    {
        return $this->posts ??= $this->load('posts');
    }

    /** @return array{id: int, path: string, title: string, published: string, modified: string, excerpt: string, categories: list<int>}|null */
    public function byPath(string $path): ?array
    {
        foreach ($this->posts() as $post) {
            if ($post['path'] === $path) {
                return $post;
            }
        }

        return null;
    }

    /** Site path of the post or page with this WordPress id, for "?p=" shortlinks. */
    public function pathForId(int $id): ?string
    {
        $posts = $this->posts();

        if (isset($posts[$id])) {
            return $posts[$id]['path'];
        }

        $pages = $this->load('pages');

        return isset($pages[$id]) ? $pages[$id]['path'] : null;
    }

    /**
     * Up to $limit other posts from the most specific category this post is
     * in, most recently updated first.
     *
     * @return list<array{id: int, path: string, title: string, published: string, modified: string, excerpt: string, categories: list<int>}>
     */
    public function related(string $path, int $limit = 4): array
    {
        $post = $this->byPath($path);

        if ($post === null) {
            return [];
        }

        $category = $this->primaryCategory($post);

        if ($category === null) {
            return [];
        }

        $related = array_filter(
            $this->posts(),
            static fn (array $other): bool => $other['id'] !== $post['id'] && in_array($category, $other['categories'], true),
        );

        usort($related, static fn (array $a, array $b): int => strcmp($b['modified'], $a['modified']));

        return array_slice(array_values($related), 0, $limit);
    }

    /** @return array{name: string, path: string}|null */
    public function category(int $id): ?array
    {
        $this->categories ??= $this->loadCategories();

        return $this->categories[$id] ?? null;
    }

    /**
     * The narrowest named category a post belongs to - the one with the
     * fewest posts in it - ignoring the generic buckets.
     *
     * @param array{categories: list<int>} $post
     */
    public function primaryCategory(array $post): ?int
    {
        $counts = [];

        foreach ($post['categories'] as $id) {
            if (in_array($id, self::GENERIC_CATEGORIES, true) || $this->category($id) === null) {
                continue;
            }

            $counts[$id] = count(array_filter($this->posts(), static fn (array $p): bool => in_array($id, $p['categories'], true)));
        }

        if ($counts === []) {
            return null;
        }

        asort($counts);

        return (int) array_key_first($counts);
    }

    /**
     * @return array<int, array{id: int, path: string, title: string, published: string, modified: string, excerpt: string, categories: list<int>}>
     */
    private function load(string $type): array
    {
        $dir = $this->baseDir . '/wp-json/wp/v2/' . $type;
        $items = [];

        if (!is_dir($dir)) {
            return [];
        }

        foreach (new \DirectoryIterator($dir) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $data = json_decode((string) file_get_contents($file->getPathname()), true);

            if (!is_array($data) || !isset($data['id'], $data['link']) || ($data['status'] ?? 'publish') !== 'publish') {
                continue;
            }

            $path = (string) parse_url((string) $data['link'], PHP_URL_PATH);
            $path = $path === '' ? '/' : '/' . trim($path, '/');

            $items[(int) $data['id']] = [
                'id' => (int) $data['id'],
                'path' => $path,
                'title' => self::text((string) ($data['title']['rendered'] ?? '')),
                'published' => self::date((string) ($data['date_gmt'] ?? $data['date'] ?? '')),
                'modified' => self::date((string) ($data['modified_gmt'] ?? $data['modified'] ?? '')),
                'excerpt' => self::text((string) ($data['excerpt']['rendered'] ?? '')),
                'categories' => array_values(array_map('intval', (array) ($data['categories'] ?? []))),
            ];
        }

        ksort($items);

        return $items;
    }

    /** @return array<int, array{name: string, path: string}> */
    private function loadCategories(): array
    {
        $dir = $this->baseDir . '/wp-json/wp/v2/categories';
        $items = [];

        if (!is_dir($dir)) {
            return [];
        }

        foreach (new \DirectoryIterator($dir) as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $data = json_decode((string) file_get_contents($file->getPathname()), true);

            if (!is_array($data) || !isset($data['id'], $data['name'], $data['link'])) {
                continue;
            }

            $items[(int) $data['id']] = [
                'name' => self::text((string) $data['name']),
                'path' => '/' . trim((string) parse_url((string) $data['link'], PHP_URL_PATH), '/'),
            ];
        }

        return $items;
    }

    private static function text(string $html): string
    {
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim((string) preg_replace('~\s+~u', ' ', $text));
    }

    private static function date(string $value): string
    {
        if ($value === '') {
            return '';
        }

        $time = strtotime($value . (preg_match('~(Z|[+-]\d\d:?\d\d)$~', $value) === 1 ? '' : ' UTC'));

        return $time === false ? '' : gmdate('c', $time);
    }
}
