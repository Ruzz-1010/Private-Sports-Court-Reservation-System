<?php

namespace App\Console\Commands;

use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Renders the technical paper from Markdown to a paginated PDF.
 *
 * The Markdown file is the single source of truth. Edit it, then re-run this
 * command, rather than editing the PDF directly.
 */
class BuildPaper extends Command
{
    protected $signature = 'paper:build
        {--source=docs/papers/SYSIN_Laravel_System_Paper.md : Markdown source}
        {--output=docs/papers/SYSIN_Laravel_System_Paper.pdf : PDF destination}';

    protected $description = 'Render the technical paper from Markdown into a paginated PDF';

    public function handle(): int
    {
        $source = base_path((string) $this->option('source'));
        $output = base_path((string) $this->option('output'));

        if (! File::exists($source)) {
            $this->error("Source not found: {$source}");

            return self::FAILURE;
        }

        $html = $this->toHtml(File::get($source));

        $scratch = storage_path('app/paper-build');
        File::ensureDirectoryExists($scratch);
        $htmlPath = "{$scratch}/paper.html";
        File::put($htmlPath, $html);

        // dompdf is already a dependency for receipts. Only bundled fonts are
        // available offline, so the document uses DejaVu Sans rather than a
        // serif face that would have to be fetched.
        // dompdf refuses to load files outside its chroot, so the chroot is
        // pointed at the application root where the scratch file lives.
        $options = new Options;
        $options->setIsRemoteEnabled(false);
        $options->setChroot(base_path());

        $dompdf = new Dompdf($options);
        $dompdf->loadHtmlFile($htmlPath);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        File::ensureDirectoryExists(dirname($output));
        File::put($output, $dompdf->output());

        File::deleteDirectory($scratch);

        $pages = $dompdf->getCanvas()->get_page_count();

        $this->info('Paper built.');
        $this->line('  pages .... '.$pages);
        $this->line('  size ..... '.number_format(strlen(File::get($output)) / 1024).' KB');
        $this->line('  file ..... '.Str::after($output, base_path().DIRECTORY_SEPARATOR));

        return self::SUCCESS;
    }

    private function toHtml(string $markdown): string
    {
        $title = collect(explode("\n", $markdown))
            ->first(fn (string $line): bool => str_starts_with($line, '# '))
            ? str_replace('# ', '', collect(explode("\n", $markdown))
                ->first(fn (string $line): bool => str_starts_with($line, '# ')))
            : 'Technical Documentation';

        // Everything before the first chapter is the title sheet. Wrapping it
        // lets the layout centre it and space the date to the foot of the page
        // while the chapters below keep the running body style.
        $parts = preg_split('/^# Chapter/m', $markdown, 2) ?: [$markdown];
        $body = '<div class="title-page">'.$this->markdownToHtml((string) $parts[0]).'</div>';

        if (isset($parts[1])) {
            $body .= "\n".$this->markdownToHtml('# Chapter'.$parts[1]);
        }

        return view('paper.layout', ['body' => $body, 'title' => $title])->render();
    }

    /**
     * A deliberately small Markdown subset: headings, paragraphs, bold, code,
     * fenced blocks, ordered and unordered lists, tables, and rules.
     *
     * A full Markdown parser is not a dependency of this project, and the
     * paper uses only these constructs.
     */
    private function markdownToHtml(string $markdown): string
    {
        $lines = preg_split('/\R/', $markdown) ?: [];
        $html = [];
        $paragraph = [];
        $listType = null;
        $inCode = false;
        $code = [];

        $flushParagraph = function () use (&$paragraph, &$html): void {
            if ($paragraph === []) {
                return;
            }

            $text = $this->inline(implode(' ', $paragraph));
            $html[] = "<p>{$text}</p>";
            $paragraph = [];
        };

        $closeList = function () use (&$listType, &$html): void {
            if ($listType !== null) {
                $html[] = $listType === 'ul' ? '</ul>' : '</ol>';
                $listType = null;
            }
        };

        // An explicit index is required: advancing $i to skip a consumed table
        // has no effect on a foreach, which iterates its own internal copy.
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            // Image: ![alt](path). Paths are resolved relative to the paper so
            // the rendered document embeds the real diagram instead of
            // approximating it with characters, which wrap unpredictably.
            if (preg_match('/^!\[([^\]]*)\]\(([^)]+)\)\s*$/', trim($line), $m) === 1) {
                $flushParagraph();
                $closeList();
                $html[] = $this->image($m[1], $m[2]);

                continue;
            }

            // Fenced code block.
            if (str_starts_with(trim($line), '```')) {
                if ($inCode) {
                    $html[] = '<pre><code>'.e(implode("\n", $code)).'</code></pre>';
                    $code = [];
                    $inCode = false;
                } else {
                    $flushParagraph();
                    $closeList();
                    $inCode = true;
                }

                continue;
            }

            if ($inCode) {
                $code[] = $line;

                continue;
            }

            if (trim($line) === '') {
                $flushParagraph();
                $closeList();

                continue;
            }

            // Horizontal rule.
            if (preg_match('/^-{3,}$/', trim($line)) === 1) {
                $flushParagraph();
                $closeList();
                $html[] = '<hr />';

                continue;
            }

            // Heading.
            if (preg_match('/^(#{1,6})\s+(.*)$/', $line, $m) === 1) {
                $flushParagraph();
                $closeList();
                $level = strlen($m[1]);

                // The reference document opens each chapter on two centred
                // lines, the chapter number above the name in capitals.
                // "Chapter 4 — Implementation Details" is written that way in
                // the source, so it is split here rather than restyled in CSS.
                if ($level === 1
                    && preg_match('/^(Chapter\s+\d+)\s*(?:[—–-])\s*(.+)$/iu', $m[2], $cm) === 1) {
                    $html[] = '<h1><span class="chapter-label">'.$this->inline($cm[1])
                        .'</span><span class="chapter-name">'.$this->inline($cm[2])
                        .'</span></h1>';

                    continue;
                }

                $html[] = "<h{$level}>{$this->inline($m[2])}</h{$level}>";

                continue;
            }

            // Table: a pipe row followed by a separator row.
            $next = $lines[$i + 1] ?? null;

            if (str_contains($line, '|')
                && is_string($next)
                && preg_match('/^\s*\|?[\s:|-]+\|[\s:|-]*$/', $next) === 1) {
                $flushParagraph();
                $closeList();
                $html[] = $this->renderTable($lines, $i);
                // Resume just past the last consumed row. The +1 accounts for the
                // loop's own $i++, so the final row is not re-emitted
                // as a stray paragraph.
                $i += $this->skipTable($lines, $i) + 1;

                continue;
            }

            // List item.
            if (preg_match('/^\s*(?:([-*])|(\d+)\.)\s+(.*)$/', $line, $m) === 1) {
                $flushParagraph();
                $type = $m[1] !== '' ? 'ul' : 'ol';

                if ($listType !== $type) {
                    $closeList();
                    $html[] = $type === 'ul' ? '<ul>' : '<ol>';
                    $listType = $type;
                }

                $html[] = '<li>'.$this->inline($m[3]).'</li>';

                continue;
            }

            $paragraph[] = trim($line);
        }

        $flushParagraph();
        $closeList();

        if ($inCode && $code !== []) {
            $html[] = '<pre><code>'.e(implode("\n", $code)).'</code></pre>';
        }

        return implode("\n", $html);
    }

    /**
     * Only relative paths inside the repository are accepted, so a document
     * can never cause the build to read an arbitrary file from the filesystem.
     */
    private function image(string $alt, string $path): string
    {
        $absolute = str_starts_with($path, '/')
            ? $path
            : base_path(dirname((string) $this->option('source')).'/'.$path);

        if (! str_starts_with($path, '/') && ! File::exists($absolute)) {
            // Fall back to the paper directory itself.
            $absolute = base_path('docs/papers/'.$path);
        }

        if (! File::exists($absolute) || ! str_starts_with($absolute, base_path())) {
            return '<p><em>[missing image: '.e($alt).']</em></p>';
        }

        // A tall diagram scaled only to the printable width would overflow the
        // page and be pushed onto the next sheet, leaving a near-empty page.
        // Dimensions are computed so the image fits both constraints.
        $size = @getimagesize($absolute);

        if ($size === false) {
            return '<p><em>[unreadable image: '.e($alt).']</em></p>';
        }

        [$width, $height] = $size;

        // Diagrams want the full printable width. The school seal on the title
        // sheet must not, or it would occupy a third of the page: it is held to
        // the size used by the reference document instead.
        $isSeal = str_contains($path, 'assets/');
        $maxWidth = $isSeal ? 34.0 : 178.0;
        $maxHeight = $isSeal ? 34.0 : 218.0;

        $scale = min($maxWidth / $width, $maxHeight / $height);
        $renderWidth = $width * $scale;
        $renderHeight = $height * $scale;

        return '<figure><img src="'.e($absolute)
            .'" alt="'.e($alt)
            .'" style="width: '.round($renderWidth, 1).'mm; height: '.round($renderHeight, 1).'mm;"'
            .' width="'.round($renderWidth, 1).'mm" height="'.round($renderHeight, 1).'mm" /></figure>';
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function renderTable(array $lines, int $start): string
    {
        // trim() removes the outer pipes, so every remaining segment is a cell.
        // Slicing off the last element here would drop a real column.
        $cells = fn (string $row): array => array_map(
            fn (string $cell): string => $this->inline($cell),
            explode('|', trim($row, " \t|")),
        );

        $html = ['<table><thead><tr>'];
        foreach ($cells($lines[$start]) as $cell) {
            $html[] = "<th>{$cell}</th>";
        }
        $html[] = '</tr></thead><tbody>';

        for ($i = $start + 2; $i < count($lines); $i++) {
            if (! str_contains($lines[$i], '|') || trim($lines[$i]) === '') {
                break;
            }

            $html[] = '<tr>';
            foreach ($cells($lines[$i]) as $cell) {
                $html[] = "<td>{$cell}</td>";
            }
            $html[] = '</tr>';
        }

        $html[] = '</tbody></table>';

        return implode('', $html);
    }

    /**
     * @param  array<int, string>  $lines
     */
    private function skipTable(array $lines, int $start): int
    {
        $skipped = 0;

        for ($i = $start + 2; $i < count($lines); $i++) {
            if (! str_contains($lines[$i], '|') || trim($lines[$i]) === '') {
                break;
            }

            $skipped++;
        }

        return $skipped;
    }

    /**
     * Inline emphasis and code spans, with everything else escaped.
     */
    private function inline(string $text): string
    {
        $placeholders = [];

        $text = preg_replace_callback('/`([^`]+)`/', function (array $m) use (&$placeholders): string {
            $key = "\x01CODE".count($placeholders)."\x01";
            $placeholders[$key] = '<code>'.e($m[1]).'</code>';

            return $key;
        }, $text) ?? $text;

        $text = e($text);
        $text = preg_replace('/\*\*(.+?)\*\*/s', '<strong>$1</strong>', $text) ?? $text;
        $text = preg_replace('/(?<!\*)\*([^*]+?)\*(?!\*)/s', '<em>$1</em>', $text) ?? $text;

        return strtr($text, $placeholders);
    }
}
